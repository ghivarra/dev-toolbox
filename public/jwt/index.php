<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $request = json_decode(file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR);
        $algorithm = $request['algorithm'] ?? '';
        $secret = (string) ($request['secret'] ?? '');
        $payload = $request['payload'] ?? null;
        $hashes = ['HS256' => 'sha256', 'HS384' => 'sha384', 'HS512' => 'sha512'];

        if (!isset($hashes[$algorithm])) throw new InvalidArgumentException('Unsupported algorithm.');
        if ($secret === '') throw new InvalidArgumentException('Enter a secret key.');
        if (!is_array($payload) || ($payload !== [] && array_is_list($payload))) throw new InvalidArgumentException('Payload must be a JSON object.');

        $base64Url = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = json_encode(['alg' => $algorithm, 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $unsigned = $base64Url($header) . '.' . $base64Url($body);
        $signature = hash_hmac($hashes[$algorithm], $unsigned, $secret, true);
        echo json_encode(['token' => $unsigned . '.' . $base64Url($signature)], JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        http_response_code(400);
        echo json_encode(['error' => $error->getMessage()]);
    }
    exit;
}

require_once dirname(__DIR__) . '/includes/layout.php';
renderHeader('JWT Tools', 'Read JSON Web Tokens and generate HMAC-signed JWTs locally.', 'jwt');
?>

<header class="page-heading">
    <span class="eyebrow">JWT workspace</span>
    <h1>JWT Tools</h1>
    <p>Inspect token contents or create a signed JWT with your own secret key.</p>
</header>

<section class="tool-section">
    <div class="tool-box">
        <h2>Read JWT</h2>
        <p>Decodes the header and payload only. It does not verify the token signature.</p>
        <div class="field">
            <label for="tokenInput">Encoded token</label>
            <textarea id="tokenInput" spellcheck="false" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."></textarea>
        </div>
        <button class="primary" id="readToken">Read token</button>
        <div class="compact-grid" style="margin-top: 18px">
            <div><label for="decodedHeader">Header</label><pre class="result-box" id="decodedHeader" data-placeholder="Decoded header"></pre></div>
            <div><label for="decodedPayload">Payload</label><pre class="result-box" id="decodedPayload" data-placeholder="Decoded payload"></pre></div>
        </div>
    </div>
</section>

<section class="tool-section" aria-labelledby="generate-title">
    <div class="section-heading">
        <div><span class="eyebrow">Signing</span><h2 id="generate-title">Generate JWT</h2></div>
        <p>Supports HMAC SHA-256, SHA-384, and SHA-512 through this PHP application.</p>
    </div>
    <div class="compact-grid">
        <div class="tool-box">
            <div class="field">
                <label for="algorithm">Algorithm</label>
                <select id="algorithm"><option value="HS256">HS256</option><option value="HS384">HS384</option><option value="HS512">HS512</option></select>
            </div>
            <div class="field">
                <label for="secret">Secret key</label>
                <input id="secret" type="password" autocomplete="off" placeholder="Enter a signing secret">
            </div>
            <div class="field">
                <label for="payload">Payload JSON</label>
                <textarea id="payload" spellcheck="false">{
  "sub": "1234567890",
  "name": "Dev Toolbox",
  "admin": true
}</textarea>
            </div>
            <button class="primary" id="generateToken">Generate token</button>
        </div>
        <div class="tool-box">
            <h2>Signed token</h2>
            <p>Keep the secret private. Anyone with it can create valid tokens.</p>
            <div class="result-box" id="tokenOutput" data-placeholder="Your generated JWT will appear here."></div>
            <div class="toolbar" style="margin-top: 12px; margin-bottom: 0"><button id="copyToken" disabled>Copy token</button></div>
        </div>
    </div>
</section>

<div id="status" class="status" role="status" aria-live="polite">Token operations run locally in your browser.</div>

<script>
'use strict';

const encoder = new TextEncoder();
const decoder = new TextDecoder();
const statusBox = document.getElementById('status');

function setStatus(message, type = '') {
    statusBox.textContent = message;
    statusBox.className = 'status' + (type ? ' ' + type : '');
}

function base64UrlEncode(value) {
    const bytes = typeof value === 'string' ? encoder.encode(value) : value;
    let binary = '';
    for (let i = 0; i < bytes.length; i += 8192) {
        binary += String.fromCharCode(...bytes.subarray(i, i + 8192));
    }
    return btoa(binary).replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');
}

function base64UrlDecode(value) {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized + '='.repeat((4 - normalized.length % 4) % 4);
    const binary = atob(padded);
    return decoder.decode(Uint8Array.from(binary, character => character.charCodeAt(0)));
}

document.getElementById('readToken').onclick = () => {
    try {
        const parts = document.getElementById('tokenInput').value.trim().split('.');
        if (parts.length !== 3 || parts.some(part => !part)) throw new Error('A JWT must contain three non-empty sections.');
        const header = JSON.parse(base64UrlDecode(parts[0]));
        const payload = JSON.parse(base64UrlDecode(parts[1]));
        document.getElementById('decodedHeader').textContent = JSON.stringify(header, null, 2);
        document.getElementById('decodedPayload').textContent = JSON.stringify(payload, null, 2);
        setStatus('Token decoded. Signature not verified.', 'success');
    } catch (error) {
        document.getElementById('decodedHeader').textContent = '';
        document.getElementById('decodedPayload').textContent = '';
        setStatus('Could not read token: ' + error.message, 'error');
    }
};

document.getElementById('generateToken').onclick = async () => {
    try {
        const algorithm = document.getElementById('algorithm').value;
        const secret = document.getElementById('secret').value;
        if (!secret) throw new Error('Enter a secret key.');
        const payload = JSON.parse(document.getElementById('payload').value);
        if (!payload || Array.isArray(payload) || typeof payload !== 'object') throw new Error('Payload must be a JSON object.');

        const response = await fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({algorithm, secret, payload})
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Token generation failed.');
        document.getElementById('tokenOutput').textContent = data.token;
        document.getElementById('copyToken').disabled = false;
        setStatus(algorithm + ' token generated.', 'success');
    } catch (error) {
        document.getElementById('tokenOutput').textContent = '';
        document.getElementById('copyToken').disabled = true;
        setStatus('Could not generate token: ' + error.message, 'error');
    }
};

document.getElementById('copyToken').onclick = async () => {
    await navigator.clipboard.writeText(document.getElementById('tokenOutput').textContent);
    setStatus('Token copied.', 'success');
};
</script>

<?php renderFooter(); ?>
