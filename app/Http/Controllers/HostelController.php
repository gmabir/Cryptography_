<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RoomApplication;
use App\Models\RoomAllocation;
use App\Models\User;
use App\Services\Crypto\KeyManager;
use App\Services\Crypto\ECCEngine;
use App\Services\Crypto\RSAEngine;
use App\Services\Crypto\MACEngine;
use Exception;

class HostelController extends Controller
{
    private KeyManager $kmm;
    private ECCEngine $eccEngine;
    private RSAEngine $rsaEngine;
    private MACEngine $macEngine;
    
    private string $rowIntegrityKey = "SecureHostelRowIntegrityKey";

    public function __construct(KeyManager $kmm, ECCEngine $eccEngine, RSAEngine $rsaEngine, MACEngine $macEngine)
    {
        $this->kmm = $kmm;
        $this->eccEngine = $eccEngine;
        $this->rsaEngine = $rsaEngine;
        $this->macEngine = $macEngine;
    }

    /**
     * STUDENT ONLY: Submit a new room application (ECC encrypted)
     */
    public function applyForRoom(Request $request)
    {
        $request->validate([
            'preferences' => 'required|string|max:500',
            'medical_needs' => 'nullable|string|max:500'
        ]);

        try {
            $user = Auth::user();
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $publicKey = $eccKeys['public_key'];

            $encPreferences = $this->encryptECCLongText($request->preferences, $publicKey);
            $encMedical = $request->medical_needs ? $this->encryptECCLongText($request->medical_needs, $publicKey) : null;
            $status = 'pending';

            $dataToAuthenticate = $user->id . $status . $encPreferences . ($encMedical ?? '');
            $rowMac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            RoomApplication::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => $status,
                    'encrypted_preferences' => $encPreferences,
                    'encrypted_medical_needs' => $encMedical,
                    'row_mac' => $rowMac
                ]
            );

