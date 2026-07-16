# AGENTS.md — FlyEnv Toolbox (PHP Tools)

## What this is

A self-drawn desktop GUI toolkit written in PHP using `kingbes/libui` (FFI native UI bindings) + `yangweijie/ui2` (Surface + widget layout framework). The whole UI is painted on a canvas — no WebView, no async JS bridge. 38 tools across 6 categories, each a native panel in the app.

## Entrypoint

```
php flyenv-web.php
```

Requires PHP 8.2+, the [LibUI FFI extension](https://github.com/kingbes/libui), and native pebview/libui shared libs. The automation server (port 18765) is enabled when `UI2_AUTOMATION=true` is set.

## Commands

| What | Command |
|------|---------|
| Run the app | `php flyenv-web.php` |
| All PHP tests | `composer test` (runs `vendor/bin/pest`) |
| Single PHP test | `php vendor/bin/pest tests/Unit/<TestName>.php` |
| All JS tests | `npm test` (runs `vitest run`) |
| All tests | `npm run test:all` |
| PHP style | `vendor/bin/pint` (Laravel Pint, no config file — uses defaults) |
| Build native libs | `composer build` (pebview + bridge + ime per-platform C compilation) |
| Build PHAR | `composer build:phar` (outputs `builds/tools.phar`, configured in `box.json`) |
| Build binary | `composer build:binary` |
| Install static PHP binary toolchain | `composer install:spc` |

## Architecture (`app/Native/`)

```
flyenv-web.php          → entrypoint, bootstraps FFI + NativeApp
app/Native/
  NativeApp.php         → app shell: window, Surface, topbar, sidebar, content area
  Catalog.php           → singleton: 38 tools, categories, favorites (persisted to JSON)
  Panel.php             → interface: build(Surface, key, width, height): LayoutNode
  Backend.php           → pure-PHP logic called by panels (hash, base64, jwt, diff, json, …)
  Ui.php                → helper builders (label, button, textField, row, column)
  WindowHolder.php      → static Window ref for native file dialogs
  PlaceholderPanel.php  → fallback for tools without a native panel yet
  Panels/*.php          → one class per tool, each implementing Panel
```

**All event handlers run synchronously** — no async bridge. Backend is pure PHP, no I/O or process forks (except `illuminate/process` for specific panels).

## Adding a new tool panel

1. Add the tool entry to `Catalog::$tools` and a `panelKey()` mapping.
2. Create `app/Native/Panels/<Key>Panel.php` implementing `App\Native\Panel`.
3. Register it in `NativeApp::__construct()`'s `$this->panels` map.
4. Add backend logic to `Backend` if needed.
5. Every interactive node ID must be prefixed with the panel key (e.g. `{$key}:enc`) to prevent handler collisions when panels are swapped.

## Testing quirks

- **PHP tests (Pest)** exercise only `Catalog` + `Backend` — the Surface/GUI layer needs native libs and is not tested headlessly. Test files in `tests/Unit/`.
- **JS tests (Vitest + jsdom)** cover the **legacy webview frontend** only (`tests/JS/toolbox.test.js`), which is no longer the running app.
- **Automation tests** use the native app's built-in MCP automation server (port 18765), which exposes click/drag/inspect/tree/rect actions. The `UI2_AUTOMATION=true` env var must be set for automation mode.
- No CI workflows exist.
- No JS linter configured.

## Dependencies worth knowing

- `yangweijie/ui2` is a **local path repository**: `composer.json` has `repositories.path` → `../HelgeSverre-libui-sdk`. It is symlinked into `vendor/yangweijie/ui2`, so edits to framework source are picked up after app restart (no reinstall needed).
- `post-autoload-dump` runs `vendor/yangweijie/ui2/patch.php`.
- Composer prefers `kingbes/libui` from source (git clone).
- `assets/php-obfuscator/` is a vendored copy of yakpro-po (263 files).

## Legacy webview files (do not extend)

`app/FlyEnvWebApp.php`, `assets/` (CSS/JS/editor), and all `tests/JS/` files belong to the **old webview version**. They are kept so existing tests pass. New features go into `app/Native/Panels/` — do not extend the webview code.

## Build caveats

- `composer.json` `bin` and `box.json` `main` point at `cli.php` but this file does not exist in the repo. The real entry is `flyenv-web.php`.
- `composer build` expects a `bridge/` directory — also absent. Building native libs has not been validated on this checkout.
