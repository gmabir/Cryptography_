<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MaintenanceRequest;
use App\Services\Crypto\KeyManager;
use App\Services\Crypto\ECCEngine;
use App\Services\Crypto\MACEngine;
use Exception;

class MaintenanceController extends Controller
{
    private KeyManager $kmm;
    private ECCEngine $eccEngine;
    private MACEngine $macEngine;
    
    private string $rowIntegrityKey = "SecureHostelRowIntegrityKey";

    public function __construct(KeyManager $kmm, ECCEngine $eccEngine, MACEngine $macEngine)
    {
        $this->kmm = $kmm;
        $this->eccEngine = $eccEngine;
        $this->macEngine = $macEngine;
    }

    /**
     * STUDENT ONLY: Create a new maintenance request (Post)
     */
    public function createRequest(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        try {
            $user = Auth::user();
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $publicKey = $eccKeys['public_key'];

            $encTitle = $this->encryptECCLongText($request->title, $publicKey);
            $encDesc = $this->encryptECCLongText($request->description, $publicKey);
            $status = 'open';

            $dataToAuthenticate = $user->id . $status . $encTitle . $encDesc;
            $rowMac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            MaintenanceRequest::create([
                'user_id' => $user->id,
                'status' => $status,
                'encrypted_title' => $encTitle,
                'encrypted_description' => $encDesc,
                'row_mac' => $rowMac
            ]);

            return response()->json(['message' => 'Maintenance request securely submitted.'], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Cryptographic failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * STUDENT ONLY: View personal requests
     */
    public function viewMyRequests()
    {
        try {
            $user = Auth::user();
            $requests = MaintenanceRequest::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
            
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $privateKey = $eccKeys['private_key'];

            $decryptedList = [];
            foreach ($requests as $req) {
                // Verify MAC Integrity
                $dataToAuthenticate = $req->user_id . $req->status . $req->encrypted_title . $req->encrypted_description . ($req->encrypted_response ?? '') . ($req->responded_by ?? '');
                if (!$this->macEngine->verifyMAC($dataToAuthenticate, $this->rowIntegrityKey, $req->row_mac)) {
                    continue; // Skip silently if tampered, or flag it
                }

                $decryptedList[] = [
                    'id' => $req->id,
                    'status' => $req->status,
                    'title' => $this->decryptECCLongText($req->encrypted_title, $privateKey),
                    'description' => $this->decryptECCLongText($req->encrypted_description, $privateKey),
                    'response' => $req->encrypted_response ? $this->decryptECCLongText($req->encrypted_response, $privateKey) : null,
                    'created_at' => $req->created_at
                ];
            }

            return response()->json(['requests' => $decryptedList], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Decryption failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * WARDEN/ADMIN ONLY: View all active maintenance requests
     */
    public function viewAllRequests()
    {
        try {
            $requests = MaintenanceRequest::orderBy('created_at', 'desc')->get();
            
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $privateKey = $eccKeys['private_key'];

            $decryptedList = [];
            foreach ($requests as $req) {
                $dataToAuthenticate = $req->user_id . $req->status . $req->encrypted_title . $req->encrypted_description . ($req->encrypted_response ?? '') . ($req->responded_by ?? '');
                if (!$this->macEngine->verifyMAC($dataToAuthenticate, $this->rowIntegrityKey, $req->row_mac)) {
                    continue; 
                }

                $decryptedList[] = [
                    'id' => $req->id,
                    'student_id' => $req->user_id,
                    'status' => $req->status,
                    'title' => $this->decryptECCLongText($req->encrypted_title, $privateKey),
                    'description' => $this->decryptECCLongText($req->encrypted_description, $privateKey),
                    'response' => $req->encrypted_response ? $this->decryptECCLongText($req->encrypted_response, $privateKey) : null,
                    'created_at' => $req->created_at
                ];
            }

            return response()->json(['requests' => $decryptedList], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Decryption failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * WARDEN/ADMIN ONLY: Respond to a maintenance request
     */
    public function respondToRequest(Request $request, $id)
    {
        $request->validate([
            'response' => 'required|string',
            'status' => 'required|in:open,in_progress,resolved'
        ]);

        try {
            $warden = Auth::user();
            $maintenanceReq = MaintenanceRequest::findOrFail($id);
            
            $eccKeys = $this->kmm->getActiveKey('ecc');
            $publicKey = $eccKeys['public_key'];

            $encResponse = $this->encryptECCLongText($request->response, $publicKey);
            
            $maintenanceReq->status = $request->status;
            $maintenanceReq->encrypted_response = $encResponse;
            $maintenanceReq->responded_by = $warden->id;

            // Recalculate MAC for the updated row
            $dataToAuthenticate = $maintenanceReq->user_id . $maintenanceReq->status . $maintenanceReq->encrypted_title . $maintenanceReq->encrypted_description . $maintenanceReq->encrypted_response . $maintenanceReq->responded_by;
            $maintenanceReq->row_mac = $this->macEngine->generateMAC($dataToAuthenticate, $this->rowIntegrityKey);

            $maintenanceReq->save();

            return response()->json(['message' => 'Response securely encrypted and saved.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Cryptographic failure: ' . $e->getMessage()], 500);
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
}