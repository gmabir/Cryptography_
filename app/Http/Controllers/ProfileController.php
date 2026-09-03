<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Crypto\KeyManager;
use App\Services\Crypto\RSAEngine;
use App\Services\Crypto\MACEngine;
use Exception;

class ProfileController extends Controller
{
    private KeyManager $kmm;
    private RSAEngine $rsaEngine;
    private MACEngine $macEngine;
    
    private string $rowIntegrityKey = "SecureHostelRowIntegrityKey";

    public function __construct(KeyManager $kmm, RSAEngine $rsaEngine, MACEngine $macEngine)
    {
        $this->kmm = $kmm;
        $this->rsaEngine = $rsaEngine;
        $this->macEngine = $macEngine;
    }

    /**
     * Retrieves and decrypts the authenticated user's profile.
     */
    public function show()
    {
        try {
            $user = Auth::user();
            
            // 1. Get the Active RSA Private Key
            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $privateKey = $rsaKeys['private_key'];

            // 2. Decrypt all profile fields
            $profile = [
                'role' => $user->role,
                'username' => $this->decryptLongText($user->encrypted_username, $privateKey),
                'email' => $this->decryptLongText($user->encrypted_email, $privateKey),
                'phone' => $user->encrypted_phone ? $this->decryptLongText($user->encrypted_phone, $privateKey) : null,
                'student_id' => $user->encrypted_student_id ? $this->decryptLongText($user->encrypted_student_id, $privateKey) : null,
                'address' => $user->encrypted_address ? $this->decryptLongText($user->encrypted_address, $privateKey) : null,
                'emergency_contact' => $user->encrypted_emergency_contact ? $this->decryptLongText($user->encrypted_emergency_contact, $privateKey) : null,
            ];

            return response()->json(['profile' => $profile], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to decrypt profile: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Encrypts and updates the user's profile data.
     */
    public function update(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'student_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255'
        ]);

        try {
            $user = Auth::user();
            
            // 1. Get the Active RSA Public Key
            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $publicKey = $rsaKeys['public_key'];

            // 2. Encrypt provided fields
            if ($request->has('phone')) {
                $user->encrypted_phone = $this->encryptLongText($request->phone, $publicKey);
            }
            if ($request->has('student_id')) {
                $user->encrypted_student_id = $this->encryptLongText($request->student_id, $publicKey);
            }
            if ($request->has('address')) {
                $user->encrypted_address = $this->encryptLongText($request->address, $publicKey);
            }
            if ($request->has('emergency_contact')) {
                $user->encrypted_emergency_contact = $this->encryptLongText($request->emergency_contact, $publicKey);
            }

            // 3. Recalculate Row Integrity MAC
            $dataToAuthenticate = $user->login_hash . $user->encrypted_username . $user->encrypted_email . $user->password_salt . $user->hashed_password . $user->encrypted_two_factor_secret . $user->role;
            $user->row_mac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            $user->save();

            return response()->json(['message' => 'Profile securely encrypted and updated.'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Cryptographic failure during update: ' . $e->getMessage()], 500);
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
        $chunks = json_decode($jsonChunks, true);
        $text = '';
        foreach ($chunks as $chunk) {
            $text .= $this->rsaEngine->decrypt($chunk, $privateKey);
        }
        return $text;
    }
}
