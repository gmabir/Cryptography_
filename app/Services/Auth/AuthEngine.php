<?php

namespace App\Services\Auth;

use App\Services\Crypto\MACEngine;

class AuthEngine
{
    private MACEngine $macEngine;

    public function __construct(MACEngine $macEngine)
    {
        $this->macEngine = $macEngine;
    }

    /**
     * Generates a random cryptographic salt for password hashing.
     */
    public function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Hashes a password using the custom Salt + HMAC mechanism.
     * 
     * @param string $password The plaintext password
     * @param string $salt The unique user salt
     * @return string The hashed password
     */
    public function hashPassword(string $password, string $salt): string
    {
        // We use the MAC engine to hash the password, using the salt as the HMAC key.
        return $this->macEngine->generateMAC($password, $salt);
    }

    /**
     * Generates an HMAC-based One-Time Password (HOTP) from scratch based on RFC 4226.
     * 
     * @param string $secret The user's unique 2FA secret key
     * @param int $counter The moving factor (e.g., login attempt counter or UNIX timestamp window)
     * @param int $length The desired OTP length (usually 6)
     * @return string The OTP string
     */
    public function generateOTP(string $secret, int $counter, int $length = 6): string
    {
        // 1. Convert the counter to an 8-byte big-endian binary string
        $counterBytes = pack('J', $counter);
        
        // 2. Generate HMAC of the counter using the user's 2FA secret
        $hmacHex = $this->macEngine->generateMAC($counterBytes, $secret);
        $hmacBin = hex2bin($hmacHex);
        
        // 3. Dynamic Truncation (Extract a 4-byte dynamic value from the HMAC)
        // Get the offset from the last nibble of the hash
        $offset = ord($hmacBin[strlen($hmacBin) - 1]) & 0x0f;
        
        // Extract 4 bytes starting at the offset, masking the most significant bit to avoid signed integer issues
        $binary = (
            ((ord($hmacBin[$offset]) & 0x7f) << 24) |
            ((ord($hmacBin[$offset + 1]) & 0xff) << 16) |
            ((ord($hmacBin[$offset + 2]) & 0xff) << 8) |
            (ord($hmacBin[$offset + 3]) & 0xff)
        );
        
        // 4. Calculate the OTP modulo 10^length
        $otp = $binary % pow(10, $length);
        
        // 5. Pad with leading zeros if necessary
        return str_pad((string)$otp, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Verifies if the provided OTP is correct.
     */
    public function verifyOTP(string $providedOtp, string $secret, int $counter, int $length = 6): bool
    {
        $expectedOtp = $this->generateOTP($secret, $counter, $length);
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($expectedOtp, $providedOtp);
    }
}