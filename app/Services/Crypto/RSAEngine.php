<?php

namespace App\Services\Crypto;

use Exception;

class RSAEngine
{
    /**
     * Generates an RSA Key Pair from scratch using mathematical primes and BCMath.
     * Note: Pure PHP prime generation is CPU intensive. Bit length is reduced to 128 for reasonable local generation times.
     * 
     * @param int $bitLength Length of the prime numbers (128 yields a 256-bit key)
     * @return array
     */
    public function generateKeys(int $bitLength = 128): array
    {
        // 1. Generate two large distinct prime numbers, p and q
        $p = $this->generateLargePrime($bitLength);
        $q = $this->generateLargePrime($bitLength);

        while (bccomp($p, $q) === 0) {
            $q = $this->generateLargePrime($bitLength);
        }

        // 2. Calculate n = p * q (The Modulus)
        $n = bcmul($p, $q);

        // 3. Calculate Euler's Totient Function: phi(n) = (p-1) * (q-1)
        $p_minus_1 = bcsub($p, '1');
        $q_minus_1 = bcsub($q, '1');
        $phi = bcmul($p_minus_1, $q_minus_1);

        // 4. Choose e (Public Exponent)
        $e = '65537';

        // 5. Calculate d (Private Exponent), the modular multiplicative inverse of e mod phi(n)
        $d = $this->modInverse($e, $phi);

        if ($d === '0') {
            throw new Exception("Failed to generate RSA keys: Could not calculate modular inverse.");
        }

        return [
            'public_key'  => ['e' => $e, 'n' => $n],
            'private_key' => ['d' => $d, 'n' => $n]
        ];
    }

    /**
     * Encrypts a plaintext string using the RSA public key.
     * Math: Ciphertext = Message^e (mod n)
     */
    public function encrypt(string $plaintext, array $publicKey): string
    {
        $e = $publicKey['e'];
        $n = $publicKey['n'];
        $m = $this->stringToDec($plaintext);

        if (bccomp($m, $n) >= 0) {
            throw new Exception("Message is too long for this key size.");
        }

        // c = m^e mod n
        $c = bcpowmod($m, $e, $n);
        return $this->decToHex($c);
    }

    /**
     * Decrypts a ciphertext string using the RSA private key.
     * Math: Message = Ciphertext^d (mod n)
     */
    public function decrypt(string $ciphertextHex, array $privateKey): string
    {
        $d = $privateKey['d'];
        $n = $privateKey['n'];
        $c = $this->hexToDec($ciphertextHex);

        // m = c^d mod n
        $m = bcpowmod($c, $d, $n);
        return $this->decToString($m);
    }

    /**
     * Generates a probable prime number.
     */
    private function generateLargePrime(int $bitLength): string
    {
        $byteLength = (int)($bitLength / 8);
        
        while (true) {
            $randomBytes = random_bytes($byteLength);
            $hex = bin2hex($randomBytes);
            $number = $this->hexToDec($hex);
            
            // Ensure it's odd
            if (bcmod($number, '2') === '0') {
                $number = bcadd($number, '1');
            }

            // Miller-Rabin primality test
            if ($this->isProbablePrime($number, 5)) {
                return $number;
            }
        }
    }

    /**
     * Miller-Rabin Primality Test written from scratch for BCMath
     */
    private function isProbablePrime(string $n, int $k): bool
    {
        if (bccomp($n, '2') === 0) return true;
        if (bccomp($n, '2') < 0 || bcmod($n, '2') === '0') return false;

        $d = bcsub($n, '1');
        $s = 0;
        while (bcmod($d, '2') === '0') {
            $d = bcdiv($d, '2', 0);
            $s++;
        }

        for ($i = 0; $i < $k; $i++) {
            $a = (string) mt_rand(2, 9999); // Simplified random base
            $x = bcpowmod($a, $d, $n);

            if (bccomp($x, '1') === 0 || bccomp($x, bcsub($n, '1')) === 0) {
                continue;
            }
            
            $continueLoop = false;
            for ($r = 1; $r < $s; $r++) {
                $x = bcpowmod($x, '2', $n);
                if (bccomp($x, '1') === 0) return false;
                if (bccomp($x, bcsub($n, '1')) === 0) {
                    $continueLoop = true;
                    break;
                }
            }
            
            if ($continueLoop) continue;
            return false;
        }
        return true;
    }

    /**
     * Extended Euclidean Algorithm for Modular Multiplicative Inverse
     */
    private function modInverse(string $a, string $m): string
    {
        $m0 = $m;
        $y = '0';
        $x = '1';

        if (bccomp($m, '1') === 0) return '0';

        while (bccomp($a, '1') > 0) {
            $q = bcdiv($a, $m, 0);
            $t = $m;
            $m = bcmod($a, $m);
            $a = $t;
            $t = $y;
            $y = bcsub($x, bcmul($q, $y));
            $x = $t;
        }

        if (bccomp($x, '0') < 0) {
            $x = bcadd($x, $m0);
        }

        return $x;
    }

    // --- String & Hex & Decimal Converters --- //

    private function stringToDec(string $string): string
    {
        return $this->hexToDec(bin2hex($string));
    }

    private function decToString(string $dec): string
    {
        $hex = $this->decToHex($dec);
        if (strlen($hex) % 2 !== 0) $hex = '0' . $hex;
        return hex2bin($hex);
    }

    private function hexToDec(string $hex): string
    {
        $dec = '0';
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul((string)hexdec($hex[$i - 1]), bcpow('16', (string)($len - $i))));
        }
        return $dec;
    }

    private function decToHex(string $dec): string
    {
        $hex = '';
        while (bccomp($dec, '0') > 0) {
            $rem = bcmod($dec, '16');
            $hex = dechex((int)$rem) . $hex;
            $dec = bcdiv($dec, '16', 0);
        }
        return $hex ?: '0';
    }
}