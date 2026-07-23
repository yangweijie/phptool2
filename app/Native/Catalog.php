<?php

declare(strict_types=1);

namespace App\Native;

/**
 * Tool catalog for the self-drawn (Surface) native app.
 *
 * Mirrors the structure of the legacy webview catalog (app/FlyEnvWebApp.php):
 * the same tool ids / categories / names / icons, but the panel map points at
 * native panel builders instead of JS panel keys. Tools without a native panel
 * yet fall back to a "coming soon" placeholder.
 *
 * Also manages the favorites (starred tool ids) — stored in a JSON file so
 * favourites survive app restarts.
 */
final class Catalog
{
    private static ?Catalog $instance = null;
    private static bool $chinese = true;

    /** @var list<array{id:string,cat:string,name:string,nameEn:string,icon:string}> */
    private array $tools;

    /** @var list<string> */
    private array $categories;

    /** tool id => native panel key (only implemented tools are listed). */
    private array $nativePanels;

    /** @var array<string,bool> tool id => isFav */
    private array $favorites;

    /** @var list<string> user-specified favorites order (most-favored first) */
    private array $favoritesOrder = [];

    private string $favFile;

    public static function getInstance(): Catalog
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setLocale(string $locale): void
    {
        self::$chinese = $locale === 'zh';
    }

    public static function chinese(): bool
    {
        return self::$chinese;
    }

