# Dev Toolbox: Application Architecture

## System summary

Dev Toolbox is a server-rendered, multi-page PHP application with embedded vanilla JavaScript. PHP produces the document shell and handles two small JSON POST APIs; browser JavaScript performs all other transformations and updates the page directly. There is no framework, package manager, build step, database, or application state service.

```text
Browser
  |
  |-- GET /, /json/, /html/, /string/, /password/, /jwt/, /tables/
  |      PHP page + shared layout
  |      CSS from /assets/styles.css
  |      inline JavaScript runs each workspace
  |
  |-- POST /password/index.php ----> PHP md5/password_hash ----> JSON
  |
  `-- POST /jwt/index.php ---------> PHP JSON/HMAC signing -----> JSON
```

## Repository layout

```text
.
├── README.md                    Project summary and public URL
├── app-features.md              User-facing behavior and boundaries
├── app-architecture.md          Technical structure and request flows
├── version.txt                  Version displayed by the shared footer
├── public/                      Web server document root
│   ├── index.php                Landing page and tool directory
│   ├── includes/
│   │   └── layout.php           Shared header, navigation, and footer
│   ├── assets/
│   │   └── styles.css           Entire shared visual and responsive system
│   ├── json/index.php           Browser-only JSON workspace
│   ├── html/index.php           Browser-only HTML workspace
│   ├── string/index.php         Browser-only string and UUID workspace
│   ├── password/index.php       Password UI plus hashing POST endpoint
│   ├── jwt/index.php            JWT UI plus signing POST endpoint
│   └── tables/index.php         Browser-only JSON/CSV table workspace
└── .github/workflows/
    └── deployment.yaml          Release-triggered production deployment
```

The intended web document root is `public/`. This is important because `version.txt`, repository metadata, and deployment configuration should not be directly served.

## Page composition

Every GET page loads `public/includes/layout.php` and follows the same lifecycle:

1. Call `renderHeader(title, description, activeKey)`.
2. Emit page-specific HTML controls and result regions.
3. Attach page behavior through an inline `<script>` block.
4. Call `renderFooter()`.

`renderHeader()` owns the HTML document start, metadata, stylesheet reference, brand, global navigation, and opening `<main>`. It HTML-escapes the supplied title and description. The `activeKey` selects the current navigation item and adds `aria-current="page"`.

`renderFooter()` closes `<main>`, reads `version.txt` two directories above the include, renders privacy/version copy and workspace navigation, and closes the document. The current existence check tests the path variable rather than the file itself, so deployments are expected to provide a readable `version.txt`.

## Runtime responsibilities

### PHP layer

PHP is responsible for:

- Server-rendering complete HTML pages.
- Reusing the common site chrome.
- Reading the deployed version string.
- Hashing password-tool input with MD5 or bcrypt.
- Constructing and HMAC-signing JWTs.
- Returning JSON errors with HTTP status 400 for rejected POST requests.

There is no router. Each directory's `index.php` is both its page controller and, where applicable, its API endpoint. `password/index.php` and `jwt/index.php` branch on `$_SERVER['REQUEST_METHOD']`: POST requests return JSON and exit before any HTML is rendered; other requests render the UI.

### Browser layer

Each page has isolated, inline JavaScript with no shared JavaScript bundle. It uses standard platform APIs:

- `JSON`, `TextEncoder`, and `TextDecoder` for data conversion.
- `crypto.getRandomValues()` and `crypto.randomUUID()` for secure random values.
- `File.text()` for local JSON file input.
- An unrestricted `iframe.srcdoc` preview for HTML input.
- `navigator.clipboard` for copying.
- `Blob`, object URLs, and a temporary link for JSON download.
- `fetch()` for the two same-origin PHP operations.
- DOM creation and `textContent` for safe table rendering.

State is ephemeral and held in DOM values or a few page-level JavaScript variables. Reloading or navigating away discards it.

### Presentation layer

`public/assets/styles.css` is a single stylesheet containing:

- CSS custom properties for the dark color palette, borders, spacing accents, and radii.
- Shared components such as tool cards, panels, toolbars, status banners, result boxes, forms, and tables.
- JSON syntax highlight classes.
- Responsive breakpoints at 1,050 px, 760 px, and 480 px.
- Horizontal overflow handling for navigation and generated tables.
- `:focus-visible` styling and empty-state placeholders.

The layout has no external fonts, images, scripts, or stylesheets.

## Request and data flows

### Browser-only tool flow

This flow applies to JSON, HTML, strings, UUIDs, random alphanumeric values, JWT reading, and table generation.

```text
User input -> page event handler -> validation/transformation
           -> DOM output/status -> optional clipboard or file download
