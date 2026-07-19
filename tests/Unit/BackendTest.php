<?php

declare(strict_types=1);

use App\Native\Backend;

/**
 * Tests for Backend pure-PHP computations — hash, base64, jwt, diff, json,
 * QR generation, WiFi QR string building, encrypt/decrypt, and more.
 */
test('hashAll returns correct digests', function () {
    $out = Backend::hashAll('abc', 'Hex');
    expect($out)->toHaveKeys(['MD5', 'SHA1', 'SHA256', 'SHA512']);
    expect($out['MD5'])->toBe(md5('abc'));
    expect($out['SHA256'])->toBe(hash('sha256', 'abc'));
});

test('base64 encode/decode roundtrips', function () {
    expect(Backend::base64Decode(Backend::base64Encode('hello 世界')))->toBe('hello 世界');
    expect(Backend::base64Decode(Backend::base64Encode('')))->toBe('');
});

test('url encode/decode roundtrips', function () {
    $encoded = Backend::urlEncode('test value with spaces & special=chars');
    expect(Backend::urlDecode($encoded))->toBe('test value with spaces & special=chars');
});

test('jwt encode/decode roundtrips for all algorithms', function () {
    foreach (['HS256', 'HS384', 'HS512'] as $alg) {
        $enc = Backend::jwtEncode('{"alg":"' . $alg . '","typ":"JWT"}', '{"sub":"test"}', $alg, 'secret');
        expect($enc)->toHaveKey('token');
        $dec = Backend::jwtDecode($enc['token'], $alg, 'secret');
        expect($dec['valid'])->toBeTrue();
        expect($dec['payload'])->toContain('"sub"');
    }
});

test('jwt decode with wrong secret fails', function () {
    $enc = Backend::jwtEncode('{"alg":"HS256","typ":"JWT"}', '{"sub":"1"}', 'HS256', 'correct');
    $dec = Backend::jwtDecode($enc['token'], 'HS256', 'wrong');
    expect($dec['valid'])->toBeFalse();
});

test('diffLines returns diff string', function () {
    $result = Backend::diffLines("line1\nline2\n", "line1\nline3\n");
    expect($result)->toContain('line2');
    expect($result)->toContain('line3');
});

test('diffHtml returns HTML diff', function () {
    $result = Backend::diffHtml("line1\nline2\n", "line1\nline3\n");
    expect($result)->toContain('line2');
    expect($result)->toContain('line3');
});

test('jsonFormat pretty-prints', function () {
    $formatted = Backend::jsonFormat('{"a":1,"b":[2,3]}');
    expect($formatted)->toContain("\n");
    expect($formatted)->toContain('"a"');
});

test('jsonValidate checks valid/invalid JSON', function () {
    expect(Backend::jsonValidate('{"ok":true}'))->toBe('Valid JSON');
    expect(Backend::jsonValidate('{bad'))->toStartWith('Invalid');
    expect(Backend::jsonValidate(''))->toContain('Invalid');
});

test('jsonConvert to PHP array', function () {
    $result = Backend::jsonConvert('{"a":1}', 'php');
    expect($result)->toContain("'a'");
    expect($result)->toContain('1');
});

test('jsonConvert to JavaScript', function () {
    $result = Backend::jsonConvert('{"a":1}', 'js');
    expect($result)->toContain('a');
});

test('jsonConvert to YAML', function () {
    $result = Backend::jsonConvert('{"a":1}', 'yaml');
    expect($result)->toContain('a:');
});

test('jsonConvert to XML', function () {
    $result = Backend::jsonConvert('{"a":1}', 'xml');
    expect($result)->toContain('<a>');
});

test('jsonConvert to TOML', function () {
    $result = Backend::jsonConvert('{"a":1}', 'toml');
    expect($result)->toContain('a');
});

test('jsonConvert to Go struct', function () {
    $result = Backend::jsonConvert('{"name":"test"}', 'goStruct');
    expect($result)->toContain('json:"name"');
    expect($result)->toContain('string');
});

test('jsonConvert to Kotlin', function () {
    $result = Backend::jsonConvert('{"name":"test"}', 'Kotlin');
    expect($result)->toContain('@SerializedName');
    expect($result)->toContain('val name: String');
});

test('jsonConvert to MySQL', function () {
    $result = Backend::jsonConvert('{"name":"test","age":25}', 'MySQL');
    expect($result)->toContain('VARCHAR');
    expect($result)->toContain('INT');
});

test('jsonConvert to PList', function () {
    $result = Backend::jsonConvert('{"a":1}', 'plist');
    expect($result)->toContain('<plist');
    expect($result)->toContain('<key>a</key>');
});

test('jsonConvert to Go Bson', function () {
    $result = Backend::jsonConvert('{"name":"test"}', 'goBson');
    expect($result)->toContain('bson');
});

test('jsonConvert to JSDoc', function () {
    $result = Backend::jsonConvert('{"name":"test"}', 'JSDoc');
    expect($result)->toContain('@type');
    expect($result)->toContain('name');
});

test('jsonConvert to Rust Serde', function () {
    $result = Backend::jsonConvert('{"name":"test"}', 'rustSerde');
    expect($result)->toContain('serde');
    expect($result)->toContain('pub name: String');
});

test('jsonConvert json-minify', function () {
    $result = Backend::jsonConvert("{\n  \"a\": 1\n}", 'json-minify');
    expect($result)->not->toContain("\n");
});

test('codeRun PHP returns output', function () {
    $result = Backend::codeRun('<?php echo "hello";', 'PHP');
    expect($result)->toContain('hello');
});

test('codeRun Python returns output', function () {
    $result = Backend::codeRun('print("hello")', 'Python');
    expect($result)->toContain('hello');
});

