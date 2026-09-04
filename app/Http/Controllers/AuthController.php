<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Services\Crypto\KeyManager;
use App\Services\Crypto\RSAEngine;
use App\Services\Crypto\MACEngine;
use App\Services\Auth\AuthEngine;
use App\Mail\SendOtpMail;
use Exception;

class AuthController extends Controller
{
    private KeyManager $kmm;
    private RSAEngine $rsaEngine;
    private MACEngine $macEngine;
    private AuthEngine $authEngine;

    private string $loginLookupKey = "SecureHostelLoginLookupKey";
    private string $rowIntegrityKey = "SecureHostelRowIntegrityKey";

    public function __construct(
        KeyManager $kmm,
        RSAEngine $rsaEngine,
        MACEngine $macEngine,
        AuthEngine $authEngine
    ) {
        $this->kmm = $kmm;
        $this->rsaEngine = $rsaEngine;
        $this->macEngine = $macEngine;
        $this->authEngine = $authEngine;
    }

    /**
     * Handles secure user registration with Admin secret enforcement and Warden approval workflows.
     */
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:student,warden,admin',
            'admin_secret' => 'nullable|string'
        ]);

        if ($request->role === 'admin' && $request->admin_secret !== '12345') {
            return response()->json(['error' => 'Invalid Admin Secret Key. Registration denied.'], 403);
        }

        $isApproved = ($request->role === 'warden') ? false : true;

        try {
            $this->ensureKeysExist();

            $loginHash = $this->macEngine->generateMAC($request->username, $this->loginLookupKey);

            if (User::where('login_hash', $loginHash)->exists()) {
                return response()->json(['error' => 'Username already taken.'], 409);
            }

            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $publicKey = $rsaKeys['public_key'];

            $encUsername = $this->encryptLongText($request->username, $publicKey);
            $encEmail = $this->encryptLongText($request->email, $publicKey);

            $salt = $this->authEngine->generateSalt();
            $hashedPassword = $this->authEngine->hashPassword($request->password, $salt);

            $twoFactorSecret = bin2hex(random_bytes(16));
            $encTwoFactor = $this->encryptLongText($twoFactorSecret, $publicKey);

            $approvalFlag = $isApproved ? '1' : '0';
            $dataToAuthenticate = $loginHash . $encUsername . $encEmail . $salt . $hashedPassword . $encTwoFactor . $request->role . $approvalFlag;
            $rowMac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            $user = User::create([
                'role' => $request->role,
                'login_hash' => $loginHash,
                'encrypted_username' => $encUsername,
                'encrypted_email' => $encEmail,
                'password_salt' => $salt,
                'hashed_password' => $hashedPassword,
                'encrypted_two_factor_secret' => $encTwoFactor,
                'row_mac' => $rowMac,
                'is_approved' => $isApproved
            ]);

            $msg = $request->role === 'warden' 
                ? 'Warden registration successful! Your account is pending admin approval.' 
                : 'Registration successful!';

            return response()->json([
                'message' => $msg,
                'user_id' => $user->id,
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Cryptographic failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 1 of Login: Verify credentials, check row MAC integrity, 
     * check warden approval status, decrypt email, and send dynamic OTP via Laravel Mail.
     */
    public function sendLoginOtp(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $loginHash = $this->macEngine->generateMAC($request->username, $this->loginLookupKey);
            $user = User::where('login_hash', $loginHash)->first();

            if (!$user) {
                return response()->json(['error' => 'Invalid credentials.'], 401);
            }

            $approvalFlag = $user->is_approved ? '1' : '0';
            $dataToAuthenticate = $user->login_hash . $user->encrypted_username . $user->encrypted_email . $user->password_salt . $user->hashed_password . $user->encrypted_two_factor_secret . $user->role . $approvalFlag . ($user->profile_photo ?? '') . ($user->encrypted_phone ?? '') . ($user->encrypted_student_id ?? '') . ($user->encrypted_address ?? '');
            
            if (!$this->macEngine->verifyMAC($dataToAuthenticate, $this->rowIntegrityKey, $user->row_mac)) {
                return response()->json(['error' => 'CRITICAL: Database tampering detected on this user record.'], 403);
            }

            $hashedAttempt = $this->authEngine->hashPassword($request->password, $user->password_salt);
            if (!hash_equals($user->hashed_password, $hashedAttempt)) {
                return response()->json(['error' => 'Invalid credentials.'], 401);
            }

            if ($user->role === 'warden' && !$user->is_approved) {
                return response()->json(['error' => 'Your warden account is pending approval by an administrator.'], 403);
            }

            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $realEmail = $this->decryptLongText($user->encrypted_email, $rsaKeys['private_key']);

            $emailOtp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            session([
                'otp_user_id' => $user->id,
                'otp_code' => $emailOtp,
                'otp_expires' => now()->addMinutes(5)
            ]);

            Mail::to($realEmail)->send(new SendOtpMail($emailOtp));

            return response()->json([
                'message' => 'OTP sent successfully to your registered Gmail address.',
                'require_otp' => true
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Login error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 2 of Login: Verify the emailed code and finalize the session.
     */
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        $userId = session('otp_user_id');
        $storedOtp = session('otp_code');
        $expiresAt = session('otp_expires');

        if (!$userId || !$storedOtp || now()->greaterThan($expiresAt)) {
            return response()->json(['error' => 'OTP session expired. Please log in again.'], 401);
        }

        if (!hash_equals($storedOtp, $request->otp)) {
            return response()->json(['error' => 'Invalid OTP code.'], 401);
        }

        $user = User::findOrFail($userId);

        session()->forget(['otp_user_id', 'otp_code', 'otp_expires']);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'role' => $user->role
        ], 200);
    }

    /**
     * Get authenticated user's decrypted profile data (RSA)
     */
    public function getProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $privateKey = $rsaKeys['private_key'];

            $username = $this->decryptLongText($user->encrypted_username, $privateKey);
            $email = $this->decryptLongText($user->encrypted_email, $privateKey);
            $phone = $user->encrypted_phone ? $this->decryptLongText($user->encrypted_phone, $privateKey) : null;
            $studentId = $user->encrypted_student_id ? $this->decryptLongText($user->encrypted_student_id, $privateKey) : null;
            $address = $user->encrypted_address ? $this->decryptLongText($user->encrypted_address, $privateKey) : null;

            return response()->json([
                'profile' => [
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone,
                    'student_id' => $studentId,
                    'address' => $address,
                    'profile_photo' => $user->profile_photo
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Decryption failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update user's profile fields with RSA encryption and Row MAC recalculation
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:50',
            'student_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $publicKey = $rsaKeys['public_key'];

            $encPhone = $request->phone ? $this->encryptLongText($request->phone, $publicKey) : null;
            $encStudentId = $request->student_id ? $this->encryptLongText($request->student_id, $publicKey) : null;
            $encAddress = $request->address ? $this->encryptLongText($request->address, $publicKey) : null;

            $user->encrypted_phone = $encPhone;
            $user->encrypted_student_id = $encStudentId;
            $user->encrypted_address = $encAddress;

            $approvalFlag = (string)($user->is_approved ?? 0);
            $dataToAuthenticate = $user->login_hash . $user->encrypted_username . $user->encrypted_email . $user->password_salt . $user->hashed_password . $user->encrypted_two_factor_secret . $user->role . $approvalFlag . ($user->profile_photo ?? '') . ($user->encrypted_phone ?? '') . ($user->encrypted_student_id ?? '') . ($user->encrypted_address ?? '');
            
            $user->row_mac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);
            $user->save();

            return response()->json(['message' => 'Profile updated successfully.'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Encryption failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ADMIN ONLY: Get all non-admin users with decrypted details.
     */
    public function getAllUsers()
    {
        try {
            $users = User::where('role', '!=', 'admin')->get();
            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $privateKey = $rsaKeys['private_key'];

            $userList = [];
            foreach ($users as $user) {
                $username = $this->decryptLongText($user->encrypted_username, $privateKey);
                $email = $this->decryptLongText($user->encrypted_email, $privateKey);

                $userList[] = [
                    'id' => $user->id,
                    'username' => $username,
                    'email' => $email,
                    'role' => $user->role,
                    'is_approved' => $user->is_approved,
                ];
            }

            return response()->json(['users' => $userList], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch users: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ADMIN ONLY: Permanently delete a user account and related records.
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->role === 'admin') {
                return response()->json(['error' => 'Cannot delete administrator accounts.'], 403);
            }

            \App\Models\RoomApplication::where('user_id', $user->id)->delete();
            \App\Models\RoomAllocation::where('user_id', $user->id)->delete();
            
            $user->delete();

            return response()->json(['message' => 'User deleted permanently.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Securely destroys the session.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    private function ensureKeysExist(): void
    {
        try {
            $this->kmm->getActiveKey('rsa');
        } catch (Exception $e) {
            $this->kmm->initMasterKey();
            $this->kmm->rotateKeys();
        }
    }

    private function encryptLongText(string $text, array $publicKey): string
    {
        $chunks = str_split($text, 8);
        $encryptedChunks = [];
        foreach ($chunks as $chunk) {
            $encryptedChunks[] = $this->rsaEngine->encrypt($chunk, $publicKey);
        }
        return json_encode($encryptedChunks);
    }

    private function decryptLongText(string $jsonChunks, array $privateKey): string
    {
        if (!$jsonChunks) return '';
        $chunks = json_decode($jsonChunks, true);
        if (!is_array($chunks)) return $jsonChunks;
        $text = '';
        foreach ($chunks as $chunk) {
            $text .= $this->rsaEngine->decrypt($chunk, $privateKey);
        }
        return $text;
    }
}