```

Inputs never leave the browser through application code. Local JSON files are read directly with the File API.

The JSON formatter retains a separate plain-text output value for copying and downloads while generating escaped syntax-highlight markup for display. In Beautify mode only, object braces wrap their nested display tokens in collapsible spans, allowing every object to be toggled without reparsing or changing the original formatted output. Ellipses and collapse controls exist only in the rendered DOM and never enter clipboard or download output.

The HTML viewer assigns input directly to unrestricted, same-origin inline and full-screen `iframe` elements through `srcdoc`. The full-screen version is hosted in a native modal `dialog`, closes through its button or the browser's standard `Esc` behavior, and clears its document on close. Embedded scripts, styles, forms, navigation, and external resources behave normally. This also means preview code can access same-origin browser state and interact with the parent page, so users must choose trusted input. Minification and beautification are lightweight text transformations and do not depend on a server or third-party parser.

### Hashing flow

```text
hashInput + action
  -> fetch(current URL, POST application/json)
  -> PHP JSON decode (maximum nesting depth 8)
  -> require a non-empty value of at most 100,000 bytes
  -> allow only md5 or bcrypt
  -> compute hash
  -> {"hash":"..."}
  -> render/copy in browser
```

Invalid JSON, invalid values, unknown algorithms, and processing errors are caught as `Throwable`, returned as `{ "error": "..." }`, and assigned HTTP 400.

### JWT signing flow

```text
algorithm + secret + parsed payload object
  -> fetch(current URL, POST application/json)
  -> PHP JSON decode (maximum nesting depth 16)
  -> allow HS256, HS384, or HS512
  -> require non-empty secret and object-shaped payload
  -> encode fixed header and payload with base64url
  -> HMAC-sign "header.payload"
  -> {"token":"header.payload.signature"}
  -> render/copy in browser
```

The client validates the payload before sending it, and the server repeats the structural and algorithm checks. The server is authoritative.

## Validation and output safety

- Dynamic page metadata and the footer version are passed through `htmlspecialchars()`.
- Tool output is assigned with `textContent` or constructed with DOM APIs, preventing input from becoming executable HTML.
- JSON syntax highlighting generates markup only after escaping `&`, `<`, and `>` in each token.
- Server endpoints use allowlists for hash and JWT algorithms.
- PHP JSON decoding uses `JSON_THROW_ON_ERROR`; failures are returned as controlled JSON errors.
- JWT generation accepts only an object-shaped payload. PHP associative arrays represent JSON objects, with an explicit rejection for non-empty lists.
- The password endpoint has an explicit 100,000-byte input limit; the JWT endpoint relies on infrastructure request-size limits and has no application-level byte limit.

## Privacy and trust boundaries

The application has two distinct trust boundaries:

1. Browser-only tools retain their working data in the current page.
2. Hashing and JWT signing transmit sensitive input over a same-origin request to the PHP server.

The repository contains no persistence, logging, analytics, cookies, or third-party API calls. That does not guarantee that surrounding infrastructure avoids access logs or request inspection. HTTPS and privacy-conscious web server, proxy, CDN, and cache configuration are especially important because password input and JWT secrets cross the server boundary.

The shared UI currently uses broad phrases such as “100% local” and “Everything runs locally in your browser.” Those phrases accurately describe most features but not server-side hashing or signing. Product copy should preserve this distinction.

## Dependencies and platform assumptions

The application depends only on:

- A PHP runtime with `json`, `hash`, and password-hashing support.
- A web server configured with `public/` as the document root and directory-index behavior for PHP.
- A modern browser with ES2020-era JavaScript, BigInt, Web Crypto, Encoding, Clipboard, File, Blob, and Fetch APIs.
- A secure browser context for reliable Clipboard and Web Crypto behavior in production.

There are no Composer or npm dependencies and no compilation or asset pipeline.

## Deployment architecture

Production deployment is driven by `.github/workflows/deployment.yaml` when a GitHub Release is published, except for tags ending in `-dev`.

1. GitHub Actions connects to the production VPS over SSH.
2. The server fetches tags and checks out the release tag in the configured application directory.
3. Deployment aborts if the checkout leaves a dirty working tree.
4. A second SSH step writes the release tag to `version.txt`.
5. A final request calls the configured cache-clearing endpoint with its secret token.

The workflow performs no dependency installation or build because the application has no external runtime packages or generated assets.

## Extension guidelines

To add another browser-only tool:

1. Create `public/<tool>/index.php`.
2. Include `public/includes/layout.php` with `dirname(__DIR__)`.
3. Add the route to the shared `$links` map and, if appropriate, the landing-page tool grid.
4. Add the route to the shared footer navigation.
5. Reuse the existing CSS components before adding new styles.
6. Keep untrusted output in `textContent`; escape any content that must become HTML.

To add a server operation, keep the request branch before page rendering, validate again on the server, use an explicit operation allowlist, return a consistent JSON envelope and status code, and document whether sensitive input crosses the browser boundary. For larger APIs, extracting endpoint logic from the view files would reduce coupling and make automated tests easier.