test('codeRun Node returns output', function () {
    $result = Backend::codeRun('console.log("hello")', 'Node.js');
    expect($result)->toContain('hello');
});

test('codeTransform converts JSON to PHP array', function () {
    $result = Backend::codeTransform('{"a":1}', 'PHP Array');
    expect($result)->toContain("'a'");
});

test('codeTransform converts JSON to YAML', function () {
    $result = Backend::codeTransform('{"a":1}', 'YAML');
    expect($result)->toContain('a:');
});

test('codeTransform raw passthrough', function () {
    $result = Backend::codeTransform('plain text output', 'Raw');
    expect($result)->toBe('plain text output');
});

test('qrCodeGenerate returns SVG', function () {
    $svg = Backend::qrCodeGenerate('test data', 'M', 8, '#000000', '#ffffff');
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

test('wifiQrBuildString nopass format', function () {
    $result = Backend::wifiQrBuildString('MyWiFi', '', 'nopass', []);
    expect($result)->toBe('WIFI:S:MyWiFi;;');
});

test('wifiQrBuildString WPA format', function () {
    $result = Backend::wifiQrBuildString('MyWiFi', 'pass123', 'WPA', []);
    expect($result)->toBe('WIFI:S:MyWiFi;T:WPA;P:pass123;;');
});

test('wifiQrBuildString WEP format', function () {
    $result = Backend::wifiQrBuildString('MyWiFi', 'pass123', 'WEP', []);
    expect($result)->toBe('WIFI:S:MyWiFi;T:WEP;P:pass123;;');
});

test('wifiQrBuildString WPA2-EAP with identity', function () {
    $extra = ['eap' => 'PEAP', 'identity' => 'user@domain.com'];
    $result = Backend::wifiQrBuildString('CorpWiFi', 'pass', 'WPA2-EAP', $extra);
    expect($result)->toContain('T:WPA2-EAP');
    expect($result)->toContain('E:PEAP');
    expect($result)->toContain('I:user@domain.com');
});

test('wifiQrBuildString escapes special characters', function () {
    $result = Backend::wifiQrBuildString('WiFi;Name', 'pass:word', 'WPA', []);
    expect($result)->toContain('WiFi\\;Name');
    expect($result)->toContain('pass\\:word');
});

test('wifiQrBuildString hidden flag', function () {
    $result = Backend::wifiQrBuildString('Hidden', 'pass', 'WPA', ['hidden' => true]);
    expect($result)->toContain('H:true');
});

test('wifiQrBuildString anonymous identity', function () {
    $extra = ['eap' => 'TTLS', 'anonymous' => 'anon@test.com'];
    $result = Backend::wifiQrBuildString('Corp', 'pass', 'WPA2-EAP', $extra);
    expect($result)->toContain('A:anon@test.com');
    expect($result)->not->toContain(';I:');
});

test('wifiQrBuildString phase2', function () {
    $extra = ['eap' => 'TTLS', 'phase2' => 'MSCHAPV2'];
    $result = Backend::wifiQrBuildString('Corp', 'pass', 'WPA2-EAP', $extra);
    expect($result)->toContain('PH2:MSCHAPV2');
});

test('encrypt/decrypt roundtrips', function () {
    $encrypted = Backend::encrypt('hello world', 'secretkey');
    $decrypted = Backend::decrypt($encrypted, 'secretkey');
    expect($decrypted)->toBe('hello world');
});

test('encrypt with wrong key fails gracefully', function () {
    $encrypted = Backend::encrypt('hello', 'key1');
    try {
        $decrypted = Backend::decrypt($encrypted, 'key2');
        expect($decrypted)->not->toBe('hello');
    } catch (\Exception $e) {
        expect(true)->toBeTrue();
    }
});

test('bomDetect finds UTF-8 BOM', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bom_');
    file_put_contents($tmp, "\xEF\xBB\xBF" . 'hello');
    $result = Backend::bomDetect($tmp);
    expect($result)->toHaveKey('found');
    expect($result['found'])->toBeTruthy();
    @unlink($tmp);
});

test('bomDetect finds no BOM', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bom_');
    file_put_contents($tmp, 'hello');
    $result = Backend::bomDetect($tmp);
    expect($result['found'])->toBeFalsy();
    @unlink($tmp);
});

test('tokenGenerate produces hex string', function () {
    $token = Backend::tokenGenerate(32);
    expect(strlen($token))->toBeGreaterThan(0);
    expect(preg_match('/^[a-f0-9]+$/', $token))->toBe(1);
});

test('escapeHtml encodes special chars', function () {
    $result = Backend::escapeHtml('<script>alert("xss")</script>');
    expect($result)->toContain('&lt;');
    expect($result)->toContain('&gt;');
    expect($result)->not->toContain('<script>');
});

test('regexTest matches pattern', function () {
    $result = Backend::regexTest('/\d+/', 'abc123def', 'm');
    expect($result)->toContain('123');
});

test('chmodToBits and chmodFromBits roundtrip', function () {
    $bits = Backend::chmodToBits('644');
    expect($bits)->toBe([true, true, false, true, false, false, true, false, false]);
    $r = Backend::chmodFromBits($bits);
    expect($r['octal'])->toBe('644');
});

test('scanBinaries returns non-empty for PHP and Java on macOS', function () {
    $php  = Backend::scanBinaries('PHP');
    $java = Backend::scanBinaries('Java');
    expect($php)->toBeArray();
    expect($java)->toBeArray();
    expect(count($php) + count($java))->toBeGreaterThan(0);
    // All results must be "Label /path" format
    foreach (array_merge($php, $java) as $item) {
        expect($item)->toMatch('/^[\w\-\.]+ \//');
    }
    // No -config binaries
    foreach (array_merge($php, $java) as $item) {
        expect($item)->not->toMatch('/-config$/');
    }
});