    public function __construct()
    {
        $this->favFile = sys_get_temp_dir() . '/flyenv_favorites.json';

        $this->tools = [
            // ── Code ──────────────────────────────────────────────────
            ['id'=>'CodePlay','cat'=>'Code','name'=>'代码沙箱','nameEn'=>'Code Playground','icon'=>'💻'],
            ['id'=>'CodeLibrary','cat'=>'Code','name'=>'代码库','nameEn'=>'Code Library','icon'=>'📚'],
            ['id'=>'MarkdownPreview','cat'=>'Code','name'=>'Markdown 预览','nameEn'=>'Markdown Preview','icon'=>'📝'],
            // ── Development ────────────────────────────────────────────
            ['id'=>'diff-compare','cat'=>'Development','name'=>'文本对比','nameEn'=>'Diff Compare','icon'=>'📋'],
            ['id'=>'cron-parser','cat'=>'Development','name'=>'Cron 解析','nameEn'=>'Cron Parser','icon'=>'⏰'],
            ['id'=>'JsonParse','cat'=>'Development','name'=>'JSON 转换器','nameEn'=>'JSON Parser','icon'=>'🔄'],
            ['id'=>'regex-tester','cat'=>'Development','name'=>'正则测试','nameEn'=>'Regex Tester','icon'=>'🔍'],
            ['id'=>'chmod-calculator','cat'=>'Development','name'=>'权限计算','nameEn'=>'Chmod','icon'=>'🔐'],
            ['id'=>'regex-cheatsheet','cat'=>'Development','name'=>'正则速查','nameEn'=>'Regex Cheatsheet','icon'=>'📖'],
            ['id'=>'git-cheatsheet','cat'=>'Development','name'=>'Git 速查','nameEn'=>'Git Memo','icon'=>'🔀'],
            ['id'=>'FileInfo','cat'=>'Development','name'=>'文件信息','nameEn'=>'File Info','icon'=>'📄'],
            ['id'=>'BomClean','cat'=>'Development','name'=>'BOM 清理','nameEn'=>'BOM Clean','icon'=>'🧹'],
            ['id'=>'PhpObfuscator','cat'=>'Development','name'=>'PHP 混淆','nameEn'=>'PHP Obfuscator','icon'=>'🔒'],
            ['id'=>'SystemEnv','cat'=>'Development','name'=>'系统环境','nameEn'=>'System Env','icon'=>'⚙️'],
            ['id'=>'SiteSucker','cat'=>'Development','name'=>'网站下载','nameEn'=>'Site Sucker','icon'=>'🌐'],
            ['id'=>'RequestTime','cat'=>'Development','name'=>'URL 测速','nameEn'=>'URL Timing','icon'=>'⏱️'],
            ['id'=>'SSLMake','cat'=>'Development','name'=>'SSL 证书','nameEn'=>'SSL Make','icon'=>'🔑'],
            ['id'=>'PortKill','cat'=>'Development','name'=>'端口杀进程','nameEn'=>'Port Kill','icon'=>'🔌'],
            ['id'=>'ProcessKill','cat'=>'Development','name'=>'进程杀','nameEn'=>'Process Kill','icon'=>'🗡️'],
            ['id'=>'keycode-info','cat'=>'Development','name'=>'键码信息','nameEn'=>'Keycode Info','icon'=>'⌨️'],
            // ── Crypto ────────────────────────────────────────────────
            ['id'=>'jwt-encoder-decoder','cat'=>'Crypto','name'=>'JWT 编解码','nameEn'=>'JWT','icon'=>'🔐'],
            ['id'=>'HashText','cat'=>'Crypto','name'=>'Hash 计算','nameEn'=>'Hash Text','icon'=>'🔑'],
            ['id'=>'EncryptDecryptText','cat'=>'Crypto','name'=>'加密解密','nameEn'=>'Encryption','icon'=>'🔒'],
            ['id'=>'rsa-key-generator','cat'=>'Crypto','name'=>'RSA 密钥','nameEn'=>'RSA Key Gen','icon'=>'🗝️'],
            ['id'=>'TokenGenerator','cat'=>'Crypto','name'=>'Token 生成','nameEn'=>'Token Gen','icon'=>'🎟️'],
            // ── Converter ─────────────────────────────────────────────
            ['id'=>'Timestamp','cat'=>'Converter','name'=>'时间戳','nameEn'=>'Timestamp','icon'=>'⏲️'],
            ['id'=>'base64-string-converter','cat'=>'Converter','name'=>'Base64','nameEn'=>'Base64','icon'=>'🔡'],
            ['id'=>'base64-file-converter','cat'=>'Converter','name'=>'Base64 文件','nameEn'=>'Base64 File','icon'=>'📁'],
            ['id'=>'url-encode','cat'=>'Converter','name'=>'URL 编解码','nameEn'=>'URL Encode','icon'=>'🔗'],
            ['id'=>'escape-html','cat'=>'Converter','name'=>'HTML 转义','nameEn'=>'Escape HTML','icon'=>'🏷️'],
            // ── Web ───────────────────────────────────────────────────
            ['id'=>'websocket-sse','cat'=>'Web','name'=>'WebSocket/SSE','nameEn'=>'WS/SSE','icon'=>'🔌'],
            ['id'=>'url-parse','cat'=>'Web','name'=>'URL 解析','nameEn'=>'URL Parse','icon'=>'🔗'],
            ['id'=>'mime-types','cat'=>'Web','name'=>'MIME 类型','nameEn'=>'MIME Types','icon'=>'📋'],
            ['id'=>'http-status-codes','cat'=>'Web','name'=>'HTTP 状态码','nameEn'=>'HTTP Status','icon'=>'📡'],
            // ── Images ────────────────────────────────────────────────
            ['id'=>'qr-code-generator','cat'=>'Images','name'=>'二维码','nameEn'=>'QR Code','icon'=>'📱'],
            ['id'=>'wifi-qr-code-generator','cat'=>'Images','name'=>'WiFi 二维码','nameEn'=>'WiFi QR','icon'=>'📶'],
            ['id'=>'ImageCompress','cat'=>'Images','name'=>'图片压缩','nameEn'=>'Image Compress','icon'=>'🖼️'],
            ['id'=>'Capturer','cat'=>'Images','name'=>'截图','nameEn'=>'Screenshot','icon'=>'📷'],
        ];

        $this->categories = ['Code','Development','Crypto','Converter','Web','Images'];

        // tool id => native panel key (implemented so far; the rest → placeholder)
        $this->nativePanels = [
            'JsonParse' => 'json',
            'base64-string-converter' => 'b64',
            'base64-file-converter' => 'b64file',
            'HashText' => 'hash',
            'Timestamp' => 'ts',
            'jwt-encoder-decoder' => 'jwt',
            'chmod-calculator' => 'chmod',
            'url-encode' => 'url',
            'diff-compare' => 'diff',
            'FileInfo' => 'file',
            'MarkdownPreview' => 'md',
            'escape-html' => 'escape',
            'url-parse' => 'urlparse',
            'regex-tester' => 'regex',
            'TokenGenerator' => 'token',
            'SystemEnv' => 'sysenv',
            'http-status-codes' => 'http',
            'mime-types' => 'mime',
            'EncryptDecryptText' => 'encrypt',
            'rsa-key-generator' => 'rsa',
            'qr-code-generator' => 'qr',
            'SiteSucker' => 'sucker',
            'BomClean' => 'bom',
            'cron-parser' => 'cron',
            'regex-cheatsheet' => 'regexref',
            'git-cheatsheet' => 'git',
            'websocket-sse' => 'wssse',
            'keycode-info' => 'keycode',
            'wifi-qr-code-generator' => 'wifiqr',
            'SSLMake' => 'sslmk',
            'RequestTime' => 'timing',
            'CodePlay' => 'codeplay',
            'CodeLibrary' => 'codelib',
            'PortKill' => 'portkill',
            'ProcessKill' => 'prockill',
            'ImageCompress' => 'imgcompress',
            'PhpObfuscator' => 'obf',
            'Capturer' => 'cap',
        ];

        $this->loadFavorites();
    }

    /** @return list<array{id:string,cat:string,name:string,nameEn:string,icon:string}> */
    public function tools(): array
    {
        return $this->tools;
    }

    /** @return list<string> */
    public function categories(): array
    {
        return $this->categories;
    }

