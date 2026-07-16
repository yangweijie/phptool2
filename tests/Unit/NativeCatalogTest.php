<?php

declare(strict_types=1);

use App\Native\Backend;
use App\Native\Catalog;

/**
 * Verifies the native (self-drawn) app's catalog integrity and the pure-PHP
 * backend computations it relies on. The GUI/Surface layer is not exercised
 * here (it needs the native libui libs); only the headless logic is tested.
 */
test('catalog tool ids are unique and well-formed', function () {
    $c = new Catalog();
    $ids = array_map(fn ($t) => $t['id'], $c->tools());
    expect(count($ids))->toBe(count(array_unique($ids)));
    foreach ($c->tools() as $t) {
        expect($t)->toHaveKeys(['id', 'cat', 'name']);
    }
});

test('catalog categories match the expected order', function () {
    $c = new Catalog();
    expect($c->categories())->toBe(['Code', 'Development', 'Crypto', 'Converter', 'Web', 'Images']);
});

test('every tool resolves to a panel key', function () {
    $c = new Catalog();
    foreach ($c->tools() as $t) {
        expect($c->panelKey($t['id']))->not->toBeEmpty();
    }
});

test('backend base64 roundtrips', function () {
    $enc = Backend::base64Encode('hello 世界');
    expect(Backend::base64Decode($enc))->toBe('hello 世界');
});

test('backend hashAll returns all algorithms', function () {
    $out = Backend::hashAll('abc', 'Hex');
    expect($out)->toHaveKeys(['MD5', 'SHA1', 'SHA256', 'SHA512']);
    expect($out['MD5'])->toBe(md5('abc'));
});

test('backend chmod bits <-> octal', function () {
    $bits = Backend::chmodToBits('755');
    expect($bits)->toBe([true, true, true, true, false, true, true, false, true]);
    $r = Backend::chmodFromBits($bits);
    expect($r)->toBe(['octal' => '755', 'symbolic' => 'rwxr-xr-x']);
});

test('backend json format pretty prints', function () {
    expect(Backend::jsonFormat('{"a":1}'))->toContain("\n");
    expect(Backend::jsonValidate('{"a":1}'))->toBe('Valid JSON');
    expect(Backend::jsonValidate('{bad'))->toStartWith('Invalid');
});

test('backend jwt encode/decode roundtrips and validates', function () {
    $enc = Backend::jwtEncode('{"alg":"HS256","typ":"JWT"}', '{"sub":"1"}', 'HS256', 'secret');
    expect($enc)->toHaveKey('token');
    $dec = Backend::jwtDecode($enc['token'], 'HS256', 'secret');
    expect($dec['valid'])->toBeTrue();
    expect($dec['payload'])->toContain('"sub"');
});

test('backend fileInfo reads a temp file', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'fly_');
    file_put_contents($tmp, 'hello');
    $info = Backend::fileInfo($tmp);
    expect($info['type'])->toBe('file');
    expect($info['size'])->toBe(5);
    expect($info)->toHaveKey('md5');
    @unlink($tmp);
});
