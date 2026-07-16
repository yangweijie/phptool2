# CODEBUDDY.md

Guidance for working in this repository (FlyEnv Toolbox — native / self-drawn version).

## Commands

**Run the desktop app (native, self-drawn)**
`php85 flyenv-web.php` — opens a windowed GUI (FlyEnv Toolbox) built entirely on
the `Yangweijie\Ui2` Surface + widget framework (no WebView). Needs PHP 8.5+,
the LibUI FFI extension, and the native pebview/libui libs. The default `php`
here is 8.4 and fails Composer's platform check; use `php85`.

**PHP tests (Pest)**
`php85 vendor/bin/pest` runs every `tests/Unit/*Test.php`. Single file:
`php85 vendor/bin/pest tests/Unit/NativeCatalogTest.php`.

**JS tests (Vitest + jsdom)** — exercise the legacy webview frontend only:
`npm test` (or `npx vitest run tests/JS/toolbox.test.js`).

**PHP style**: `vendor/bin/pint` (no composer script). No JS linter.

**Build native libs (caveat)**: see the caveat in Architecture about
missing `cli.php`/`bridge/` and the native libs produced by `composer build`.

## Architecture

Hybrid desktop app whose UI is **self-drawn on a canvas** (GPUI-style: one or
more `Surface` Areas + own layout/focus/event routing), with pure-PHP backend
helpers. No WebView, no async JS bridge.

### Layer 1 — App shell (`app/Native/NativeApp.php`)
Builds the window as a **single Surface** (the framework's intended
"one canvas, own layout, own event routing" model — the Surface docblock
says it is the stretchy child of a Box, not a peer of one). The whole UI —
a self-drawn top bar (title + theme toggle), a left sidebar (`ScrollViewControl`
holding the tool tree from `Catalog`) and a right content column — is one
`LayoutNode` tree: `column(topbar, row(sidebar, contentCol))`. The sidebar is
fixed at 240px; the content column has `grow: 1` and fills the rest.
Selecting a tool swaps the content column's children (`openTool()` →
`contentCol->children = [$panelNode]` → `refreshFocusables()` + `redraw()`).
Putting two Surfaces side-by-side in a native `Build::hbox` is unstable
because a libui `Area` has no reliable intrinsic width in a Box — that's
why the old two-Surface design flickered between "sidebar fills" and
"content fills".

### Layer 2 — Catalog (`app/Native/Catalog.php`)
Holds the tool list (id / cat / name / icon), the categories, and a `panelKey()`
map. Implemented tools route to a native panel; everything else falls back to a
"coming soon" `PlaceholderPanel`. **All 38 tools are listed** so the sidebar is
complete; porting a tool = adding a `Panel` + registering it in `NativeApp`.

### Layer 3 — Panels (`app/Native/Panels/*.php`)
One class per tool implementing `App\Native\Panel::build(Surface, key, w, h)`:
it returns the panel's root node (usually a `ScrollViewControl` sized to the
content area) and wires all of its own event handlers on the content Surface.
Every interactive node id is **prefixed with the panel key** so handlers from a
previously-opened panel never collide. `app/Native/Ui.php` has small builders
(label / button / textField / row / column) to cut boilerplate.

### Layer 4 — Backend (`app/Native/Backend.php`)
Pure-PHP computations the panels call synchronously (hash, base64, url, json,
timestamp, chmod, diff, fileInfo, and JWT/markdown via `App\JwtHelper` /
`App\MarkdownHelper`). No async bridge — the Surface app runs synchronously.

### Support classes (`app/`)
- `JwtHelper` (final, static): base64url + HS256/384/512 + `none`.
- `MarkdownHelper`: Parsedown wrapper.
- `SiteSucker`: website downloader engine (BFS crawl, state in a temp JSON).
  Not yet wired into a native panel.

### Legacy webview files (kept for tests only)
`app/FlyEnvWebApp.php` (HTML generator) and `assets/` (CSS/JS SPA) are **no
longer the running app** — they remain so `tests/Unit/FlyEnvWebAppTest.php` and
the JS suite keep passing. Do not extend them for new features; add native
panels instead.

### Dependencies
Composer deps: `chillerlan/php-qrcode`, `erusev/parsedown`, `illuminate/process`,
`kingbes/libui` (FFI native UI), `monolog`, `yangweijie/ui2` (Surface + widgets).
`yangweijie/ui2` is installed from a local **path repository** (composer
`repositories.path` → `../HelgeSverre-libui-sdk`) and **symlinked** into
`vendor/yangweijie/ui2`, so edits to the framework source there are picked up
after a redraw / app restart.
`assets/php-obfuscator/` is a vendored yakpro-po (263 files). Tests: `pestphp/pest`,
`mockery/mockery`, `laravel/pint` (PHP); `vitest` + `jsdom` (JS).

## Adding a native tool
1. Add the tool to `Catalog::$tools` and a `panelKey()` entry.
2. Create `app/Native/Panels/<Key>Panel.php` implementing `Panel`.
3. Register it in `NativeApp::__construct()`'s `$this->panels` map.
4. Add backend logic to `Backend` if needed (or reuse `JwtHelper`/`MarkdownHelper`).

## Testing
- PHP (Pest): `tests/Unit/*Test.php`. `FlyEnvWebAppTest` validates the legacy
  catalog; `NativeCatalogTest` validates the native catalog + `Backend`.
- JS (Vitest, jsdom): `tests/JS/toolbox.test.js` (legacy frontend only).

## Caveats (verify before relying on build steps)
`composer.json` `bin` and `box.json` `main` point at **`cli.php`**, and
`composer build`/`build:bridge` expect a **`bridge/`** directory — neither
exists in the repo today. The working entry is `flyenv-web.php` (native).
Running the app requires the native LibUI/pebview libraries present, which the
build step produces.
