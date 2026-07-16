<?php

declare(strict_types=1);

namespace App;

/**
 * JWT encode / decode — shared by the WebView "jwt" bind and the test suite.
 *
 * Mirrors the crypto-js (HmacSHA*) behaviour used by the original FlyEnv UI:
 *  - base64url encoding (no padding, "+/"" → "-_")
 *  - HS256 / HS384 / HS512 via hash_hmac
 *  - "none" algorithm (empty signature)
 */
final class JwtHelper
{
    private const ALGOS = ['none', 'HS256', 'HS384', 'HS512'];
    private const HASH_FOR = ['HS256' => 'sha256', 'HS384' => 'sha384', 'HS512' => 'sha512'];

    public static function b64url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function b64urldec(string $s): string
    {
        $s = strtr($s, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) {
            $s .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode($s, true);
        return $raw === false ? '' : $raw;
    }

    public static function allowed(string $alg): bool
    {
        return in_array($alg, self::ALGOS, true);
    }

    /**
     * @return array{header:array, payload:array, token:string}
     */
    public static function encode(string $headerJson, string $payloadJson, string $alg, string $secret): array
    {
        if (!self::allowed($alg)) {
            throw new \InvalidArgumentException('Unsupported algorithm: ' . $alg);
        }
        $h = json_decode($headerJson, true);
        if (!is_array($h)) {
            throw new \InvalidArgumentException('Invalid JSON in header');
        }
        $h['alg'] = $alg;
        if (!isset($h['typ'])) {
            $h['typ'] = 'JWT';
        }
        $p = json_decode($payloadJson, true);
        if (!is_array($p)) {
            throw new \InvalidArgumentException('Invalid JSON in payload');
        }
        $encH = self::b64url(json_encode($h, JSON_UNESCAPED_UNICODE));
        $encP = self::b64url(json_encode($p, JSON_UNESCAPED_UNICODE));
        $sig = $alg === 'none'
            ? ''
            : self::b64url(hash_hmac(self::HASH_FOR[$alg], $encH . '.' . $encP, $secret, true));

        return [
            'header' => $h,
            'payload' => $p,
            'token' => $encH . '.' . $encP . '.' . $sig,
        ];
    }

    /**
     * @return array{header:array, payload:array, valid:bool}
     */
    public static function decode(string $token, string $alg, string $secret): array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('JWT must contain header, payload and signature');
        }
        $h = json_decode(self::b64urldec($parts[0]), true);
        $p = json_decode(self::b64urldec($parts[1]), true);
        if (!is_array($h) || !is_array($p)) {
            throw new \InvalidArgumentException('Invalid JWT');
        }
        $algH = $h['alg'] ?? $alg;
        if (!self::allowed($algH)) {
            throw new \InvalidArgumentException('Unsupported algorithm: ' . $algH);
        }
        $valid = $algH === 'none'
            ? true
            : hash_equals(
                self::b64url(hash_hmac(self::HASH_FOR[$algH], $parts[0] . '.' . $parts[1], $secret, true)),
                $parts[2]
            );

        return ['header' => $h, 'payload' => $p, 'valid' => $valid];
    }
}
