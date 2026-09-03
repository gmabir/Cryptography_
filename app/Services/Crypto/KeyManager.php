<?php

namespace App\Services\Crypto;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;

class KeyManager
{
    private RSAEngine $rsaEngine;
    private ECCEngine $eccEngine;
    private string $masterKeyPath;

    public function __construct(RSAEngine $rsaEngine, ECCEngine $eccEngine)
    {
        $this->rsaEngine = $rsaEngine;
        $this->eccEngine = $eccEngine;
        $this->masterKeyPath = storage_path('app/master_rsa_key.json');
    }

    /**
     * Step 1: Initialize the Server's Master RSA Key (Run once during setup).
     * This key never goes into the database. It protects the database keys.
     */
    public function initMasterKey(): void
    {
        if (File::exists($this->masterKeyPath)) {
            return; // Master key already exists
        }

        // Generate a 128-bit Master RSA Key
        $masterKeys = $this->rsaEngine->generateKeys(128);
        File::put($this->masterKeyPath, json_encode($masterKeys, JSON_PRETTY_PRINT));
    }

    /**
     * Loads the Master RSA Key Pair from the local filesystem.
     */
    private function getMasterKey(): array
    {
        if (!File::exists($this->masterKeyPath)) {
            throw new Exception("Master Key not found. Please run initMasterKey() first.");
        }
        return json_decode(File::get($this->masterKeyPath), true);
    }

    /**
     * Step 2: Key Rotation. Generates new RSA and ECC working keys for the database.
     * Deactivates old keys, encrypts new private keys with the Master RSA Key, and saves them.
     */
    public function rotateKeys(): void
    {
        $masterKey = $this->getMasterKey();
        $masterPub = $masterKey['public_key'];

        // 1. Deactivate old keys
        DB::table('cryptographic_keys')->update(['is_active' => false]);

        // 2. Generate New RSA Working Keys (128-bit for local speed)
        $newRsa = $this->rsaEngine->generateKeys(128);
        $encryptedRsaPriv = $this->encryptPrivateKey(json_encode($newRsa['private_key']), $masterPub);
        
        $rsaVersion = DB::table('cryptographic_keys')->where('type', 'rsa')->max('version') ?? 0;
        
        DB::table('cryptographic_keys')->insert([
            'type' => 'rsa',
            'public_key' => json_encode($newRsa['public_key']),
            'encrypted_private_key' => json_encode($encryptedRsaPriv),
            'version' => $rsaVersion + 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Generate New ECC Working Keys
        $newEcc = $this->eccEngine->generateKeys();
        $encryptedEccPriv = $this->encryptPrivateKey($newEcc['private_key'], $masterPub);
        
        $eccVersion = DB::table('cryptographic_keys')->where('type', 'ecc')->max('version') ?? 0;

        DB::table('cryptographic_keys')->insert([
            'type' => 'ecc',
            'public_key' => json_encode($newEcc['public_key']),
            'encrypted_private_key' => json_encode($encryptedEccPriv),
            'version' => $eccVersion + 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Retrieves the currently active keys from the database and decrypts the private key 
     * in memory using the Master RSA Key.
     */
    public function getActiveKey(string $type): array
    {
        $record = DB::table('cryptographic_keys')
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if (!$record) {
            throw new Exception("No active keys found for type: {$type}");
        }

        $masterKey = $this->getMasterKey();
        $encryptedChunks = json_decode($record->encrypted_private_key, true);
        
        $decryptedPrivString = $this->decryptPrivateKey($encryptedChunks, $masterKey['private_key']);

        return [
            'public_key' => json_decode($record->public_key, true),
            'private_key' => $type === 'rsa' ? json_decode($decryptedPrivString, true) : $decryptedPrivString,
            'version' => $record->version
        ];
    }

    /**
     * Chunked RSA Encryption: Splitting the hex string into 8-character chunks (32 bits) 
     * so it safely fits inside the math limits of our 128-bit Master RSA key.
     */
    private function encryptPrivateKey(string $privateKeyString, array $masterPub): array
    {
        $chunks = str_split($privateKeyString, 8);
        $encryptedChunks = [];
        foreach ($chunks as $chunk) {
            $encryptedChunks[] = $this->rsaEngine->encrypt($chunk, $masterPub);
        }
        return $encryptedChunks;
    }

    /**
     * Chunked RSA Decryption: Rebuilding the private key string from encrypted chunks.
     */
    private function decryptPrivateKey(array $encryptedChunks, array $masterPriv): string
    {
        $decryptedString = '';
        foreach ($encryptedChunks as $chunk) {
            $decryptedString .= $this->rsaEngine->decrypt($chunk, $masterPriv);
        }
        return $decryptedString;
    }
}