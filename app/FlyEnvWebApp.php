<?php
/**
 * FlyEnv Toolbox — PHP Entry: reads static assets, injects dynamic data
 *
 * CSS and JS are in separate files under assets/ for readability.
 * Only dynamic data (tool list, category names, panel map) is generated here.
 */

declare(strict_types=1);

namespace App;

class FlyEnvWebApp
{
    private array $tools;
    private array $categories;
    private array $panelMap;

    public function __construct()
    {
        $this->tools = [
            ['id'=>'CodePlay','cat'=>'Code','name'=>'Code Playground','icon'=>'<svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3" fill="currentColor"/></svg>'],
            ['id'=>'CodeLibrary','cat'=>'Code','name'=>'Code Library','icon'=>'<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15z" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'MarkdownPreview','cat'=>'Code','name'=>'Markdown Preview','icon'=>'<svg viewBox="0 0 24 24"><path d="M22 5v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 15V9l3 3 3-3v6" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'diff-compare','cat'=>'Development','name'=>'Diff Compare','icon'=>'<svg viewBox="0 0 24 24"><path d="M7 7h10M7 12h6M7 17h10" fill="none" stroke="currentColor" stroke-width="2"/><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'cron-parser','cat'=>'Development','name'=>'Cron Parser','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'JsonParse','cat'=>'Development','name'=>'JSON Parser','icon'=>'<svg viewBox="0 0 24 24"><path d="M5 3l-4 9 4 9M19 3l4 9-4 9M12 12h.01" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'regex-tester','cat'=>'Development','name'=>'Regex Tester','icon'=>'<svg viewBox="0 0 24 24"><path d="M17 3v18M13 3l-8 18M5 3l8 18M3 5h4M3 19h4M17 5h4M17 19h4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'chmod-calculator','cat'=>'Development','name'=>'Chmod','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'regex-cheatsheet','cat'=>'Development','name'=>'Regex Cheatsheet','icon'=>'<svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h12M4 17h8" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'git-cheatsheet','cat'=>'Development','name'=>'Git Memo','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="18" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="6" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="6" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M18 9v1a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'FileInfo','cat'=>'Development','name'=>'File Info','icon'=>'<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M14 2v6h6" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'BomClean','cat'=>'Development','name'=>'BOM Clean','icon'=>'<svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h12M3 18h6" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'PhpObfuscator','cat'=>'Development','name'=>'PHP Obfuscator','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 9l3 3-3 3M15 9l-3 3 3 3" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'SystemEnv','cat'=>'Development','name'=>'System Env','icon'=>'<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M14 2v6h6" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 13h8M8 17h5" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'SiteSucker','cat'=>'Development','name'=>'Site Sucker','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 12h8M12 8v8" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'RequestTime','cat'=>'Development','name'=>'URL Timing','icon'=>'<svg viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'SSLMake','cat'=>'Development','name'=>'SSL Make','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>'],
            ['id'=>'jwt-encoder-decoder','cat'=>'Crypto','name'=>'JWT','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'HashText','cat'=>'Crypto','name'=>'Hash Text','icon'=>'<svg viewBox="0 0 24 24"><path d="M4 7h16M7 7v10M17 7v10M4 17h16" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'EncryptDecryptText','cat'=>'Crypto','name'=>'Encryption','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'rsa-key-generator','cat'=>'Crypto','name'=>'RSA Key Gen','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 2v8M12 14v8" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'TokenGenerator','cat'=>'Crypto','name'=>'Token Gen','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="5" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'Timestamp','cat'=>'Converter','name'=>'Timestamp','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'base64-string-converter','cat'=>'Converter','name'=>'Base64','icon'=>'<svg viewBox="0 0 24 24"><path d="M10 8h4M12 8v8M5 8l-2 4 2 4M19 8l2 4-2 4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'url-encode','cat'=>'Converter','name'=>'URL Encode','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a2 2 0 0 0 0-2l-1.2-2.1" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4.6 9a2 2 0 0 0 0 2l1.2 2.1" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'escape-html','cat'=>'Converter','name'=>'Escape HTML','icon'=>'<svg viewBox="0 0 24 24"><path d="M17 13l4-4-4-4M7 11l-4 4 4 4M14.5 4l-5 16" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'websocket-sse','cat'=>'Web','name'=>'WS/SSE','icon'=>'<svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'url-parse','cat'=>'Web','name'=>'URL Parse','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 2a10 10 0 0 0-10 10" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 22a10 10 0 0 0 10-10" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'mime-types','cat'=>'Web','name'=>'MIME Types','icon'=>'<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M14 2v6h6" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'http-status-codes','cat'=>'Web','name'=>'HTTP Status','icon'=>'<svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'qr-code-generator','cat'=>'Images','name'=>'QR Code','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><path d="M14 14h3v3M17 14h.01M14 17h.01" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'wifi-qr-code-generator','cat'=>'Images','name'=>'WiFi QR','icon'=>'<svg viewBox="0 0 24 24"><path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'ImageCompress','cat'=>'Images','name'=>'Image Compress','icon'=>'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><path d="M21 15l-5-5L5 21" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'Capturer','cat'=>'Images','name'=>'Screenshot','icon'=>'<svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="13" r="4" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'PortKill','cat'=>'Development','name'=>'Port Kill','icon'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 12h8" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'ProcessKill','cat'=>'Development','name'=>'Process Kill','icon'=>'<svg viewBox="0 0 24 24"><path d="M20 12H4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 4v16" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
            ['id'=>'keycode-info','cat'=>'Development','name'=>'Keycode Info','icon'=>'<svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 10h4M14 10h4M6 14h12" fill="none" stroke="currentColor" stroke-width="2"/></svg>'],
        ];
        $this->categories = ['Code','Development','Crypto','Converter','Web','Images'];
        $this->panelMap = [
            'diff-compare'=>'diff','cron-parser'=>'cron','JsonParse'=>'json','jwt-encoder-decoder'=>'jwt',
            'HashText'=>'hash','EncryptDecryptText'=>'encrypt','Timestamp'=>'ts','base64-string-converter'=>'b64',
            'url-encode'=>'url','url-parse'=>'urlparse','escape-html'=>'html','regex-tester'=>'regex','chmod-calculator'=>'chmod',
            'TokenGenerator'=>'token','http-status-codes'=>'http','mime-types'=>'mime','BomClean'=>'bom',
            'MarkdownPreview'=>'md','websocket-sse'=>'wss','CodePlay'=>'code','CodeLibrary'=>'clib',
            'qr-code-generator'=>'qr','wifi-qr-code-generator'=>'wifi','ImageCompress'=>'img',
            'Capturer'=>'capture','rsa-key-generator'=>'rsa','FileInfo'=>'file','RequestTime'=>'timing',
            'SiteSucker'=>'suck','SSLMake'=>'ssl','PhpObfuscator'=>'obf','SystemEnv'=>'env',
            'PortKill'=>'portkill','ProcessKill'=>'prockill',
            'regex-cheatsheet'=>'regex_memo','git-cheatsheet'=>'git_memo',
            'keycode-info'=>'keycode',
        ];
    }

    public function getHtml(): string
    {
        $css = file_get_contents(__DIR__ . '/../assets/css/toolbox.css');
        $epCss = file_get_contents(__DIR__ . '/../assets/css/ep.css');
        $i18n = file_get_contents(__DIR__ . '/../assets/js/toolbox-i18n.js');
        $epUi = file_get_contents(__DIR__ . '/../assets/js/ep-ui.js');
        $panels = file_get_contents(__DIR__ . '/../assets/js/toolbox-panels.js');
        $logic = file_get_contents(__DIR__ . '/../assets/js/toolbox-logic.js');
        $gitMemo = @file_get_contents(__DIR__ . '/../assets/md/git-memo.en.md') ?: '';
        $cmCss = file_get_contents(__DIR__ . '/../assets/editor/cm-core.css');
        $cmTheme = file_get_contents(__DIR__ . '/../assets/editor/cm-theme.css');
        $cmCore = file_get_contents(__DIR__ . '/../assets/editor/cm-core.js');
        $cmShell = file_get_contents(__DIR__ . '/../assets/editor/cm-mode-shell.js');
        $cmProps = file_get_contents(__DIR__ . '/../assets/editor/cm-mode-properties.js');
        $data = json_encode($this->tools);
        $cats = json_encode($this->categories);
        $pmap = json_encode($this->panelMap);
        $tree = $this->renderTree();

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>FlyEnv Toolbox</title>
<style>{$css}</style>
<style>{$epCss}</style>
<style>{$cmCss}</style>
<style>{$cmTheme}</style></head>
<body>
<div id="app"><div class="layout">
  <script type="text/markdown" id="gitMemoRaw">{$gitMemo}</script>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">TOOLS</div>
    <div class="sidebar-tree" id="sidebarTree">{$tree}</div>
  </aside>
  <main class="main">
    <div class="toolbar">
      <button class="tb-btn" id="foldBtn" onclick="toggleSidebar()"><svg viewBox="0 0 24 24" width="20" height="20"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2"/></svg></button>
      <button class="tb-btn" onclick="goHome()"><svg viewBox="0 0 24 24" width="20" height="20"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" fill="none" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" fill="none" stroke="currentColor" stroke-width="2"/></svg></button>
      <div class="search-wrap"><input class="search-input" id="searchInput" placeholder="Search tools..." onfocus="showAllSuggestions()" oninput="doSearch(this.value)" onblur="setTimeout(function(){hideSugg();},200)"><div class="search-suggestions" id="suggestions"></div></div>
      <span class="tool-current" id="currentTool"></span>
      <button class="lang-btn" id="langBtn" onclick="toggleLang()">中</button>
    </div>
    <div class="content" id="content">
      <div id="homeView"><div id="favSection"></div><div id="allSection"></div></div>
      <div id="toolView" style="display:none"></div>
    </div>
  </main>
</div></div>
<script>{$cmCore}</script>
<script>{$cmShell}</script>
<script>{$cmProps}</script>
<script>
var TOOLS = {$data};
var CATS = {$cats};
var PMAP = {$pmap};
{$i18n}
{$epUi}
{$panels}
{$logic}
initApp();
</script>
</body></html>
HTML;
    }

    private function renderTree(): string
    {
        $h = '';
        $h .= '<div class="tree-cat">◈ Favorites</div><div class="tree-children"></div>';
        foreach ($this->categories as $cat) {
            $items = array_filter($this->tools, fn($t) => $t['cat'] === $cat);
            if (!$items) continue;
            $h .= '<div class="tree-cat">◈ '.$cat.'</div><div class="tree-children">';
            foreach ($items as $t) {
                $h .= '<div class="tree-item tree-leaf" data-id="'.$t['id'].'" onclick="openTool(\''.$t['id'].'\')">'
                    .'<span class="tree-icon">'.$t['icon'].'</span><span class="tree-label">'.$t['name'].'</span></div>';
            }
            $h .= '</div>';
        }
        return $h;
    }
}
