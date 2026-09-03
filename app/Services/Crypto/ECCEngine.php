<?php

namespace App\Services\Crypto;

use Exception;

class ECCEngine
{
    // Secp256k1 Curve Parameters
    private string $p;
    private string $a;
    private string $b;
    private array $G;
    private string $n;

    public function __construct()
    {
        // Hexadecimal parameters for the secp256k1 standard curve
        $this->p = $this->hexToDec('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F');
        $this->a = '0';
        $this->b = '7';
        $this->G = [
            'x' => $this->hexToDec('79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798'),
            'y' => $this->hexToDec('483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8')
        ];
        $this->n = $this->hexToDec('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
    }

    /**
     * Generates an ECC Key Pair (Private Key 'd' and Public Key 'Q').
     */
    public function generateKeys(): array
    {
        $d = $this->randomInteger($this->n);
        if (bccomp($d, '0') === 0) {
            $d = '1';
        }

        // Q = d * G
        $Q = $this->scalarMult($d, $this->G);

        return [
            'private_key' => $this->decToHex($d),
            'public_key'  => [
                'x' => $this->decToHex($Q['x']),
                'y' => $this->decToHex($Q['y'])
            ]
        ];
    }

    /**
     * Encrypts data using Pure Asymmetric EC-ElGamal.
     * C1 = k * G
     * S = k * Q (Shared Secret)
     * C2 = (Message * S.x) mod p
     */
    public function encrypt(string $plaintext, array $publicKey): array
    {
        $m = $this->stringToDec($plaintext);
        if (bccomp($m, $this->p) >= 0) {
            throw new Exception("Message exceeds curve field size.");
        }

        $Q = [
            'x' => $this->hexToDec($publicKey['x']),
            'y' => $this->hexToDec($publicKey['y'])
        ];

        // Generate random ephemeral key 'k'
        $k = $this->randomInteger($this->n);
        if (bccomp($k, '0') === 0) $k = '1';

        $C1 = $this->scalarMult($k, $this->G);
        $S  = $this->scalarMult($k, $Q);

        // C2 = (m * S.x) mod p
        $C2 = $this->mod(bcmul($m, $S['x']), $this->p);

        return [
            'C1' => [
                'x' => $this->decToHex($C1['x']),
                'y' => $this->decToHex($C1['y'])
            ],
            'C2' => $this->decToHex($C2)
        ];
    }

    /**
     * Decrypts EC-ElGamal ciphertext.
     * S = d * C1
     * Message = (C2 * S.x^-1) mod p
     */
    public function decrypt(array $ciphertext, string $privateKeyHex): string
    {
        $d = $this->hexToDec($privateKeyHex);
        $C1 = [
            'x' => $this->hexToDec($ciphertext['C1']['x']),
            'y' => $this->hexToDec($ciphertext['C1']['y'])
        ];
        $C2 = $this->hexToDec($ciphertext['C2']);

        // Recover shared secret S = d * C1
        $S = $this->scalarMult($d, $C1);

        // m = (C2 * modular_inverse(S.x)) mod p
        $Sx_inv = $this->modInverse($S['x'], $this->p);
        $m = $this->mod(bcmul($C2, $Sx_inv), $this->p);

        return $this->decToString($m);
    }

    // --- Elliptic Curve Mathematics --- //

    private function pointAdd(array $P, array $Q): array
    {
        if (empty($P)) return $Q;
        if (empty($Q)) return $P;

        if (bccomp($P['x'], $Q['x']) === 0) {
            if (bccomp($P['y'], $Q['y']) !== 0) return []; // Point at infinity
            return $this->pointDouble($P);
        }

        // lambda = (Q.y - P.y) * (Q.x - P.x)^-1 mod p
        $dy = $this->mod(bcsub($Q['y'], $P['y']), $this->p);
        $dx = $this->mod(bcsub($Q['x'], $P['x']), $this->p);
        $dx_inv = $this->modInverse($dx, $this->p);
        $lambda = $this->mod(bcmul($dy, $dx_inv), $this->p);

        // x_r = lambda^2 - P.x - Q.x mod p
        $lambdaSq = bcmul($lambda, $lambda);
        $x_r = $this->mod(bcsub(bcsub($lambdaSq, $P['x']), $Q['x']), $this->p);

        // y_r = lambda * (P.x - x_r) - P.y mod p
        $y_r = $this->mod(bcsub(bcmul($lambda, bcsub($P['x'], $x_r)), $P['y']), $this->p);

        return ['x' => $x_r, 'y' => $y_r];
    }

    private function pointDouble(array $P): array
    {
        if (empty($P)) return [];

        // lambda = (3 * P.x^2 + a) * (2 * P.y)^-1 mod p
        $xSq = bcmul($P['x'], $P['x']);
        $num = $this->mod(bcadd(bcmul('3', $xSq), $this->a), $this->p);
        $den = $this->mod(bcmul('2', $P['y']), $this->p);
        $den_inv = $this->modInverse($den, $this->p);
        $lambda = $this->mod(bcmul($num, $den_inv), $this->p);

        // x_r = lambda^2 - 2 * P.x mod p
        $lambdaSq = bcmul($lambda, $lambda);
        $x_r = $this->mod(bcsub($lambdaSq, bcmul('2', $P['x'])), $this->p);

        // y_r = lambda * (P.x - x_r) - P.y mod p
        $y_r = $this->mod(bcsub(bcmul($lambda, bcsub($P['x'], $x_r)), $P['y']), $this->p);

        return ['x' => $x_r, 'y' => $y_r];
    }

    private function scalarMult(string $k, array $P): array
    {
        $R = []; // Point at infinity
        $Q = $P;

        // Convert k to binary
        $kBin = '';
        $tempK = $k;
        while (bccomp($tempK, '0') > 0) {
            $kBin = bcmod($tempK, '2') . $kBin;
            $tempK = bcdiv($tempK, '2', 0);
        }

        // Double-and-Add Algorithm
        $len = strlen($kBin);
        for ($i = $len - 1; $i >= 0; $i--) {
            if ($kBin[$i] === '1') {
                $R = $this->pointAdd($R, $Q);
            }
            $Q = $this->pointDouble($Q);
        }
        return $R;
    }

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

    private function mod(string $num, string $mod): string
    {
        $res = bcmod($num, $mod);
        if (bccomp($res, '0') < 0) {
            $res = bcadd($res, $mod);
        }
        return $res;
    }

    // --- Core Helpers --- //

    private function randomInteger(string $max): string
    {
        $bytes = random_bytes(32);
        $hex = bin2hex($bytes);
        $dec = $this->hexToDec($hex);
        return $this->mod($dec, $max);
    }

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
