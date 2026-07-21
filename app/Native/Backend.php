<?php

declare(strict_types=1);

namespace App\Native;

use App\JwtHelper;
use App\MarkdownHelper;

/**
 * Pure-PHP backend computations for the native panels.
 *
 * These mirror the logic previously embedded in the webview PHP bindings
 * (flyenv-web.php) but take plain PHP arguments and return plain PHP values —
 * no async bridge, since the Surface app runs synchronously.
 */
final class Backend
{
    // ── Hash ────────────────────────────────────────────────────────────────
    /** @return array<string,string> */
    public static function hashAll(string $text, string $digest): array
    {
        $algos = [
            'MD5' => 'md5',
            'SHA1' => 'sha1',
            'SHA256' => 'sha256',
            'SHA224' => 'sha224',
            'SHA512' => 'sha512',
            'SHA384' => 'sha384',
            'SHA3' => 'sha3-512',
            'RIPEMD160' => 'ripemd160',
        ];
        $out = [];
        foreach ($algos as $name => $phpAlgo) {
            if (!function_exists('hash') || !in_array($phpAlgo, hash_algos(), true)) {
                $out[$name] = '(unsupported)';
                continue;
            }
            $raw = hash($phpAlgo, $text, true);
            $out[$name] = match ($digest) {
                'Bin' => implode('', array_map(
                    static fn (string $b): string => str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT),
                    str_split($raw),
                )),
                'Base64' => base64_encode($raw),
                'Base64url' => rtrim(strtr(base64_encode($raw), '+/', '-_'), '='),
                default => bin2hex($raw),
            };
        }
        return $out;
    }

    // ── Base64 ──────────────────────────────────────────────────────────────
    public static function base64Encode(string $text): string
    {
        return base64_encode($text);
    }

    public static function base64Decode(string $text): string
    {
        $dec = base64_decode($text, true);
        return $dec === false ? '(invalid base64)' : $dec;
    }

    // ── URL ─────────────────────────────────────────────────────────────────
    public static function urlEncode(string $text): string
    {
        return urlencode($text);
    }

    public static function urlDecode(string $text): string
    {
        return urldecode($text);
    }

    public static function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ── JSON ────────────────────────────────────────────────────────────────
    public static function jsonFormat(string $text): string
    {
        $dec = json_decode($text, true);
        if ($dec === null && $text !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
            return 'JSON Error: ' . json_last_error_msg();
        }
        return json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Convert decoded JSON to one of 16 output formats. */
    public static function jsonConvert(string $text, string $format, string $sort = 'none'): string
    {
        $dec = json_decode($text, true);
        // Fallback: try PHP array syntax → JSON
        if ($dec === null && $text !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
            $json = self::phpToJson($text);
            if ($json !== null) {
                $dec = json_decode($json, true);
            }
        }
        if ($dec === null && $text !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
            return 'JSON Error: ' . json_last_error_msg();
        }
        if ($sort !== 'none') {
            $dec = self::jsonSortRecursive($dec, $sort === 'asc');
        }
        return match ($format) {
            'json-minify' => json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'json' => json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'php' => self::jsonToPhp($dec),
            'js' => self::jsonToJs($dec),
            'ts' => self::jsonToStruct($dec, 'ts'),
            'yaml' => self::jsonToYaml($dec),
            'xml' => self::jsonToXml($dec),
            'toml' => self::jsonToToml($dec),
            'goStruct' => self::jsonToStruct($dec, 'go'),
            'goBson' => self::jsonToStruct($dec, 'goBson'),
            'rustSerde' => self::jsonToStruct($dec, 'rust'),
            'Java' => self::jsonToStruct($dec, 'java'),
            'Kotlin' => self::jsonToStruct($dec, 'kotlin'),
            'MySQL' => self::jsonToStruct($dec, 'mysql'),
            'JSDoc' => self::jsonToStruct($dec, 'jsdoc'),
            'plist' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n<plist version=\"1.0\">\n" . self::jsonToPlist($dec) . "</plist>\n",
            default => json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }

    /**
     * Transform code-run output to one of the 17 Code Playground formats.
     *
     * Works like jsonConvert but accepts display names (e.g. "JSON Minify")
     * and falls back to raw passthrough for non-JSON output.
     */
    public static function codeTransform(string $output, string $format): string
    {
        $key = match ($format) {
            'JSON' => 'json',
            'JSON Minify' => 'json-minify',
            'PHP Array' => 'php',
            'JavaScript' => 'js',
            'TypeScript' => 'ts',
            'YAML' => 'yaml',
            'XML' => 'xml',
            'PList' => 'plist',
            'TOML' => 'toml',
            'Go Struct' => 'goStruct',
            'Go Bson' => 'goBson',
            'Rust Serde' => 'rustSerde',
            'Java' => 'Java',
            'Kotlin' => 'Kotlin',
            'MySQL' => 'MySQL',
            'JSDoc' => 'JSDoc',
            default => 'raw',
        };
        if ($key === 'raw') return $output;
        // Try to parse as JSON first; if it fails, return raw.
        $dec = json_decode($output, true);
        if ($dec === null && $output !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
            return $output;
        }
        return self::jsonConvert($output, $key);
    }

    private static function jsonSortRecursive(mixed $v, bool $asc): mixed
    {
        if (is_array($v)) {
            $isIndexed = array_is_list($v);
            $sorted = [];
            foreach ($v as $k => $val) {
                $sorted[$k] = self::jsonSortRecursive($val, $asc);
            }
            if ($isIndexed) {
                // Sort indexed arrays by their string representation
                usort($sorted, fn($a, $b) => $asc ? strcmp((string)json_encode($a), (string)json_encode($b)) : -strcmp((string)json_encode($a), (string)json_encode($b)));
            } else {
                $asc ? ksort($sorted) : krsort($sorted);
            }
            return $sorted;
        }
        return $v;
    }

    /** Convert PHP array syntax to JSON string. Returns null on failure. */
    public static function phpToJson(string $input): ?string
    {
        $t = $input;
        // Strip PHP tags and leading return/semicolon
        $t = preg_replace('/<\?php\s*/', '', $t);
        $t = preg_replace('/<\?/', '', $t);
        $t = preg_replace('/^return\s+/i', '', $t);
        $t = preg_replace('/;\s*$/', '', $t);
        $t = trim($t);
        if ($t === '') return null;

        // Fast path: if it's already valid JSON, just return it
        $decoded = json_decode($t, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Try eval (safe: this is a developer tool for the user's own code)
        try {
            $decoded = @eval('return ' . $t . ';');
        } catch (\Throwable $e) {
            $decoded = null;
        }

        if ($decoded !== null && !is_string($decoded)) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Regex fallback for simple cases: key=>value without complex nesting
        $t = str_replace('=>', ':', $t);
        $t = str_replace(['[', ']'], ['{', '}'], $t);
        $t = preg_replace('/([{,]\s*)([A-Za-z_]\w*)\s*:/', '$1"$2":', $t);
        // Replace single quotes with double quotes (careful with apostrophes)
        $t = str_replace("'", '"', $t);
        $decoded = json_decode($t, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    private static function jsonToPhp(mixed $data, int $depth = 0): string
    {
        if ($data === null) return 'null';
        if (is_bool($data)) return $data ? 'true' : 'false';
        if (is_scalar($data)) return var_export($data, true);
        $indent = str_repeat('    ', $depth);
        if (array_is_list($data)) {
            $items = [];
            foreach ($data as $v) {
                $items[] = $indent . '    ' . self::jsonToPhp($v, $depth + 1);
            }
            return "[\n" . implode(",\n", $items) . "\n{$indent}]";
        }
        $items = [];
        foreach ($data as $k => $v) {
            $items[] = $indent . '    ' . var_export((string)$k, true) . ' => ' . self::jsonToPhp($v, $depth + 1);
        }
        return "[\n" . implode(",\n", $items) . "\n{$indent}]";
    }

    private static function jsonToJs(mixed $data, int $depth = 0): string
    {
        if ($data === null) return 'null';
        if (is_bool($data)) return $data ? 'true' : 'false';
        if (is_scalar($data)) return json_encode($data, JSON_UNESCAPED_UNICODE);
        $indent = str_repeat('  ', $depth);
        $inner = str_repeat('  ', $depth + 1);
        if (array_is_list($data)) {
            $items = [];
            foreach ($data as $v) {
                $items[] = $inner . self::jsonToJs($v, $depth + 1);
            }
            return "[\n" . implode(",\n", $items) . "\n{$indent}]";
        }
        $items = [];
        foreach ($data as $k => $v) {
            $items[] = $inner . json_encode((string)$k, JSON_UNESCAPED_UNICODE) . ': ' . self::jsonToJs($v, $depth + 1);
        }
        return "{\n" . implode(",\n", $items) . "\n{$indent}}";
    }

    private static function jsonToYaml(mixed $data, int $depth = 0): string
    {
        if ($data === null) return "~\n";
        if (is_bool($data)) return ($data ? 'true' : 'false') . "\n";
        if (is_scalar($data)) return json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        $indent = str_repeat('  ', $depth);
        $out = '';
        if (array_is_list($data)) {
            foreach ($data as $v) {
                if (is_array($v)) {
                    $out .= "{$indent}- " . ltrim(self::jsonToYaml($v, $depth + 1));
                } else {
                    $out .= "{$indent}- " . (is_scalar($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : '') . "\n";
                }
            }
        } else {
            foreach ($data as $k => $v) {
                $key = (string)$k;
                if (is_array($v)) {
                    $out .= "{$indent}{$key}:\n" . self::jsonToYaml($v, $depth + 1);
                } else {
                    $out .= "{$indent}{$key}: " . (is_scalar($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : 'null') . "\n";
                }
            }
        }
        return $out;
    }

    private static function jsonToXml(mixed $data, string $parentKey = 'root', int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $out = '';
        if (is_scalar($data) || $data === null) {
            return "{$indent}<{$parentKey}>" . htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8') . "</{$parentKey}>\n";
        }
        if (array_is_list($data)) {
            foreach ($data as $v) {
                $out .= self::jsonToXml($v, 'item', $depth);
            }
            return $out;
        }
        if ($depth === 0) {
            $out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        }
        $children = '';
        foreach ($data as $k => $v) {
            $children .= self::jsonToXml($v, (string)$k, $depth + 1);
        }
        if (empty(trim($children))) {
            $out .= "{$indent}<{$parentKey}/>\n";
        } else {
            $out .= "{$indent}<{$parentKey}>\n{$children}{$indent}</{$parentKey}>\n";
        }
        return $out;
    }

    private static function jsonToToml(mixed $data, int $depth = 0): string
    {
        if (is_scalar($data) || $data === null) {
            return self::tomlValue($data) . "\n";
        }
        $indent = str_repeat('  ', $depth);
        $out = '';
        if (array_is_list($data)) {
            $items = [];
            foreach ($data as $v) {
                $items[] = $indent . self::tomlValue($v);
            }
            return "[\n" . implode(",\n", $items) . "\n{$indent}]\n";
        }
        foreach ($data as $k => $v) {
            $key = (string)$k;
            if (is_array($v)) {
                $out .= "{$indent}[{$key}]\n" . self::jsonToToml($v, $depth + 1);
            } else {
                $out .= "{$indent}{$key} = " . self::tomlValue($v) . "\n";
            }
        }
        return $out;
    }

    private static function tomlValue(mixed $v): string
    {
        if ($v === null) return '';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_string($v)) {
            return str_contains($v, "\n") ? '"""' . $v . '"""' : '"' . addslashes($v) . '"';
        }
        return (string)$v;
    }

    private static function jsonToPlist(mixed $data): string
    {
        if ($data === null) return '';
        if (is_bool($data)) return $data ? '<true/>' : '<false/>';
        if (is_int($data)) return '<integer>' . $data . '</integer>';
        if (is_float($data)) return '<real>' . $data . '</real>';
        if (is_string($data)) return '<string>' . htmlspecialchars($data, ENT_XML1, 'UTF-8') . '</string>';
        if (array_is_list($data)) {
            $items = '';
            foreach ($data as $v) {
                $items .= self::jsonToPlist($v);
            }
            return "<array>\n{$items}</array>\n";
        }
        if (is_array($data)) {
            $items = '';
            foreach ($data as $k => $v) {
                $items .= '<key>' . htmlspecialchars((string)$k, ENT_XML1, 'UTF-8') . "</key>\n" . self::jsonToPlist($v);
            }
            return "<dict>\n{$items}</dict>\n";
        }
        return '';
    }

    private static function jsonToStruct(mixed $data, string $lang): string
    {
        if (!is_array($data) || array_is_list($data)) {
            return '// Root must be an object (key-value pairs) for ' . $lang . ' struct generation';
        }
        $out = '';
        foreach ($data as $key => $val) {
            $type = self::phpTypeToLangType($val, $lang);
            $name = self::camelCase($key, $lang);
            $out .= match ($lang) {
                'go' => "{$name} {$type} `json:\"{$key}\"`\n",
                'goBson' => "{$name} {$type} `bson:\"{$key}\"`\n",
                'rust' => "#[serde(rename = \"{$key}\")]\npub {$name}: {$type},\n",
                'java' => "private {$type} {$name};\n",
                'kotlin' => "@SerializedName(\"{$key}\")\nval {$name}: {$type},\n",
                'ts' => "{$name}: {$type};\n",
                'jsdoc' => "/** @type {{$type}} */ {$name};\n",
                'mysql' => "`{$name}` {$type} DEFAULT NULL,\n",
                default => "{$name}: {$type}\n",
            };
        }
        return $out;
    }

    private static function phpTypeToLangType(mixed $v, string $lang): string
    {
        $t = gettype($v);
        return match ($lang) {
            'go', 'goBson' => match ($t) {
                'string' => 'string', 'integer' => 'int', 'double' => 'float64', 'boolean' => 'bool', 'NULL' => 'interface{}',
                'array' => array_is_list($v) ? '[]interface{}' : 'map[string]interface{}',
                default => 'interface{}',
            },
            'rust' => match ($t) {
                'string' => 'String', 'integer' => 'i64', 'double' => 'f64', 'boolean' => 'bool', 'NULL' => 'Option<Value>',
                default => 'Value',
            },
            'java' => match ($t) {
                'string' => 'String', 'integer' => 'int', 'double' => 'double', 'boolean' => 'boolean', 'NULL' => 'Object',
                'array' => 'List<Object>', default => 'Object',
            },
            'kotlin' => match ($t) {
                'string' => 'String', 'integer' => 'Int', 'double' => 'Double', 'boolean' => 'Boolean', 'NULL' => 'Any?',
                'array' => 'List<Any>', default => 'Any',
            },
            'mysql' => match ($t) {
                'string' => 'VARCHAR(255)', 'integer' => 'INT', 'double' => 'DECIMAL(10,2)', 'boolean' => 'TINYINT(1)', 'NULL' => 'TEXT', 'array' => 'JSON',
                default => 'TEXT',
            },
            'ts', 'jsdoc' => match ($t) {
                'string' => 'string', 'integer' => 'number', 'double' => 'number', 'boolean' => 'boolean', 'NULL' => 'null',
                'array' => 'any[]', default => 'any',
            },
            default => 'string',
        };
    }

    private static function camelCase(string $key, string $lang): string
    {
        $parts = preg_split('/[-_\\s.]+/', $key);
        $name = $parts[0] ?? $key;
        if ($lang === 'go' || $lang === 'goBson') {
            // Go uses PascalCase
            foreach (array_slice($parts, 1) as $p) {
                $name .= ucfirst($p);
            }
        } else {
            foreach (array_slice($parts, 1) as $p) {
                $name .= ucfirst($p);
            }
        }
        return $name;
    }

    public static function fileRead(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return "Cannot read file: {$path}";
        }
        return file_get_contents($path);
    }

    public static function fileSave(string $path, string $content): string
    {
        $result = file_put_contents($path, $content);
        return $result !== false ? "Saved {$result} bytes" : 'Save failed';
    }

    public static function jsonValidate(string $text): string
    {
        $dec = json_decode($text, true);
        if ($dec === null && $text !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
            return 'Invalid: ' . json_last_error_msg();
        }
        return 'Valid JSON';
    }

    // ── Timestamp ─────────────────────────────────────────────────────────────
    public static function tsNow(): array
    {
        $now = time();
        return [
            'unix' => (string) $now,
            'utc' => gmdate('Y-m-d H:i:s', $now),
            'local' => date('Y-m-d H:i:s', $now),
            'iso' => date('c', $now),
        ];
    }

    public static function tsToDate(string $unix): array
    {
        $u = (int) $unix;
        if ($u <= 0) {
            return ['error' => 'Enter a positive unix timestamp'];
        }
        return [
            'utc' => gmdate('Y-m-d H:i:s', $u),
            'local' => date('Y-m-d H:i:s', $u),
            'iso' => date('c', $u),
        ];
    }

    public static function dateToTs(string $date): array
    {
        $u = strtotime($date);
        if ($u === false) {
            return ['error' => 'Unparseable date string'];
        }
        return [
            'unix' => (string) $u,
            'utc' => gmdate('Y-m-d H:i:s', $u),
            'local' => date('Y-m-d H:i:s', $u),
        ];
    }

    // ── Chmod ──────────────────────────────────────────────────────────────
    /** @param list<bool> $bits 9 bits: owner r,w,x / group r,w,x / other r,w,x */
    public static function chmodFromBits(array $bits): array
    {
        $triads = [array_slice($bits, 0, 3), array_slice($bits, 3, 3), array_slice($bits, 6, 3)];
        $octal = '';
        $symbolic = '';
        $weights = [4, 2, 1];
        $letters = ['r', 'w', 'x'];
        foreach ($triads as $triad) {
            $v = 0;
            foreach ($triad as $i => $on) {
                if ($on) {
                    $v += $weights[$i];
                }
            }
            $octal .= (string) $v;
            foreach ($triad as $i => $on) {
                $symbolic .= $on ? $letters[$i] : '-';
            }
        }
        return ['octal' => $octal, 'symbolic' => $symbolic];
    }

    /** @return list<bool> 9 bits parsed from an octal string like "755". */
    public static function chmodToBits(string $octal): array
    {
        $bits = array_fill(0, 9, false);
        if (!preg_match('/^[0-7]{1,4}$/', $octal)) {
            return $bits;
        }
        $padded = str_pad($octal, 3, '0', STR_PAD_LEFT);
        $padded = substr($padded, -3);
        $weights = [4, 2, 1];
        for ($t = 0; $t < 3; $t++) {
            $v = (int) $padded[$t];
            for ($i = 0; $i < 3; $i++) {
                $bits[$t * 3 + $i] = ($v & $weights[$i]) !== 0;
            }
        }
        return $bits;
    }

    // ── Diff (simple line comparison) ────────────────────────────────────────
    public static function diffLines(string $a, string $b): string
    {
        $la = preg_split('/\r?\n/', $a) ?: [];
        $lb = preg_split('/\r?\n/', $b) ?: [];
        $setB = array_flip($lb);
        $setA = array_flip($la);
        $lines = [];
        foreach ($la as $line) {
            $lines[] = in_array($line, $lb, true) ? '  ' . $line : '- ' . $line;
        }
        foreach ($lb as $line) {
            if (!isset($setA[$line])) {
                $lines[] = '+ ' . $line;
            }
        }
        return implode("\n", $lines);
    }

    // ── File Info ──────────────────────────────────────────────────────────
    private static function formatBytes(int $b): string
    {
        if ($b >= 1073741824) return round($b / 1073741824, 1) . ' GB';
        if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
        if ($b >= 1024) return round($b / 1024, 1) . ' KB';
        return $b . ' B';
    }

    private static function birthTime(string $p): ?int
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os === 'DAR') {
            $out = @shell_exec('stat -f %B ' . escapeshellarg($p));
            if (is_numeric(trim((string) $out))) {
                return (int) trim((string) $out);
            }
        } elseif ($os === 'LIN') {
            $out = @shell_exec('stat -c %W ' . escapeshellarg($p));
            $w = (int) trim((string) $out);
            if ($w > 0) {
                return $w;
            }
        }
        return null;
    }

    /** @return array<string,mixed> */
    public static function fileInfo(string $path): array
    {
        if (!file_exists($path)) {
            return ['error' => 'Not found'];
        }
        $isDir = is_dir($path);
        $sz = $isDir ? 0 : (@filesize($path) ?: 0);
        $i = [
            'path' => realpath($path) ?: $path,
            'type' => $isDir ? 'dir' : 'file',
            'size' => $sz,
            'size_str' => self::formatBytes((int) $sz),
            'mtime' => null, 'mtime_str' => null,
            'ctime' => null, 'ctime_str' => null,
            'atime' => null, 'atime_str' => null,
            'btime' => null, 'btime_str' => null,
        ];
        if ($isDir) {
            $i['mtime'] = filemtime($path) * 1000;
            $i['mtime_str'] = date('c', filemtime($path));
            $i['ctime'] = filectime($path) * 1000;
            $i['ctime_str'] = date('c', filectime($path));
        } else {
            $i['atime'] = fileatime($path) * 1000;
            $i['atime_str'] = date('c', fileatime($path));
            $i['mtime'] = filemtime($path) * 1000;
            $i['mtime_str'] = date('c', filemtime($path));
            $i['ctime'] = filectime($path) * 1000;
            $i['ctime_str'] = date('c', filectime($path));
            $bt = self::birthTime($path);
            if ($bt !== null) {
                $i['btime'] = $bt * 1000;
                $i['btime_str'] = date('c', $bt);
            }
            $limit = 200 * 1024 * 1024;
            if ($sz < $limit) {
                $i['md5'] = md5_file($path);
                $i['sha1'] = sha1_file($path);
                $i['sha256'] = hash_file('sha256', $path);
                $i['sha512'] = base64_encode((string) hash_file('sha512', $path, true));
            } else {
                $i['md5'] = $i['sha1'] = $i['sha256'] = $i['sha512'] = '(file too large)';
            }
        }
        return $i;
    }

    // ── JWT (delegates to App\JwtHelper) ─────────────────────────────────────
    /** @return array{token:string} */
    public static function jwtEncode(string $header, string $payload, string $alg, string $secret): array
    {
        return JwtHelper::encode($header, $payload, $alg, $secret);
    }

    /** @return array{header:string,payload:string,valid:bool} */
    public static function jwtDecode(string $token, string $alg, string $secret): array
    {
        $res = JwtHelper::decode($token, $alg, $secret);
        return [
            'header' => json_encode($res['header'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'payload' => json_encode($res['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'valid' => $res['valid'],
        ];
    }

    // ── Markdown (delegates to App\MarkdownHelper) ───────────────────────────
    public static function markdown(string $md): string
    {
        return MarkdownHelper::render($md);
    }

    // ── HTML Entities ────────────────────────────────────────────────────────
    public static function escapeHtmlDecode(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ── Clipboard ──────────────────────────────────────────────────────────
    /**
     * Best-effort copy to the system clipboard. Silently no-ops when no
     * clipboard helper is available (e.g. headless CI).
     */
    public static function copyText(string $text): void
    {
        if ($text === '') {
            return;
        }
        $os = PHP_OS_FAMILY;
        $cmd = match ($os) {
            'Darwin' => 'pbcopy',
            'Linux'  => 'xclip -selection clipboard',
            default  => '',
        };
        if ($cmd === '') {
            return;
        }
        $proc = @proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($proc === false) {
            return;
        }
        fwrite($pipes[0], $text);
        fclose($pipes[0]);
        proc_close($proc);
    }

    // ── Regex ────────────────────────────────────────────────────────────────
    public static function regexTest(string $pattern, string $subject, string $flags = ''): string
    {
        if (trim($pattern) === '') return 'Enter a pattern';
        if (trim($subject) === '') return 'Enter subject text';
        $count = preg_match_all($pattern, $subject, $matches);
        if ($count === false) {
            return 'Regex Error: ' . preg_last_error_msg();
        }
        if ($count === 0) {
            return 'No matches found';
        }
        $out = "Matches: {$count}\n\n";
        foreach ($matches as $i => $group) {
            $out .= "Group {$i}:\n";
            foreach ($group as $m) {
                $out .= "  \"{$m}\"\n";
            }
        }
        return $out;
    }

    // ── Token Generator ──────────────────────────────────────────────────────
    public static function tokenGenerate(int $length = 64): string
    {
        return bin2hex(random_bytes($length));
    }

    // ── Encryption (AES-256-CBC) ─────────────────────────────────────────────
    public static function encrypt(string $text, string $key): string
    {
        $cipher = 'aes-256-cbc';
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $encrypted = openssl_encrypt($text, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            return 'Encryption failed';
        }
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $text, string $key): string
    {
        $cipher = 'aes-256-cbc';
        $data = base64_decode($text, true);
        if ($data === false) {
            return 'Invalid base64';
        }
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = mb_substr($data, 0, $ivLen, '8bit');
        $ciphertext = mb_substr($data, $ivLen, null, '8bit');
        $dec = @openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return $dec === false ? 'Decryption failed (wrong key or corrupted data)' : $dec;
    }

    // ── RSA Key Generator ────────────────────────────────────────────────────
    public static function rsaKeyGenerate(int $bits = 2048): array
    {
        $res = openssl_pkey_new(['private_key_bits' => $bits, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($res === false) {
            return ['error' => 'Key generation failed'];
        }
        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res)['key'];
        return ['private' => $privKey, 'public' => $pubKey];
    }

    // ── QR Code (uses chillerlan/php-qrcode) ─────────────────────────────────
    public static function qrCodeGenerate(string $data, string $ecc = 'M', int $scale = 8, string $fg = '#000000', string $bg = '#ffffff'): string
    {
        if (!class_exists(\chillerlan\QRCode\QRCode::class)) {
            return '';
        }
        try {
            $options = new \chillerlan\QRCode\QROptions();
            $options->outputBase64 = false;
            $options->outputInterface = \chillerlan\QRCode\Output\QRMarkupSVG::class;
            $options->eccLevel = $ecc;
            $options->scale = $scale;
            $options->fgColor = $fg;
            $options->bgColor = $bg;
            return (new \chillerlan\QRCode\QRCode($options))->render($data);
        } catch (\Throwable $e) {
            return "QR Error: {$e->getMessage()}";
        }
    }

    public static function qrCodeSavePng(string $data, string $path, string $ecc = 'M', int $scale = 10, string $fg = '#000000', string $bg = '#ffffff'): string
    {
        if (!class_exists(\chillerlan\QRCode\QRCode::class)) {
            return 'QR library not available';
        }
        try {
            $options = new \chillerlan\QRCode\QROptions();
            $options->outputInterface = \chillerlan\QRCode\Output\QRImagePNG::class;
            $options->eccLevel = $ecc;
            $options->scale = $scale;
            $options->fgColor = $fg;
            $options->bgColor = $bg;
            $options->imageBase64 = false;
            $png = (new \chillerlan\QRCode\QRCode($options))->render($data);
            file_put_contents($path, $png);
            return "Saved QR PNG to {$path} (" . strlen($png) . ' bytes)';
        } catch (\Throwable $e) {
            return "PNG Error: {$e->getMessage()}";
        }
    }

    /**
     * Build a WIFI QR string per the WPA QR spec.
     *
     * @param array{eap?:string,identity?:string,anonymous?:string,phase2?:string,hidden?:bool} $extra
     */
    public static function wifiQrBuildString(
        string $ssid,
        string $password,
        string $encryption = 'WPA',
        array  $extra = [],
    ): string {
        $esc = static fn(string $v): string => preg_replace('/([\\;:,"])/', '\\\\$1', $v);

        if ($encryption === 'nopass') {
            return 'WIFI:S:' . $esc($ssid) . ';;';
        }

        $parts = [
            'S:' . $esc($ssid),
            'T:' . $encryption,
            'P:' . $esc($password),
        ];

        if ($encryption === 'WPA2-EAP') {
            $parts[] = 'E:' . ($extra['eap'] ?? 'PEAP');
            if (!empty($extra['phase2'])) {
                $parts[] = 'PH2:' . $extra['phase2'];
            }
            if (!empty($extra['anonymous'])) {
                $parts[] = 'A:' . $esc($extra['anonymous']);
            } elseif (!empty($extra['identity'])) {
                $parts[] = 'I:' . $esc($extra['identity']);
            }
        }

        if (!empty($extra['hidden'])) {
            $parts[] = 'H:true';
        }

        return 'WIFI:' . implode(';', $parts) . ';;';
    }

    public static function wifiQrGenerate(
        string $ssid,
        string $password,
        string $encryption = 'WPA',
        string $ecc = 'M',
        int    $scale = 8,
        string $fg = '#000000',
        string $bg = '#ffffff',
        array  $extra = [],
    ): string {
        $wifiText = self::wifiQrBuildString($ssid, $password, $encryption, $extra);
        return self::qrCodeGenerate($wifiText, $ecc, $scale, $fg, $bg);
    }

    public static function wifiQrSavePng(
        string $ssid,
        string $password,
        string $encryption,
        string $path,
        string $ecc = 'M',
        int    $scale = 10,
        string $fg = '#000000',
        string $bg = '#ffffff',
        array  $extra = [],
    ): string {
        $wifiText = self::wifiQrBuildString($ssid, $password, $encryption, $extra);
        return self::qrCodeSavePng($wifiText, $path, $ecc, $scale, $fg, $bg);
    }

    // ── BOM Detection / Clean ────────────────────────────────────────────────
    public static function bomDetect(string $path): array
    {
        if (!is_file($path)) {
            return ['error' => 'File not found'];
        }
        $content = file_get_contents($path);
        $bom = substr($content, 0, 3);
        if ($bom === "\xEF\xBB\xBF") {
            return ['found' => true, 'size' => 3, 'mime' => mime_content_type($path)];
        }
        $bom2 = substr($content, 0, 2);
        if ($bom2 === "\xFE\xFF" || $bom2 === "\xFF\xFE") {
            return ['found' => true, 'size' => 2, 'mime' => mime_content_type($path)];
        }
        return ['found' => false, 'mime' => mime_content_type($path)];
    }

    public static function bomClean(string $path): array
    {
        if (!is_file($path)) {
            return ['error' => 'File not found'];
        }
        $content = file_get_contents($path);
        $bom = substr($content, 0, 3);
        if ($bom === "\xEF\xBB\xBF") {
            $cleaned = substr($content, 3);
            file_put_contents($path, $cleaned);
            return ['cleaned' => true, 'removed' => 'UTF-8 BOM'];
        }
        $bom2 = substr($content, 0, 2);
        if ($bom2 === "\xFE\xFF" || $bom2 === "\xFF\xFE") {
            $cleaned = substr($content, 2);
            file_put_contents($path, $cleaned);
            return ['cleaned' => true, 'removed' => $bom2 === "\xFE\xFF" ? 'UTF-16 BE BOM' : 'UTF-16 LE BOM'];
        }
        return ['cleaned' => false, 'msg' => 'No BOM found'];
    }

    // ── Cron Parser ──────────────────────────────────────────────────────────
    /** @return list<string> */
    public static function cronParse(string $expr): array
    {
        $parts = preg_split('/\s+/', trim($expr));
        if (count($parts) !== 5) {
            return ['Enter 5 cron fields (min hour dom month dow)'];
        }
        $names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $next = [];
        $now = time();
        for ($i = 0; $i < 10; $i++) {
            $t = $now + $i * 60;
            $min = (int)date('i', $t); $hour = (int)date('G', $t);
            $dom = (int)date('j', $t); $mon = (int)date('n', $t);
            $dow = (int)date('w', $t);
            if (self::cronFieldMatch($parts[0], $min) && self::cronFieldMatch($parts[1], $hour)
                && self::cronFieldMatch($parts[2], $dom) && self::cronFieldMatch($parts[3], $mon)
                && self::cronFieldMatch($parts[4], $dow)) {
                $next[] = date('Y-m-d H:i D', $t) . ' — ' . ($names[$dow] ?? '?');
                if (count($next) >= 5) break;
            }
        }
        return $next ?: ['No future matches within 10 minutes'];
    }

    private static function cronFieldMatch(string $field, int $value): bool
    {
        if ($field === '*') return true;
        if (str_contains($field, ',')) {
            foreach (explode(',', $field) as $f) { if (self::cronFieldMatch($f, $value)) return true; }
            return false;
        }
        if (str_contains($field, '/')) {
            [$base, $step] = explode('/', $field, 2);
            $start = $base === '*' ? 0 : (int)$base;
            return ($value - $start) >= 0 && ($value - $start) % (int)$step === 0;
        }
        if (str_contains($field, '-')) {
            [$lo, $hi] = explode('-', $field, 2);
            return $value >= (int)$lo && $value <= (int)$hi;
        }
        return (int)$field === $value;
    }

    /** Month aliases: jan=1..dec=12 */
    private const CRON_MONTH_ALIAS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
        'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    /** Day-of-week aliases: sun=0..sat=6 */
    private const CRON_DAY_ALIAS = [
        'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3,
        'thu' => 4, 'fri' => 5, 'sat' => 6,
    ];

    /** Field configs: name => ['min'=>, 'max'=>, 'aliases'=>] */
    private const CRON_FIELDS = [
        'second'     => ['min' => 0, 'max' => 59],
        'minute'     => ['min' => 0, 'max' => 59],
        'hour'       => ['min' => 0, 'max' => 23],
        'dayOfMonth' => ['min' => 1, 'max' => 31],
        'month'      => ['min' => 1, 'max' => 12, 'aliases' => self::CRON_MONTH_ALIAS],
        'dayOfWeek'  => ['min' => 0, 'max' => 7, 'aliases' => self::CRON_DAY_ALIAS],
    ];

    private const CRON_MODE_FIELDS = [
        'linux'   => ['minute', 'hour', 'dayOfMonth', 'month', 'dayOfWeek'],
        'seconds' => ['second', 'minute', 'hour', 'dayOfMonth', 'month', 'dayOfWeek'],
    ];

    private const CRON_FIELD_HINT = [
        'auto'    => '自动检测 5 或 6 字段',
        'linux'   => '分 时 日 月 周',
        'seconds' => '秒 分 时 日 月 周',
    ];

    /** Detect mode from field count: 'linux' (5) or 'seconds' (6). */
    public static function cronDetectMode(string $expr): string
    {
        $n = count(preg_split('/\s+/', trim($expr)));
        return $n === 6 ? 'seconds' : 'linux';
    }

    /** Return human-readable field hint for a mode. */
    public static function cronFieldHints(string $expr): string
    {
        $mode = self::cronDetectMode($expr);
        return self::CRON_FIELD_HINT[$mode] ?? '';
    }

    /**
     * Build a cron expression from generation parameters.
     *
     * @param array{mode?:string, genMode:string, intMin?:int, minute?:int, hour?:int, dow?:int, dom?:int} $p
     */
    public static function cronBuildExpr(array $p): string
    {
        $outMode = ($p['mode'] ?? 'auto') === 'auto' ? 'linux' : ($p['mode'] ?? 'linux');
        $intMin  = max(1, min(59, $p['intMin'] ?? 5));
        $minute  = max(0, min(59, $p['minute'] ?? 0));
        $hour    = max(0, min(23, $p['hour'] ?? 0));
        $dow     = max(0, min(6, $p['dow'] ?? 0));
        $dom     = max(1, min(31, $p['dom'] ?? 1));
        $withPrefix = static fn (string $e): string => $outMode === 'linux' ? $e : "0 {$e}";

        return match ($p['genMode']) {
            'everyMinute'   => $outMode === 'linux' ? '* * * * *' : '0 * * * * *',
            'everyNMinutes' => $withPrefix("*/{$intMin} * * * *"),
            'hourly'        => $withPrefix("{$minute} * * * *"),
            'daily'         => $withPrefix("{$minute} {$hour} * * *"),
            'weekly'        => $withPrefix("{$minute} {$hour} * * {$dow}"),
            'monthly'       => $withPrefix("{$minute} {$hour} {$dom} * *"),
            default         => '* * * * *',
        };
    }

    /**
     * Full cron parser: returns next N run times within 1 year.
     *
     * @return array{detectedMode:string, fieldHint:string, runs:list<string>}
     * @throws \InvalidArgumentException
     */
    public static function cronGetNextRuns(string $expr, int $count = 10, string $mode = 'auto'): array
    {
        $fields = preg_split('/\s+/', trim($expr));
        $resolved = match ($mode) {
            'linux'   => count($fields) === 5 ? 'linux' : throw new \InvalidArgumentException('Linux 模式需要 5 个字段'),
            'seconds' => count($fields) === 6 ? 'seconds' : throw new \InvalidArgumentException('秒级模式需要 6 个字段'),
            default   => count($fields) === 6 ? 'seconds' : (count($fields) === 5 ? 'linux' : throw new \InvalidArgumentException('Cron 表达式需要 5 或 6 个字段')),
        };

        $names = self::CRON_MODE_FIELDS[$resolved];
        $sched = [];
        foreach ($names as $i => $nm) {
            $sched[$nm] = self::cronParseFieldFull($fields[$i], $nm, self::CRON_FIELDS[$nm]);
        }
        // Default second=0 when linux mode
        if (!isset($sched['second'])) {
            $sched['second'] = ['values' => [0], 'wildcard' => false];
        }

        $scanBySecond = $resolved !== 'linux'
            || !($sched['second']['values'] === [0] && !$sched['second']['wildcard']);

        // Build match function
        $matchDay = static function (\DateTimeImmutable $d, array $s): bool {
            $dom = in_array((int)$d->format('j'), $s['dayOfMonth']['values'], true);
            $dow = in_array((int)$d->format('w'), $s['dayOfWeek']['values'], true);
            if ($s['dayOfMonth']['wildcard'] && $s['dayOfWeek']['wildcard']) return true;
            if ($s['dayOfMonth']['wildcard']) return $dow;
            if ($s['dayOfWeek']['wildcard']) return $dom;
            return $dom || $dow;
        };

        $isMatch = static function (\DateTimeImmutable $d, array $s) use ($matchDay): bool {
            return in_array((int)$d->format('s'), $s['second']['values'], true)
                && in_array((int)$d->format('i'), $s['minute']['values'], true)
                && in_array((int)$d->format('G'), $s['hour']['values'], true)
                && $matchDay($d, $s)
                && in_array((int)$d->format('n'), $s['month']['values'], true);
        };

        $cursor = new \DateTimeImmutable('now');
        $cursor = $cursor->setMicrosecond(0);
        if ($scanBySecond) {
            $cursor = $cursor->modify('+1 second');
        } else {
            $cursor = $cursor->setTime((int) $cursor->format('H'), (int) $cursor->format('i'), 0)->modify('+1 minute');
        }

        $limit = $scanBySecond ? 366 * 24 * 60 * 60 : 366 * 24 * 60;
        $runs  = [];
        for ($i = 0; $i < $limit && count($runs) < $count; $i++) {
            if ($isMatch($cursor, $sched)) {
                $runs[] = $cursor->format('Y-m-d H:i:s D');
            }
            $cursor = $scanBySecond ? $cursor->modify('+1 second') : $cursor->modify('+1 minute');
        }

        if ($runs === []) {
            throw new \InvalidArgumentException('未来一年内没有匹配的执行时间');
        }

        return [
            'detectedMode' => $resolved,
            'fieldHint'    => self::CRON_FIELD_HINT[$resolved] ?? '',
            'runs'         => $runs,
        ];
    }

    /**
     * Parse a single cron field into a set of matching values.
     *
     * @param array{min:int, max:int, aliases?:array<string,int>} $cfg
     * @return array{values:list<int>, wildcard:bool}
     * @throws \InvalidArgumentException
     */
    private static function cronParseFieldFull(string $field, string $name, array $cfg): array
    {
        $values   = [];
        $wildcard = false;
        $parts    = explode(',', $field);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                throw new \InvalidArgumentException("{$name} 包含空值");
            }
            $sp    = explode('/', $part, 2);
            $range = $sp[0];
            $step  = isset($sp[1]) ? (int)$sp[1] : 1;
            if ($step < 1) {
                throw new \InvalidArgumentException("{$name} 步长必须大于 0");
            }

            if ($range === '*') {
                $wildcard = true;
                self::cronAddRange($values, $cfg['min'], $cfg['max'], $step, $name);
                continue;
            }

            $rv = explode('-', $range, 2);
            if (count($rv) === 1) {
                $values[] = self::cronNormalize($rv[0], $name, $cfg);
                continue;
            }
            if (count($rv) === 2) {
                self::cronAddRange($values, self::cronNormalize($rv[0], $name, $cfg), self::cronNormalize($rv[1], $name, $cfg), $step, $name);
                continue;
            }
            throw new \InvalidArgumentException("{$name} 包含无效的范围");
        }

        return ['values' => array_values(array_unique($values)), 'wildcard' => $wildcard];
    }

    /** Normalize a value (alias or int), validate range. */
    private static function cronNormalize(string $value, string $name, array $cfg): int
    {
        $lv      = strtolower($value);
        $aliases = $cfg['aliases'] ?? [];
        $mapped  = array_key_exists($lv, $aliases) ? $aliases[$lv] : (int)$value;

        if (!is_int($mapped) && !ctype_digit((string)$mapped)) {
            throw new \InvalidArgumentException("{$value} 对 {$name} 无效");
        }
        $mapped = (int)$mapped;
        if ($name === 'dayOfWeek' && $mapped === 7) $mapped = 0;
        if ($mapped < $cfg['min'] || $mapped > $cfg['max']) {
            throw new \InvalidArgumentException("{$value} 超出 {$name} 范围 ({$cfg['min']}-{$cfg['max']})");
        }
        return $mapped;
    }

    /** Add a range of values to the array. */
    private static function cronAddRange(array &$values, int $start, int $end, int $step, string $name): void
    {
        for ($v = $start; $v <= $end; $v += $step) {
            $values[] = $name === 'dayOfWeek' && $v === 7 ? 0 : $v;
        }
    }

    // ── URL Timing (curl) ────────────────────────────────────────────────────
    public static function requestTime(string $url): string
    {
        $ch = curl_init($url);
        if ($ch === false) return 'Failed to initialise curl';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ]);
        $start = microtime(true);
        curl_exec($ch);
        $total = microtime(true) - $start;
        $info = curl_getinfo($ch);
        curl_close($ch);
        return sprintf(
            "URL: %s\nHTTP: %d\nTotal: %.3fs\nDNS: %.3fs\nConnect: %.3fs\nSSL: %.3fs\nStart Transfer: %.3fs\nRedirects: %d\nSize: %s",
            $url, $info['http_code'] ?? 0, $total,
            $info['namelookup_time'] ?? 0, $info['connect_time'] ?? 0,
            $info['appconnect_time'] ?? 0, $info['starttransfer_time'] ?? 0,
            $info['redirect_count'] ?? 0,
            ($info['size_download'] ?? 0) > 0 ? round($info['size_download'] / 1024, 1) . ' KB' : '-'
        );
    }

    // ── SSL Certificate Generator (self-signed) ─────────────────────────────
    public static function sslMake(array $dn): array
    {
        $dn = array_merge(['commonName' => 'localhost', 'organizationName' => 'Self-Signed'], $dn);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($key === false) return ['error' => 'Key generation failed'];
        $csr = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
        if ($csr === false) return ['error' => 'CSR generation failed'];
        $cert = openssl_csr_sign($csr, null, $key, 365, ['digest_alg' => 'sha256']);
        if ($cert === false) return ['error' => 'Cert signing failed'];
        openssl_x509_export($cert, $certOut);
        openssl_pkey_export($key, $keyOut);
        return ['cert' => $certOut, 'key' => $keyOut];
    }

    // ── Code Playground (sandboxed PHP exec) ─────────────────────────────────
    public static function codePlay(string $code): string
    {
        if (trim($code) === '') return '';
        $code = preg_replace('/^<\?(php\s*)?/i', '', $code);
        ob_start();
        try {
            $result = eval($code);
            $output = ob_get_clean();
            if ($output === false) $output = '';
            $return = $result !== null ? "\n\n→ Return: " . var_export($result, true) : '';
            return ($output !== '' ? $output : '(no output)') . $return;
        } catch (\Throwable $e) {
            ob_end_clean();
            return "Error: {$e->getMessage()}";
        }
    }

    /**
     * Scan the system for installed binaries of the given language.
     *
     * Returns a list of "label /path" strings (e.g. "Java-25.0.2 /opt/homebrew/opt/openjdk@25/bin/java").
     * Returns ["—"] if nothing is found.
     *
     * @return list<string>
     */
    public static function scanBinaries(string $lang): array
    {
        $results = [];

        $searchDirs = [
            '/opt/homebrew/bin',
            '/opt/homebrew/opt',
            '/usr/local/bin',
            '/usr/local/opt',
            '/usr/bin',
            '/usr/sbin',
            '/bin',
        ];

        $langMap = match ($lang) {
            'PHP'     => ['php'],
            'Python'  => ['python3', 'python'],
            'Node.js' => ['node'],
            'Go'      => ['go'],
            'Rust'    => ['rustc'],
            'Java'    => ['java'],
            default   => [],
        };

        foreach ($langMap as $binaryName) {
            // 1) `which` the default binary
            $which = shell_exec("which " . escapeshellarg($binaryName) . " 2>/dev/null");
            if ($which !== null && trim($which) !== '') {
                $path = trim($which);
                // Skip config/auxiliary binaries
                $baseName = basename($path);
                if (str_ends_with($baseName, '-config') || str_ends_with($baseName, '-dbg')) continue;
                $label = self::tryVersionLabel($binaryName, $path);
                $results[] = $label . " " . $path;
            }

            // 2) Glob common versioned installs in brew/opt directories
            if ($lang === 'Java') {
                // macOS java_home
                $javaHome = shell_exec('/usr/libexec/java_home -F 2>/dev/null');
                if ($javaHome !== null && trim($javaHome) !== '') {
                    $hm = trim($javaHome);
                    $jpath = $hm . '/bin/java';
                    if (is_executable($jpath)) {
                        $jl = self::tryVersionLabel('java', $jpath);
                        $results[] = $jl . " " . $jpath;
                    }
                }
                // List all java versions
                $allJava = shell_exec('/usr/libexec/java_home -V 2>&1');
                if ($allJava !== null) {
                    foreach (explode("\n", $allJava) as $line) {
                        if (str_contains($line, '/Contents/Home')) {
                            // Extract path: "   /path/Contents/Home (Java 25.0.2)"
                            $parts = explode('(', $line);
                            $jhm = trim($parts[0]);
                            $ver = count($parts) > 1 ? trim(str_replace('Java ', '', str_replace(')', '', $parts[1]))) : '';
                            $jpath2 = $jhm . '/bin/java';
                            if (is_executable($jpath2)) {
                                $jl2 = $ver !== '' ? 'Java-' . $ver : 'Java';
                                $results[] = $jl2 . " " . $jpath2;
                            }
                        }
                    }
                }
                // Also scan /opt/homebrew/opt/openjdk*
                foreach (glob('/opt/homebrew/opt/openjdk*') as $dir) {
                    $jpath3 = $dir . '/bin/java';
                    if (is_executable($jpath3)) {
                        $ver2 = basename($dir); // e.g. openjdk@25
                        $jl3 = $ver2 !== '' ? 'Java-' . ltrim($ver2, 'openjdk@') : 'Java';
                        $results[] = $jl3 . " " . $jpath3;
                    }
                }
            } else {
                // For non-Java, glob versioned installs
                foreach ($searchDirs as $d) {
                    if (!is_dir($d)) continue;
                    $pattern = $d . '/' . $binaryName . '[0-9]*';
                    foreach (glob($pattern) as $full) {
                        // Skip config/auxiliary binaries
                        $baseName = basename($full);
                        if (str_ends_with($baseName, '-config') || str_ends_with($baseName, '-dbg') || str_ends_with($baseName, '-valgrind')) continue;
                        if (is_executable($full)) {
                            $lbl = self::tryVersionLabel($binaryName, $full);
                            $results[] = $lbl . " " . $full;
                        }
                    }
                }
            }
        }

        // Deduplicate by path
        $seen = [];
        $unique = [];
        foreach ($results as $r) {
            $path = substr($r, strrpos($r, ' ') + 1);
            if (!isset($seen[$path])) {
                $seen[$path] = true;
                $unique[] = $r;
            }
        }

        return $unique;
    }

    /** Try to extract a version label from binary --version output. */
    private static function tryVersionLabel(string $name, string $path): string
    {
        $verOut = shell_exec(escapeshellcmd($path) . ' --version 2>/dev/null');
        if ($verOut === null) return $name;
        $line = explode("\n", $verOut)[0] ?? '';
        // Extract version number like "25.0.2", "3.12.1", "v22.1.0"
        if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $line, $m)) {
            $v = $m[1];
            return match ($name) {
                'java' => 'Java-' . $v,
                'php'  => 'PHP-' . $v,
                'python3' => 'Python-' . $v,
                'python' => 'Python-' . $v,
                'node' => 'Node-' . $v,
                'go' => 'Go-' . $v,
                'rustc' => 'Rust-' . $v,
                default => $name,
            };
        }
        return $name;
    }

    public static function codeRun(string $code, string $lang, string $binary = ''): string
    {
        if (trim($code) === '') return '';
        return match ($lang) {
            'PHP' => self::codePlay($code),
            'Python' => self::codeRunShell($code, $binary ?: 'python3'),
            'Node.js' => self::codeRunShell($code, $binary ?: 'node'),
            'Go' => self::codeRunShell('package main
import "fmt"
func main() {
' . $code . '
}', $binary ?: 'go run'),
            'Rust' => self::codeRunFn($code, $binary ?: 'rustc'),
            'Java' => self::codeRunFn($code, $binary ?: 'java'),
            default => self::codePlay($code),
        };
    }

    private static function codeRunShell(string $code, string $bin): string
    {
        $tmp = sys_get_temp_dir() . '/fly_code_' . time() . '.tmp';
        file_put_contents($tmp, $code);
        $cmd = escapeshellcmd($bin) . ' ' . escapeshellarg($tmp) . ' 2>&1';
        $output = shell_exec($cmd);
        unlink($tmp);
        return $output !== null ? $output : '(no output)';
    }

    private static function codeRunFn(string $code, string $bin): string
    {
        $tmp = sys_get_temp_dir() . '/fly_code_' . time() . '.tmp';
        file_put_contents($tmp, $code);
        $cmd = escapeshellcmd($bin) . ' ' . escapeshellarg($tmp) . ' 2>&1';
        $output = shell_exec($cmd);
        // For Rust/Java, the binary might compile then need to run the compiled result
        unlink($tmp);
        return $output !== null ? $output : '(no output)';
    }

    // ── Markdown HTML (wrapped in a styled preview page) ──────────────────
    public static function markdownPreview(string $md): string
    {
        $body = self::markdown($md);
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;padding:4px 8px;margin:0;max-width:100%;line-height:1.6;color:#222;overflow-x:hidden}'
            . 'h1{font-size:1.7em;margin:.4em 0 .2em}h2{font-size:1.4em;margin:.4em 0 .2em}'
            . 'code,pre{background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:.9em}'
            . 'pre{padding:12px;overflow-x:auto;margin:.4em 0}'
            . 'blockquote{border-left:3px solid #ccc;margin:0 0 0 4px;padding:2px 0 2px 12px;color:#666}'
            . 'img{max-width:100%}table{border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px 10px}'
            . 'p{margin:.4em 0}'
            . '</style></head><body>' . $body . '</body></html>';
    }

    // ── Diff HTML (styled) ──────────────────────────────────────────────────
    public static function diffHtml(string $a, string $b): string
    {
        $text = self::diffLines($a, $b);
        $esc = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        // Colour the diff markers
        $colored = preg_replace('/^(\+.*)$/m', '<span style="color:#1a7f37">$1</span>', $esc);
        $colored = preg_replace('/^(\-.*)$/m', '<span style="color:#cf222e">$1</span>', $colored);
        $colored = preg_replace('/^(@@.*@@)$/m', '<span style="color:#0550ae;font-weight:bold">$1</span>', $colored);
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>body{font-family:Menlo,Consolas,monospace;font-size:13px;padding:12px;line-height:1.5;white-space:pre-wrap}'
            . '</style></head><body>' . $colored . '</body></html>';
    }

    // ── Port Kill ────────────────────────────────────────────────────────────
    public static function portKill(int $port): string
    {
        $output = [];
        $cmd = sprintf('lsof -ti :%d 2>/dev/null', $port);
        exec($cmd, $output, $code);
        if (empty($output)) return "No process found on port {$port}";
        $killed = 0;
        foreach ($output as $pid) {
            $pid = trim($pid);
            if ($pid === '' || !ctype_digit($pid)) continue;
            exec("kill -9 {$pid} 2>/dev/null");
            $killed++;
        }
        return "Killed {$killed} process(es) on port {$port}";
    }

    // ── Process Kill ─────────────────────────────────────────────────────────
    public static function processKill(string $name): string
    {
        $output = [];
        $name = escapeshellarg($name);
        exec("ps aux | grep -i {$name} | grep -v grep 2>/dev/null", $output);
        if (empty($output)) return "No process found matching \"{$name}\"";
        $lines = [];
        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line), 9);
            if (count($parts) >= 2 && ctype_digit($parts[1])) {
                $pid = (int)$parts[1];
                exec("kill -9 {$pid} 2>/dev/null");
                $lines[] = "Killed PID {$pid}: " . ($parts[8] ?? '');
            }
        }
        return implode("\n", $lines) ? : "No killable process found";
    }

    // ── Image Compress ───────────────────────────────────────────────────────
    public static function imageCompress(string $path, int $quality = 75): array
    {
        if (!is_file($path)) return ['error' => 'File not found'];
        $info = getimagesize($path);
        if ($info === false) return ['error' => 'Not a valid image'];
        $mime = $info['mime'];
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path) ?: @imagecreatefromjpeg($path),
            default => false,
        };
        if ($img === false) return ['error' => "Unsupported format: {$mime}"];
        $origSize = filesize($path);
        $tmp = sys_get_temp_dir() . '/fly_compress_' . time() . '.png';
        imagepng($img, $tmp, (int)(9 * $quality / 100));
        imagedestroy($img);
        $newSize = filesize($tmp);
        $ratio = $origSize > 0 ? round((1 - $newSize / $origSize) * 100, 1) : 0;
        return [
            'saved' => true,
            'output' => $tmp,
            'original' => $origSize,
            'compressed' => $newSize,
            'ratio' => $ratio . '%',
        ];
    }

    /**
     * Advanced image compress with options: quality, max width, format,
     * brightness/contrast/blur, watermark text, texture preset.
     *
     * @return array{output:string,original:int,compressed:int,ratio:string,error?:string}
     */
    public static function imageCompressAdvanced(
        string $path,
        int $quality = 75,
        int $maxWidth = 0,
        int $maxHeight = 0,
        string $format = 'jpg',
        int $brightness = 0,
        int $contrast = 0,
        int $saturation = 0,
        int $sharpen = 0,
        int $noise = 0,
        int $blur = 0,
        string $watermarkText = '',
        string $watermarkPos = 'center',
        int $watermarkOpacity = 50,
        int $wmFontSize = 12,
        int $wmRotation = 0,
        string $texture = 'none',
    ): array {
        if (!is_file($path)) return ['error' => 'File not found', 'output' => '', 'original' => 0, 'compressed' => 0, 'ratio' => '0%'];
        $info = getimagesize($path);
        if ($info === false) return ['error' => 'Not a valid image', 'output' => '', 'original' => 0, 'compressed' => 0, 'ratio' => '0%'];
        $mime = $info['mime'];
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path) ?: @imagecreatefromjpeg($path),
            default => false,
        };
        if ($img === false) return ['error' => "Unsupported format: {$mime}", 'output' => '', 'original' => 0, 'compressed' => 0, 'ratio' => '0%'];

        // Resize to max width and/or height while preserving aspect ratio
        $origW = imagesx($img);
        $origH = imagesy($img);
        $scale = 1.0;
        if ($maxWidth > 0 && $origW > $maxWidth) {
            $scale = min($scale, $maxWidth / $origW);
        }
        if ($maxHeight > 0 && $origH > $maxHeight) {
            $scale = min($scale, $maxHeight / $origH);
        }
        if ($scale < 1.0) {
            $newW = (int) round($origW * $scale);
            $newH = (int) round($origH * $scale);
            $resized = imagecreatetruecolor($newW, $newH);
            if (in_array(strtolower($format), ['png', 'webp', 'gif'], true)) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
            }
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($img);
            $img = $resized;
        }

        // Effects with GD filters
        if ($brightness !== 0)  imagefilter($img, IMG_FILTER_BRIGHTNESS, $brightness);
        if ($contrast !== 0)    imagefilter($img, IMG_FILTER_CONTRAST, $contrast * -1);
        if ($saturation !== 0)  imagefilter($img, IMG_FILTER_COLORIZE, $saturation, $saturation, $saturation);
        if ($sharpen > 0) {
            $sharpenMatrix = [[-1, -1, -1], [-1, 24, -1], [-1, -1, -1]];
            $divisor = 16; $offset = $sharpen;
            imageconvolution($img, $sharpenMatrix, $divisor, $offset);
        }
        if ($noise > 0) {
            $randMax = $noise * 8;
            for ($i = 0; $i < $noise * 100; $i++) {
                $x = rand(0, imagesx($img) - 1);
                $y = rand(0, imagesy($img) - 1);
                $c = imagecolorat($img, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $dr = rand(-$randMax, $randMax);
                $dg = rand(-$randMax, $randMax);
                $db = rand(-$randMax, $randMax);
                $nc = imagecolorallocate($img, max(0, min(255, $r + $dr)), max(0, min(255, $g + $dg)), max(0, min(255, $b + $db)));
                imagesetpixel($img, $x, $y, $nc);
            }
        }
        if ($blur > 0) {
            for ($i = 0; $i < $blur; $i++) imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
        }

        // Watermark
        if ($watermarkText !== '') {
            $w = imagesx($img);
            $h = imagesy($img);
            $fontSize = max(3, (int) ($w / 60));
            $tw = imagefontwidth($fontSize) * mb_strlen($watermarkText);
            $th = imagefontheight($fontSize);
            $alpha = (int) ((1 - $watermarkOpacity / 100) * 127);
            $color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
            $positions = [
                'center' => [($w - $tw) / 2, ($h - $th) / 2],
                'top-left' => [10, 10],
                'top-right' => [$w - $tw - 10, 10],
                'bottom-left' => [10, $h - $th - 10],
                'bottom-right' => [$w - $tw - 10, $h - $th - 10],
            ];
            [$x, $y] = $positions[$watermarkPos] ?? $positions['center'];
            imagestring($img, $fontSize, (int) $x, (int) $y, $watermarkText, $color);
        }

        $origSize = filesize($path);
        $tmp = sys_get_temp_dir() . '/fly_compress_' . time() . '_' . random_int(1000, 9999) . '.' . $format;
        $saved = match (strtolower($format)) {
            'jpeg', 'jpg' => imagejpeg($img, $tmp, $quality),
            'webp' => function_exists('imagewebp') ? imagewebp($img, $tmp, $quality) : imagepng($img, $tmp, 9),
            default => imagepng($img, $tmp, (int) (9 * $quality / 100)),
        };
        imagedestroy($img);
        $newSize = is_file($tmp) ? filesize($tmp) : 0;
        $ratio = $origSize > 0 ? round((1 - $newSize / $origSize) * 100, 1) : 0;
        if ($saved === false) {
            return ['error' => 'Save failed', 'output' => '', 'original' => $origSize, 'compressed' => 0, 'ratio' => '0%'];
        }
        return [
            'output' => $tmp,
            'original' => $origSize,
            'compressed' => $newSize,
            'ratio' => $ratio . '%',
        ];
    }

    // ── Screenshot (macOS screencapture) ──────────────────────────────
    public static function capture(string $dir, string $name = 'fly_ss_{datetime}', bool $hide = false): string
    {
        if (!is_dir($dir)) {
            return "Error: output directory does not exist: {$dir}";
        }
        $placeholders = [
            '{index}' => (string) (int) (microtime(true) * 1000),
            '{timestamp}' => (string) time(),
            '{datetime}' => date('Ymd-His'),
            '{uuid}' => bin2hex(random_bytes(4)),
        ];
        $finalName = str_replace(array_keys($placeholders), array_values($placeholders), $name);
        $outFile = rtrim($dir, '/') . '/' . $finalName . '.png';

        $cmd = ['/usr/sbin/screencapture', '-s', '-x', '-t', 'png', $outFile];
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return 'Error: failed to start screencapture';
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            return "Error: screencapture exit={$exit} stderr={$stderr}";
        }
        return "Saved: {$outFile}";
    }

    public static function captureDefaultDir(): string
    {
        $home = getenv('HOME') ?: '/tmp';
        $dir = $home . '/Pictures/FlyEnvScreens';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    // ── PHP Obfuscator (yakpro-po) ────────────────────────────────────
    public static function phpObfuscate(string $src, string $desc, string $phpBin = 'php', string $configPath = ''): string
    {
        if (!is_file($src) && !is_dir($src)) {
            return "Error: source not found: {$src}";
        }
        if (!is_dir($desc)) {
            if (!@mkdir($desc, 0755, true)) {
                return "Error: cannot create output dir: {$desc}";
            }
        }
        $yakpro = realpath(__DIR__ . '/../../assets/php-obfuscator/yakpro-po.php');
        if ($yakpro === false) {
            return 'Error: yakpro-po.php not found in assets/php-obfuscator/';
        }
        $configArg = [];
        if ($configPath !== '' && is_file($configPath)) {
            $configArg = ['--config-file', $configPath];
        }
        // yakpro-po: php yakpro-po.php -o <out> -s <out_suffix> [-c config] [input]
        $cmd = array_merge(
            [$phpBin, $yakpro, '-o', 'obfuscated', '-s', '_obf.php'],
            $configArg,
            [is_dir($src) ? rtrim($src, '/') . '/' : $src]
        );
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return "Error: failed to start obfuscator (php={$phpBin})";
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            return "Error: obfuscator exit={$exit} stderr={$stderr}";
        }
        $outDir = is_dir($src) ? rtrim($src, '/') . '/obfuscated' : dirname($src) . '/obfuscated';
        return "Obfuscated → {$outDir}\n{$stdout}";
    }

    public static function yakproDefaultConfig(): string
    {
        $path = realpath(__DIR__ . '/../../assets/php-obfuscator/yakpro-po.default.cnf');
        if ($path === false) return '';
        return file_get_contents($path);
    }

    // ── Port Kill (search/list version) ────────────────────────────────
    /** @return list<array{pid:int,user:string,command:string,port:string}> */
    public static function portKillSearch(int $port): array
    {
        $rows = [];
        $cmd = sprintf('lsof -nP -iTCP:%d -sTCP:LISTEN 2>/dev/null', $port);
        exec($cmd, $lines);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'COMMAND')) continue;
            $parts = preg_split('/\s+/', $line, 9);
            if (count($parts) < 9) continue;
            $rows[] = [
                'pid' => (int) $parts[1],
                'user' => $parts[2],
                'command' => $parts[0] . ' ' . substr($parts[8], 0, 60),
                'port' => (string) $port,
            ];
        }
        if (empty($rows)) {
            exec(sprintf('lsof -ti :%d 2>/dev/null', $port), $pids);
            foreach ($pids as $p) {
                $p = trim($p);
                if ($p === '' || !ctype_digit($p)) continue;
                $rows[] = [
                    'pid' => (int) $p,
                    'user' => '?',
                    'command' => "process on port {$port}",
                    'port' => (string) $port,
                ];
            }
        }
        return $rows;
    }

    /**
     * @return array{killed:int, attempted:int, details:list<array{pid:int, killed:bool, error:string}>}
     */
    public static function portKillPids(array $pids): array
    {
        $killed = 0; $attempted = 0; $details = [];
        foreach ($pids as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) continue;
            $attempted++;
            exec("kill -9 {$pid} 2>&1", $out, $code);
            $ok = ($code === 0);
            if ($ok) $killed++; 
            $details[] = [
                'pid' => $pid,
                'killed' => $ok,
                'error' => $ok ? '' : trim(implode("\n", $out) ?: 'Operation not permitted'),
            ];
        }
        return ['killed' => $killed, 'attempted' => $attempted, 'details' => $details];
    }

    // ── Process Kill (search/list version) ────────────────────────────
    /** @return list<array{pid:int,user:string,command:string}> */
    public static function processKillSearch(string $keyword): array
    {
        $rows = [];
        if ($keyword === '') {
            exec('ps -axo pid,user,comm 2>/dev/null', $lines);
        } else {
            $kw = escapeshellarg($keyword);
            exec("ps -axo pid,user,comm 2>/dev/null | grep -i {$kw} | grep -v grep", $lines);
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'PID')) continue;
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 3 || !ctype_digit($parts[0])) continue;
            $rows[] = [
                'pid' => (int) $parts[0],
                'user' => $parts[1],
                'command' => substr($parts[2], 0, 60),
            ];
        }
        return $rows;
    }

    /**
     * @return array{killed:int, attempted:int, details:list<array{pid:int, killed:bool, error:string}>}
     */
    public static function processKillPids(array $pids): array
    {
        $killed = 0; $attempted = 0; $details = [];
        foreach ($pids as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) continue;
            $attempted++;
            exec("kill -9 {$pid} 2>&1", $out, $code);
            $ok = ($code === 0);
            if ($ok) $killed++;
            $details[] = [
                'pid' => $pid,
                'killed' => $ok,
                'error' => $ok ? '' : trim(implode("\n", $out) ?: 'Operation not permitted'),
            ];
        }
        return ['killed' => $killed, 'attempted' => $attempted, 'details' => $details];
    }
}
