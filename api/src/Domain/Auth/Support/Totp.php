<?php

namespace Domain\Auth\Support;

/**
 * RFC 6238 TOTP (SHA-1, 6 digits, 30 second step) with a one-step clock window.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->encode(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $account, string $issuer = 'Hanab'): string
    {
        $label = rawurlencode($issuer.':'.$account);

        return 'otpauth://totp/'.$label
            .'?secret='.$secret
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits=6&period=30';
    }

    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): ?int
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $counter = intdiv($timestamp ?? time(), 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $counter + $offset;
            if ($step < 0) {
                continue;
            }
            if (hash_equals($this->codeAt($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public function codeAt(string $secret, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('N*', 0).pack('N*', $counter), $this->decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function encode(string $bytes): string
    {
        $bits = '';
        $length = strlen($bytes);
        for ($i = 0; $i < $length; $i++) {
            $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= self::ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';
        $length = strlen($secret);
        for ($i = 0; $i < $length; $i++) {
            $position = strpos(self::ALPHABET, $secret[$i]);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
