#!/usr/bin/env php
<?php
/**
 * FlyEnv Toolbox — Rebuilt from original FlyEnv 4.15.4 source
 *
 * Hybrid architecture: frontend JS for most tools,
 * PHP WebView bindings for system-level operations.
 *
 * Usage: php85 flyenv-web.php
 */

declare(strict_types=1);

require __DIR__ . "/vendor/autoload.php";

use Libui\App;
use Libui\Ffi;
use Libui\Window;
use Libui\Menu;
use Yangweijie\Ui2\WebView;
use App\FlyEnvWebApp;
use Illuminate\Process\Factory as ProcessFactory;

Ffi::init();

$proc = new ProcessFactory();

$appMenu = new Menu("App");
$appMenu->appendQuitItem();

$window = new Window("FlyEnv Toolbox", 1100, 760, true);
$wv = new WebView($window, 0, 0, 1100, 760, true);
$wv->autoResize($window, 0, 0);
$wv->setHtml(new FlyEnvWebApp()->getHtml());

// ══════════════════════════════════════════════════════════════
// PHP BACKEND BINDINGS (system/network/file operations)
// ══════════════════════════════════════════════════════════════

// QR Code
$wv->bind("qr", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $t = $d["text"] ?? "";
        if (empty($t)) {
            $wv->return(
                $id,
                1,
                json_encode(["error" => "No input: req=" . json_encode($req)]),
            );
            return;
        }
        $ecc = [
            \chillerlan\QRCode\Common\EccLevel::L,
            \chillerlan\QRCode\Common\EccLevel::M,
            \chillerlan\QRCode\Common\EccLevel::Q,
            \chillerlan\QRCode\Common\EccLevel::H,
        ][min(3, max(0, (int) ($d["ecc"] ?? 1)))];
        $opts = new \chillerlan\QRCode\QROptions();
        $opts->outputInterface = \chillerlan\QRCode\Output\QRMarkupSVG::class;
        $opts->eccLevel = $ecc;
        $opts->scale = 5;
        $opts->addQuietzone = true;
        $wv->return(
            $id,
            0,
            json_encode([
                "svg" => new \chillerlan\QRCode\QRCode($opts)->render($t),
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// RSA
$wv->bind("rsa", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $bits = [1024, 2048, 4096][min(2, max(0, (int) ($d["bits"] ?? 1)))];
        if (!extension_loaded("openssl")) {
            $wv->return($id, 1, json_encode(["error" => "OpenSSL required"]));
            return;
        }
        $key = openssl_pkey_new([
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        if (!$key) {
            $wv->return(
                $id,
                1,
                json_encode(["error" => openssl_error_string()]),
            );
            return;
        }
        $priv = "";
        openssl_pkey_export($key, $priv);
        $pub = openssl_pkey_get_details($key)["key"] ?? "";
        $wv->return(
            $id,
            0,
            json_encode([
                "public" => $pub,
                "private" => $priv,
                "bits" => $bits,
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// File Info
$wv->bind("file_info", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $p = $d["path"] ?? "";
        if (!file_exists($p)) {
            $wv->return($id, 1, json_encode(["error" => "Not found"]));
            return;
        }
        if (is_dir($p)) {
            $wv->return(
                $id,
                0,
                json_encode([
                    "type" => "dir",
                    "path" => realpath($p),
                    "perms" => substr(sprintf("%o", fileperms($p)), -4),
                    "mtime" => date("Y-m-d H:i:s", filemtime($p)),
                ]),
            );
            return;
        }
        $sz = filesize($p);
        $i = [
            "path" => realpath($p),
            "type" => "file",
            "size" => $sz,
            "size_h" =>
                $sz >= 1073741824
                    ? round($sz / 1073741824, 1) . " GB"
                    : ($sz >= 1048576
                        ? round($sz / 1048576, 1) . " MB"
                        : ($sz >= 1024
                            ? round($sz / 1024, 1) . " KB"
                            : $sz . " B")),
            "ext" => pathinfo($p, PATHINFO_EXTENSION),
            "mime" => mime_content_type($p) ?: "?",
            "perms" => substr(sprintf("%o", fileperms($p)), -4),
            "mtime" => date("Y-m-d H:i:s", filemtime($p)),
            "ctime" => date("Y-m-d H:i:s", filectime($p)),
        ];
        $i["md5"] = $sz < 100 * 1024 * 1024 ? md5_file($p) : "(large)";
        $i["sha1"] = $sz < 100 * 1024 * 1024 ? sha1_file($p) : "(large)";
        $wv->return($id, 0, json_encode($i));
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// Image compress
$wv->bind("image_c", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $p = $d["path"] ?? "";
        $q = (int) ($d["quality"] ?? 70);
        $mw = (int) ($d["maxWidth"] ?? 1920);
        if (!file_exists($p) || !($info = @getimagesize($p))) {
            $wv->return($id, 1, json_encode(["error" => "Invalid image"]));
            return;
        }
        [$ow, $oh, $type] = $info;
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($p),
            IMAGETYPE_PNG => @imagecreatefrompng($p),
            IMAGETYPE_WEBP => @imagecreatefromwebp($p),
            default => null,
        };
        if (!$src) {
            $wv->return($id, 1, json_encode(["error" => "Unsupported"]));
            return;
        }
        $nw = min($ow, $mw);
        $nh = (int) ($oh * ($nw / $ow));
        $dst = imagescale($src, $nw, $nh, IMG_BILINEAR_FIXED);
        if (!$dst) {
            $wv->return($id, 1, json_encode(["error" => "Resize failed"]));
            return;
        }
        $tmp = sys_get_temp_dir() . "/fly_" . time() . ".jpg";
        imagejpeg($dst, $tmp, $q);
        $ns = filesize($tmp);
        $wv->return(
            $id,
            0,
            json_encode([
                "saved" => $tmp,
                "size" => $ns,
                "orig" => filesize($p),
                "dim" => "{$nw}x{$nh}",
                "ratio" => round((1 - $ns / filesize($p)) * 100, 1),
            ]),
        );
        imagedestroy($src);
        imagedestroy($dst);
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// Capture (screenshot)
$wv->bind("capture", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $t = $d["type"] ?? "full";
        $p = getenv("HOME") . "/Desktop/fly_ss_" . date("Ymd_His") . ".png";
        $cmd = match ($t) {
            "select" => "screencapture -i -x",
            "window" => "screencapture -i -W -x",
            default => "screencapture -x",
        };
        $result = $GLOBALS["proc"]->run(
            "{$cmd} " . escapeshellarg($p) . " 2>/dev/null",
        );
        $ok = $result->successful() && file_exists($p);
        $wv->return(
            $id,
            $ok ? 0 : 1,
            json_encode(
                $ok
                    ? ["path" => $p, "size" => filesize($p)]
                    : ["error" => "Cancelled"],
            ),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// Code Run (PHP playground) — sandboxed subprocess via Illuminate Process
$wv->bind("code_run", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $code = $d["code"] ?? "";
        if (empty($code)) {
            $wv->return($id, 1, json_encode(["error" => "No code"]));
            return;
        }
        $body = preg_replace("/^<\?(?:php)?\s*/", "", $code);
        $runner =
            "try{\$__ret=eval(stream_get_contents(STDIN));if(\$__ret!==null)echo\$__ret;}catch(\Throwable\$__e){echo\$__e->getMessage();}";
        $safe = implode(",", [
            "exec",
            "shell_exec",
            "system",
            "passthru",
            "popen",
            "proc_open",
            "pcntl_exec",
            "pcntl_fork",
            "dl",
            "phpinfo",
            "ini_set",
            "ini_restore",
            "mail",
            "curl_exec",
            "curl_multi_exec",
            "create_function",
            "assert",
        ]);
        $phpBin = PHP_BINARY;
        // Use direct proc_open for reliability (bypass Illuminate Process)
        $spec = [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]];
        $cmd =
            "{$phpBin} -d open_basedir=" .
            escapeshellarg(sys_get_temp_dir()) .
            " -d disable_functions=" .
            escapeshellarg($safe) .
            " -d display_errors=0 -r " .
            escapeshellarg($runner);
        $ph = @proc_open($cmd, $spec, $pipes, null, null, [
            "bypass_shell" => true,
        ]);
        if (!is_resource($ph)) {
            $wv->return($id, 1, json_encode(["error" => "proc_open failed"]));
            return;
        }
        fwrite($pipes[0], $body);
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $ec = proc_close($ph);
        $output = $out ?: $err;
        $wv->return(
            $id,
            0,
            json_encode([
                "output" => $output ?: "(no output / exit " . $ec . ")",
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return(
            $id,
            1,
            json_encode(["error" => get_class($e) . ": " . $e->getMessage()]),
        );
    }
});

// URL Timing
$wv->bind("url_timing", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $url = $d["url"] ?? "";
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $wv->return($id, 1, json_encode(["error" => "Invalid URL"]));
            return;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_NOBODY => 1,
        ]);
        $start = microtime(true);
        curl_exec($ch);
        $total = round((microtime(true) - $start) * 1000, 1);
        $info = curl_getinfo($ch);
        $wv->return(
            $id,
            0,
            json_encode([
                "total" => $total,
                "dns" => round($info["namelookup_time"] * 1000, 1),
                "tcp" => round(
                    ($info["connect_time"] - $info["namelookup_time"]) * 1000,
                    1,
                ),
                "ssl" => round(
                    ($info["pretransfer_time"] - $info["connect_time"]) * 1000,
                    1,
                ),
                "ttfb" => round($info["starttransfer_time"] * 1000, 1),
                "code" => $info["http_code"],
                "size" => $info["download_content_length"],
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// Site Sucker (download page)
$wv->bind("site_suck", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $url = $d["url"] ?? "";
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $wv->return($id, 1, json_encode(["error" => "Invalid URL"]));
            return;
        }
        $html = @file_get_contents(
            $url,
            false,
            stream_context_create([
                "http" => ["timeout" => 10, "user_agent" => "Mozilla/5.0"],
            ]),
        );
        if ($html === false) {
            $wv->return($id, 1, json_encode(["error" => "Failed to fetch"]));
            return;
        }
        $wv->return(
            $id,
            0,
            json_encode([
                "html" => mb_substr($html, 0, 50000),
                "size" => strlen($html),
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// SSL Make (self-signed cert)
$wv->bind("ssl_make", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        if (!extension_loaded("openssl")) {
            $wv->return($id, 1, json_encode(["error" => "OpenSSL required"]));
            return;
        }
        $cn = $d["cn"] ?? "localhost";
        $days = (int) ($d["days"] ?? 365);
        $bits = (int) ($d["bits"] ?? 2048);
        $key = openssl_pkey_new([
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        $csr = openssl_csr_new(
            ["commonName" => $cn, "organizationName" => "FlyEnv"],
            $key,
        );
        $cert = openssl_csr_sign($csr, null, $key, $days, [
            "digest_alg" => "sha256",
        ]);
        openssl_x509_export($cert, $certOut);
        openssl_pkey_export($key, $keyOut);
        $wv->return(
            $id,
            0,
            json_encode([
                "cert" => $certOut,
                "key" => $keyOut,
                "cn" => $cn,
                "days" => $days,
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// PHP Obfuscator (base64 encode)
$wv->bind("php_obf", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $code = $d["code"] ?? "";
        $b64 = base64_encode(gzencode($code));
        $obf = "<?php\n/** FlyEnv Obfuscator */\neval(gzuncompress(base64_decode('{$b64}')));\n?>";
        $wv->return(
            $id,
            0,
            json_encode([
                "result" => $obf,
                "ratio" => round(
                    (strlen($obf) / max(1, strlen($code))) * 100,
                    1,
                ),
            ]),
        );
    } catch (\Throwable $e) {
        $wv->return($id, 1, json_encode(["error" => $e->getMessage()]));
    }
});

// Port Kill — lsof + kill (native exec + posix_kill fallback)
$wv->bind("port_kill", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $a = $d["action"] ?? "";
        if ($a === "search") {
            $port = (int) ($d["port"] ?? 0);
            if ($port <= 0) {
                $wv->return($id, 1, json_encode(["error" => "Invalid port"]));
                return;
            }
            $out = [];
            $ec = -1;
            exec(
                "lsof -i :{$port} 2>/dev/null | awk 'NR>1{print \$2, \$3, \$NF}'",
                $out,
                $ec,
            );
            $procs = [];
            foreach ($out as $line) {
                $parts = preg_split("/\s+/", trim($line), 3);
                if (count($parts) >= 2) {
                    $procs[] = [
                        "PID" => $parts[0],
                        "USER" => $parts[1] ?? "",
                        "COMMAND" => $parts[2] ?? "",
                    ];
                }
            }
            $wv->return(
                $id,
                0,
                json_encode(["port" => $port, "processes" => $procs]),
            );
        } else {
            $pids = $d["pids"] ?? [];
            error_log("[port_kill] action=$a pids=" . json_encode($pids));
            $results = [];
            foreach ($pids as $pid) {
                $pid = (int) $pid;
                if ($pid <= 0) {
                    continue;
                }
                // Use /bin/kill directly (full path, avoid shell builtin issues)
                $out = [];
                $ec = -1;
                exec("/bin/kill -9 {$pid} 2>&1", $out, $ec);
                $ok = $ec === 0;
                $err = $ok
                    ? ""
                    : (implode("\n", $out) ?:
                    "kill returned exit code " . $ec);
                error_log(
                    "[port_kill] PID=$pid exit=$ec ok=" .
                        json_encode($ok) .
                        " err=" .
                        json_encode($err),
                );
                $results[] = [
                    "PID" => $pid,
                    "killed" => $ok,
                    "error" => $err,
                ];
            }
            $killed = count(array_filter($results, fn($r) => $r["killed"]));
            $wv->return(
                $id,
                0,
                json_encode([
                    "killed" => $killed,
                    "attempted" => count($pids),
                    "details" => $results,
                ]),
            );
        }
    } catch (\Throwable $e) {
        $wv->return(
            $id,
            1,
            json_encode(["error" => get_class($e) . ": " . $e->getMessage()]),
        );
    }
});

// Process Kill — ps + kill (native exec + posix_kill fallback)
$wv->bind("process_kill", function (string $id, string $req) use ($wv): void {
    try {
        $d = json_decode($req, true);
        $a = $d["action"] ?? "";
        if ($a === "search") {
            $key = escapeshellarg($d["keyword"] ?? "");
            if (empty($key) || $key === "''") {
                $wv->return($id, 1, json_encode(["error" => "Empty keyword"]));
                return;
            }
            $out = [];
            $ec = -1;
            exec(
                "ps aux 2>/dev/null | grep -i {$key} | grep -v grep | awk '{print \$2, \$1, \$NF}'",
                $out,
                $ec,
            );
            $procs = [];
            foreach ($out as $line) {
                $parts = preg_split("/\s+/", trim($line), 3);
                if (count($parts) >= 2) {
                    $procs[] = [
                        "PID" => $parts[0],
                        "USER" => $parts[1] ?? "",
                        "COMMAND" => $parts[2] ?? "",
                    ];
                }
            }
            $wv->return(
                $id,
                0,
                json_encode([
                    "keyword" => $d["keyword"],
                    "processes" => $procs,
                ]),
            );
        } else {
            $pids = $d["pids"] ?? [];
            error_log("[process_kill] action=$a pids=" . json_encode($pids));
            $results = [];
            foreach ($pids as $pid) {
                $pid = (int) $pid;
                if ($pid <= 0) {
                    continue;
                }
                $out = [];
                $ec = -1;
                exec("/bin/kill -9 {$pid} 2>&1", $out, $ec);
                $ok = $ec === 0;
                $err = $ok
                    ? ""
                    : (implode("\n", $out) ?:
                    "kill returned exit code " . $ec);
                error_log(
                    "[process_kill] PID=$pid exit=$ec ok=" .
                        json_encode($ok) .
                        " err=" .
                        json_encode($err),
                );
                $results[] = [
                    "PID" => $pid,
                    "killed" => $ok,
                    "error" => $err,
                ];
            }
            $killed = count(array_filter($results, fn($r) => $r["killed"]));
            $wv->return(
                $id,
                0,
                json_encode([
                    "killed" => $killed,
                    "attempted" => count($pids),
                    "details" => $results,
                ]),
            );
        }
    } catch (\Throwable $e) {
        $wv->return(
            $id,
            1,
            json_encode(["error" => get_class($e) . ": " . $e->getMessage()]),
        );
    }
});

$wv->cleanupOnClose($window);
// Periodic temp file cleanup (older than 1 hour)
register_shutdown_function(function () {
    $files = glob(sys_get_temp_dir() . "/fly_*");
    if ($files) {
        foreach ($files as $f) {
            if (filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
    }
});
App::new()->window($window)->onShouldQuit(fn() => true)->run();