            return response()->json(['message' => 'Room application securely submitted using ECC.'], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Cryptographic failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * STUDENT ONLY: View personal accommodation status
     */
    public function myAccommodation()
    {
        try {
            $user = Auth::user();
            $allocation = RoomAllocation::where('user_id', $user->id)->first();

            if (!$allocation) {
                return response()->json(['message' => 'No room allocated yet.'], 404);
            }

            $eccKeys = $this->kmm->getActiveKey('ecc');
            $privateKey = $eccKeys['private_key'];

            // Verify MAC Integrity
            $dataToAuthenticate = $allocation->user_id . $allocation->building_name . $allocation->room_number . ($allocation->encrypted_notes ?? '');
            if (!$this->macEngine->verifyMAC($dataToAuthenticate, $this->rowIntegrityKey, $allocation->row_mac)) {
                return response()->json(['error' => 'CRITICAL: Data tampering detected in room allocation.'], 403);
            }

            return response()->json([
                'allocation' => [
                    'building_name' => $allocation->building_name,
                    'room_number' => $allocation->room_number,
                    'notes' => $allocation->encrypted_notes ? $this->decryptECCLongText($allocation->encrypted_notes, $privateKey) : null
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Decryption failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * WARDEN/ADMIN ONLY: View all student room applications
     */
    public function viewApplications()
    {
        try {
            $applications = RoomApplication::all();
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $privateKey = $eccKeys['private_key'];

            $decryptedList = [];
            foreach ($applications as $app) {
                $dataToAuthenticate = $app->user_id . $app->status . $app->encrypted_preferences . ($app->encrypted_medical_needs ?? '');
                if (!$this->macEngine->verifyMAC($dataToAuthenticate, $this->rowIntegrityKey, $app->row_mac)) {
                    continue; // Skip corrupted rows silently
                }

                $decryptedList[] = [
                    'id' => $app->id,
                    'user_id' => $app->user_id,
                    'status' => $app->status,
                    'preferences' => $this->decryptECCLongText($app->encrypted_preferences, $privateKey),
                    'medical_needs' => $app->encrypted_medical_needs ? $this->decryptECCLongText($app->encrypted_medical_needs, $privateKey) : null
                ];
            }

            return response()->json(['applications' => $decryptedList], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Decryption failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * WARDEN/ADMIN ONLY: Allocate a room to a student
     */
    public function allocateRoom(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'building_name' => 'required|string|max:255',
            'room_number' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $publicKey = $eccKeys['public_key'];

            $encNotes = $request->notes ? $this->encryptECCLongText($request->notes, $publicKey) : null;

            $dataToAuthenticate = $request->student_id . $request->building_name . $request->room_number . ($encNotes ?? '');
            $rowMac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            RoomAllocation::updateOrCreate(
                ['user_id' => $request->student_id],
                [
                    'building_name' => $request->building_name,
                    'room_number' => $request->room_number,
                    'encrypted_notes' => $encNotes,
                    'row_mac' => $rowMac
                ]
            );

            // Update application status if exists
            RoomApplication::where('user_id', $request->student_id)->update(['status' => 'allocated']);

            return response()->json(['message' => 'Room successfully allocated via ECC.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Cryptographic failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ADMIN ONLY: View pending warden accounts awaiting approval with decrypted details
     */
    public function getPendingWardens()
    {
        try {
            $wardens = User::where('role', 'warden')->where('is_approved', 0)->get();
            $rsaKeys = $this->kmm->getActiveKey('rsa');
            $privateKey = $rsaKeys['private_key'];

            $pendingList = [];
            foreach ($wardens as $warden) {
                $username = 'Warden #' . $warden->id;
                $email = 'Hidden';

                try {
                    $username = $this->decryptLongText($warden->encrypted_username, $privateKey);
                    $email = $this->decryptLongText($warden->encrypted_email, $privateKey);
                } catch (Exception $ex) {
                    // Fallback if chunk decryption fails
                }

                $pendingList[] = [
                    'id' => $warden->id,
                    'username' => $username,
                    'email' => $email,
                    'role' => $warden->role,
                    'is_approved' => $warden->is_approved,
                ];
            }

            return response()->json(['pending_wardens' => $pendingList], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch pending wardens: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ADMIN ONLY: Approve a pending warden account
     */
    public function approveWarden($id)
    {
        try {
            $warden = User::where('role', 'warden')->findOrFail($id);
            $warden->is_approved = 1;
            
            // Recalculate Row MAC to match updated is_approved status
            $approvalFlag = '1';
            $dataToAuthenticate = $warden->login_hash . $warden->encrypted_username . $warden->encrypted_email . $warden->password_salt . $warden->hashed_password . $warden->encrypted_two_factor_secret . $warden->role . $approvalFlag;
            $warden->row_mac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            $warden->save();

            return response()->json(['message' => 'Warden account approved successfully.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to approve warden: ' . $e->getMessage()], 500);
        }
    }

    // --- ECC Chunking Helpers --- //

    private function encryptECCLongText(string $text, array $publicKey): string
    {
        $chunks = str_split($text, 24);
        $encryptedChunks = [];
        foreach ($chunks as $chunk) {
            $encryptedChunks[] = $this->eccEngine->encrypt($chunk, $publicKey);
        }
        return json_encode($encryptedChunks);
    }

    private function decryptECCLongText(string $jsonChunks, string $privateKeyHex): string
    {
        $chunks = json_decode($jsonChunks, true);
        $text = '';
        foreach ($chunks as $chunk) {
            $text .= $this->eccEngine->decrypt($chunk, $privateKeyHex);
        }
        return $text;
    }

    // --- RSA Chunking Helper for Users --- //

    private function decryptLongText(string $jsonChunks, array $privateKey): string
    {
        $chunks = json_decode($jsonChunks, true);
        if (!is_array($chunks)) return $jsonChunks;
        $text = '';
        foreach ($chunks as $chunk) {
            $text .= $this->rsaEngine->decrypt($chunk, $privateKey);
        }
        return $text;
    }
}