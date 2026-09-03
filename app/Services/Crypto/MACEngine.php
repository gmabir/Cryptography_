<?php

namespace App\Services\Crypto;

class MACEngine
{
    private string $hashAlgorithm = 'sha256';
    
    // The block size for SHA-256 is 64 bytes
    private int $blockSize = 64; 

    /**
     * Generates an HMAC from scratch using XOR padding.
     * Math formula: HMAC(K, m) = H((K ^ opad) . H((K ^ ipad) . m))
     * 
     * @param string $data The encrypted data string to be authenticated
     * @param string $key  The integrity key (can be the Master Public Key string)
     * @return string      The resulting MAC hex string
     */
    public function generateMAC(string $data, string $key): string
    {
        // 1. Prepare the key: Hash it if it's too long, pad with null bytes if it's too short
        if (strlen($key) > $this->blockSize) {
            $key = hash($this->hashAlgorithm, $key, true);
        }
        $key = str_pad($key, $this->blockSize, chr(0x00));

        // 2. Prepare the inner and outer pads (Standard RFC 2104 constants)
        $ipad = str_repeat(chr(0x36), $this->blockSize);
        $opad = str_repeat(chr(0x5c), $this->blockSize);

        // 3. XOR the key with both pads
        // (In PHP, the bitwise XOR operator ^ works directly on strings byte-by-byte)
        $iKeyPad = $key ^ $ipad;
        $oKeyPad = $key ^ $opad;

        // 4. Perform the inner hash: H( (K ^ ipad) . data )
        $innerHash = hash($this->hashAlgorithm, $iKeyPad . $data, true);

        // 5. Perform the outer hash: H( (K ^ opad) . innerHash )
        $macRaw = hash($this->hashAlgorithm, $oKeyPad . $innerHash, false);

        return $macRaw; // Returns the calculated MAC as a hex string
    }

    /**
     * Verifies if a provided MAC matches the data.
     * Protects against database tampering.
     */
    public function verifyMAC(string $data, string $key, string $providedMAC): bool
    {
        $calculatedMAC = $this->generateMAC($data, $key);
        
        // We use hash_equals to prevent timing attacks when comparing strings
        return hash_equals($calculatedMAC, $providedMAC);
    }
}