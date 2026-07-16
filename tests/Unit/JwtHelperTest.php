<?php

declare(strict_types=1);

use App\JwtHelper;

/**
 * JWT backend (App\JwtHelper) — the PHP side behind the WebView "jwt" bind.
 *
 * Verifies 1:1 parity with the original FlyEnv crypto-js (HmacSHA*) path:
 *  - base64url token shape, HS256/384/512 signing, "none" algorithm,
 *  - signature verification (valid on correct secret, invalid on wrong secret).
 */

test('encode produces a 3-part base64url token', function () {
    $res = JwtHelper::encode('{"alg":"HS256","typ":"JWT"}', '{"sub":1}', 'HS256', 'secret');
    expect($res['token'])->toMatch('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/');
    expect(explode('.', $res['token']))->toHaveCount(3);
});

test('encode forces alg and typ into the header', function () {
    $res = JwtHelper::encode('{}', '{"sub":1}', 'HS256', 'secret');
    expect($res['header']['alg'])->toBe('HS256');
    expect($res['header']['typ'])->toBe('JWT');
});

test('encode round-trips through decode with valid signature', function () {
    $header = '{"alg":"HS256","typ":"JWT"}';
    $payload = '{"sub":"1234567890","name":"FlyEnv","iat":1516239022}';
    $enc = JwtHelper::encode($header, $payload, 'HS256', 'your-256-bit-secret');

    $dec = JwtHelper::decode($enc['token'], 'HS256', 'your-256-bit-secret');
    expect($dec['valid'])->toBeTrue();
    expect($dec['payload']['name'])->toBe('FlyEnv');
    expect($dec['header']['alg'])->toBe('HS256');
});

test('decode reports invalid signature on wrong secret', function () {
    $enc = JwtHelper::encode('{"alg":"HS256"}', '{"sub":1}', 'HS256', 'correct-secret');
    $dec = JwtHelper::decode($enc['token'], 'HS256', 'wrong-secret');
    expect($dec['valid'])->toBeFalse();
});

test('HS384 and HS512 sign and verify correctly', function () {
    foreach (['HS384', 'HS512'] as $alg) {
        $enc = JwtHelper::encode('{}', '{"sub":1}', $alg, 's');
        $dec = JwtHelper::decode($enc['token'], $alg, 's');
        expect($dec['valid'])->toBeTrue();
    }
});

test('none algorithm yields empty signature and always verifies', function () {
    $enc = JwtHelper::encode('{}', '{"sub":1}', 'none', '');
    expect(explode('.', $enc['token'])[2])->toBe('');
    $dec = JwtHelper::decode($enc['token'], 'none', 'whatever');
    expect($dec['valid'])->toBeTrue();
});

test('unsupported algorithm throws', function () {
    expect(fn() => JwtHelper::encode('{}', '{}', 'HS111', 's'))->toThrow(\InvalidArgumentException::class);
});

test('malformed token throws on decode', function () {
    expect(fn() => JwtHelper::decode('not.a.jwt.token.extra', 'HS256', 's'))->toThrow(\InvalidArgumentException::class);
});
