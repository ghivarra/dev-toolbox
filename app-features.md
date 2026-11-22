# Dev Toolbox: Application Features

## Overview

Dev Toolbox is a small, dependency-free web application that groups five everyday developer utilities behind a shared interface. The application is published at <https://dev.ghivarra.com> and is designed for quick, one-off transformations without accounts, tracking, or persistent storage.

Most operations run in the browser. Two operations—password hashing and JWT signing—send their inputs to the same PHP application for processing. The code does not persist those inputs, but they are not browser-only operations.

## Shared experience

- Landing page with direct access to all five workspaces.
- Shared navigation with an active-page indicator.
- Responsive dark interface for desktop, tablet, and mobile widths.
- Accessible labels, visible keyboard focus, live status messages, and semantic tables.
- Clipboard actions for generated or transformed values.
- No account, database, cookies, analytics, or third-party browser requests in the current implementation.
- Footer version read from `version.txt`.

## JSON Formatter

The JSON workspace accepts pasted text or a local `.json`/`.txt` file and provides:

- Beautification with 2 spaces, 4 spaces, or tabs.
- Minification.
- Syntax validation with the browser's JSON parser.
- JSON string escaping and unescaping.
- Syntax-highlighted JSON output.
- Input and output character counts.
- A built-in sample document.
- Copy and `.json` download actions.
- `Ctrl+Enter` or `Cmd+Enter` to run the selected action.

Formatting is token-based after validation. This deliberately preserves source details that a parse-and-reserialize implementation could change, including duplicate object keys, key order, large integer text, exponent notation, and original string escapes.

## String Tools

The string workspace provides:

- UTF-8-safe Base64 encoding and decoding.
- URL component encoding and decoding; decoding also treats `+` as a space.
- Locale-aware uppercase and lowercase conversion.
- Live character, word, and UTF-8 byte counts.
- Copy-to-clipboard output.
- `Ctrl+Enter` or `Cmd+Enter` to run the selected conversion.
- UUID v4 generation through `crypto.randomUUID()`.
- UUID v7 generation using the current millisecond timestamp and cryptographically random bytes.

Invalid Base64, invalid UTF-8, and malformed URL escapes are reported in the status area instead of producing partial output.

## Password Tools

### Random value generation

- Generates alphanumeric values from `A-Z`, `a-z`, and `0-9`.
- Supports lengths from 1 through 4,096 characters.
- Uses `crypto.getRandomValues()` and rejection sampling to avoid modulo bias.
- Runs fully in the browser and supports copying the result.

### Hash generation

- Generates bcrypt hashes with PHP's `password_hash(..., PASSWORD_BCRYPT)`.
- Generates MD5 digests with PHP's `md5()`.
- Accepts non-empty inputs up to 100,000 bytes on the server.
- Sends the value and chosen algorithm as JSON in a same-origin POST to `/password/index.php` (or the current equivalent URL).
- Returns either `{ "hash": "..." }` or an HTTP 400 JSON error.

Bcrypt uses a new random salt on every run, so the same input normally produces a different hash each time. MD5 is included as a general-purpose legacy digest and should not be used to store passwords.

## JWT Tools

### Token reader

- Requires the JWT compact form with three non-empty sections.
- Base64url-decodes and parses the header and payload in the browser.
- Pretty-prints both JSON objects.
- Does **not** verify the signature, claims, expiration, issuer, or audience.

### Token generator

- Supports `HS256`, `HS384`, and `HS512`.
- Requires a secret and a payload that is a JSON object rather than a list or scalar.
- Creates a standard `{ "alg": ..., "typ": "JWT" }` header.
- Preserves Unicode and unescaped slashes in the serialized payload.
- Sends the algorithm, secret, and payload as JSON in a same-origin POST to `/jwt/index.php` (or the current equivalent URL).
- Signs with PHP `hash_hmac()` and returns the compact JWT as JSON.
- Supports copying the generated token.

The signing secret and payload are processed by the server even though they are not stored by the application. The reader remains browser-only.

## Table Generator

The table workspace converts either JSON or CSV into an HTML preview.

### JSON input

- Requires a non-empty top-level array.
- For arrays of objects, creates the union of all discovered object keys as columns.
- For arrays of arrays, creates numbered columns based on the widest row.
- For arrays of scalar or mixed values, creates a single `value` column.
- Renders nested objects and arrays as compact JSON inside cells.
- Displays `null` explicitly and missing values as empty text.

### CSV input

- Uses the first row as column headers and requires at least one data row.
- Replaces empty header names with `Column N`.
- Supports comma delimiters, quoted fields, escaped double quotes, embedded newlines, and both LF and CRLF line endings.
- Removes a leading UTF-8 byte-order mark.
- Fills missing cells with empty text.

Both sources show row and column counts. Tables are assembled with DOM APIs and `textContent`, so input values are displayed as text rather than interpreted as HTML.

## Processing and privacy summary

| Capability | Processing location | Network request | Persistence |
| --- | --- | --- | --- |
| JSON formatting, validation, escaping | Browser | None | None |
| String conversions and counters | Browser | None | None |
| UUID generation | Browser | None | None |
| Random alphanumeric generation | Browser | None | None |
| JWT header/payload reading | Browser | None | None |
| JSON/CSV table preview | Browser | None | None |
| MD5 and bcrypt hashing | PHP server | Same-origin POST | None in application code |
| HMAC JWT signing | PHP server | Same-origin POST | None in application code |

“No persistence” describes the repository code. Web server, reverse proxy, CDN, or infrastructure logging is outside this application's implementation and should be configured consistently with the published privacy promise.

## Current boundaries

- There is no saved history, synchronization, or server-side user state.
- There are no user accounts or authorization controls.
- Files opened in the JSON tool are read with the browser File API and are not uploaded.
- Generated output exists only in the page until copied, downloaded, or the page is closed.
- Clipboard support depends on browser permissions and a secure context. The JSON tool provides manual selection as a fallback; other copy actions do not.
- JWT verification, asymmetric signing, custom JWT headers, and claim validation are not implemented.
- CSV export, delimiter selection, and spreadsheet parsing are not implemented.