    /** Native panel key for a tool id, or 'placeholder' when not yet implemented. */
    public function panelKey(string $toolId): string
    {
        return $this->nativePanels[$toolId] ?? 'placeholder';
    }

    public function nameOf(string $toolId): string
    {
        foreach ($this->tools as $t) {
            if ($t['id'] === $toolId) {
                return $t['name'];
            }
        }
        return $toolId;
    }

    public function nameEnOf(string $toolId): string
    {
        foreach ($this->tools as $t) {
            if ($t['id'] === $toolId) {
                return $t['nameEn'];
            }
        }
        return $toolId;
    }

    public function iconOf(string $toolId): string
    {
        foreach ($this->tools as $t) {
            if ($t['id'] === $toolId) {
                return $t['icon'];
            }
        }
        return '🔧';
    }

    /** @return list<array{id:string,name:string}> tools in a given category */
    public function toolsIn(string $category): array
    {
        $out = [];
        foreach ($this->tools as $t) {
            if ($t['cat'] === $category) {
                $out[] = ['id' => $t['id'], 'name' => $t['name']];
            }
        }
        return $out;
    }

    /** @return list<array{id:string,cat:string,name:string,icon:string}> */
    public function search(string $query): array
    {
        if (trim($query) === '') {
            return $this->tools;
        }
        $q = mb_strtolower(trim($query));
        $out = [];
        foreach ($this->tools as $t) {
            if (mb_strpos(mb_strtolower($t['name']), $q) !== false
                || mb_strpos(mb_strtolower($t['nameEn']), $q) !== false
                || mb_strpos(mb_strtolower($t['id']), $q) !== false) {
                $out[] = $t;
            }
        }
        return $out;
    }

    // ── Favorites ──────────────────────────────────────────────────────────

    /** @return list<array{id:string,cat:string,name:string,nameEn:string,icon:string}> */
    public function favorites(): array
    {
        // If we have a user-specified order, use it
        if (!empty($this->favoritesOrder)) {
            $toolMap = [];
            foreach ($this->tools as $t) {
                $toolMap[$t['id']] = $t;
            }
            $out = [];
            foreach ($this->favoritesOrder as $id) {
                if (!empty($this->favorites[$id]) && isset($toolMap[$id])) {
                    $out[] = $toolMap[$id];
                }
            }
            return $out;
        }
        // Fallback: use master tools order
        $out = [];
        foreach ($this->tools as $t) {
            if (!empty($this->favorites[$t['id']])) {
                $out[] = $t;
            }
        }
        return $out;
    }

    public function isFavorite(string $toolId): bool
    {
        return !empty($this->favorites[$toolId]);
    }

    public function toggleFavorite(string $toolId): void
    {
        if (!empty($this->favorites[$toolId])) {
            unset($this->favorites[$toolId]);
            // Remove from order
            $this->favoritesOrder = array_values(array_filter($this->favoritesOrder, fn ($id) => $id !== $toolId));
        } else {
            $this->favorites[$toolId] = true;
            $this->favoritesOrder[] = $toolId;
        }
        $this->saveFavorites();
    }

    /**
     * Move a favorited tool one step up (-1) or down (+1) in the favorites list.
     */
    public function reorderFavorite(string $toolId, int $direction): void
    {
        if (empty($this->favorites[$toolId])) {
            return;
        }
        $idx = array_search($toolId, $this->favoritesOrder);
        if ($idx === false) {
            return;
        }
        $newIdx = $idx + $direction;
        if ($newIdx < 0 || $newIdx >= count($this->favoritesOrder)) {
            return;
        }
        // Swap
        $temp = $this->favoritesOrder[$idx];
        $this->favoritesOrder[$idx] = $this->favoritesOrder[$newIdx];
        $this->favoritesOrder[$newIdx] = $temp;
        $this->saveFavorites();
    }

    public function hasFavorites(): bool
    {
        foreach ($this->tools as $t) {
            if (!empty($this->favorites[$t['id']])) {
                return true;
            }
        }
        return false;
    }

    // ── Persistence ────────────────────────────────────────────────────────

    private function loadFavorites(): void
    {
        if (is_file($this->favFile)) {
            $data = json_decode(file_get_contents($this->favFile), true);
            if (is_array($data)) {
                // Support both formats: old (hash map) and new (with order key)
                if (isset($data['order']) && isset($data['ids'])) {
                    $this->favorites = [];
                    foreach ($data['ids'] as $id) {
                        $this->favorites[$id] = true;
                    }
                    $this->favoritesOrder = $data['order'];
                } else {
                    $this->favorites = $data;
                    $this->favoritesOrder = array_keys($data);
                }
            } else {
                $this->favorites = [];
            }
        } else {
            $this->favorites = [];
        }
    }

    private function saveFavorites(): void
    {
        $data = [
            'ids' => $this->favorites,
            'order' => $this->favoritesOrder,
        ];
        file_put_contents($this->favFile, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
