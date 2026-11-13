<?php
require_once dirname(__DIR__) . '/includes/layout.php';
renderHeader('String Tools', 'Count, convert, encode, decode, and transform strings locally.', 'string');
?>

<header class="page-heading">
    <span class="eyebrow">String workspace</span>
    <h1>String Tools</h1>
    <p>One text workspace for everyday conversions, plus secure UUID generators.</p>
</header>

<div class="toolbar action-tabs" aria-label="String action">
    <button class="action-tab active" data-action="base64-encode" aria-pressed="true">String → Base64</button>
    <button class="action-tab" data-action="base64-decode" aria-pressed="false">Base64 → String</button>
    <button class="action-tab" data-action="url-encode" aria-pressed="false">URL Encode</button>
    <button class="action-tab" data-action="url-decode" aria-pressed="false">URL Decode</button>
    <button class="action-tab" data-action="uppercase" aria-pressed="false">Uppercase</button>
    <button class="action-tab" data-action="lowercase" aria-pressed="false">Lowercase</button>
    <button id="clear">Clear</button>
</div>

<div class="workspace">
    <section class="panel">
        <div class="panel-bar"><strong><label for="input">Input</label></strong><span>Updates as you type</span></div>
        <textarea id="input" spellcheck="false" placeholder="Type or paste text here..." aria-describedby="counter status"></textarea>
        <div class="counter" id="counter"><strong>0</strong> characters · <strong>0</strong> words · <strong>0</strong> bytes</div>
    </section>
    <section class="panel">
        <div class="panel-bar"><strong id="outputLabel">Output</strong><button id="copy" disabled>Copy</button></div>
        <pre class="code-output" tabindex="0" aria-labelledby="outputLabel"><code id="output" class="empty-output" data-placeholder="Converted text will appear here."></code></pre>
    </section>
</div>

<div id="status" class="status" role="status" aria-live="polite">Choose a conversion. Ctrl / Cmd + Enter runs the selected action.</div>

<section class="tool-section" aria-labelledby="uuid-title">
    <div class="section-heading">
        <div><span class="eyebrow">Identifiers</span><h2 id="uuid-title">UUID generators</h2></div>
        <p>Generated with the browser's cryptographic random source.</p>
    </div>
    <div class="compact-grid">
        <article class="tool-box">
            <h2>UUID v4</h2>
            <p>A fully random, universally unique identifier.</p>
            <button class="primary" id="uuid4">Generate UUID v4</button>
            <div class="result-box" id="uuid4Result" data-placeholder="Your UUID v4 will appear here."></div>
        </article>
        <article class="tool-box">
            <h2>UUID v7</h2>
            <p>A time-ordered UUID with millisecond precision.</p>
            <button class="primary" id="uuid7">Generate UUID v7</button>
            <div class="result-box" id="uuid7Result" data-placeholder="Your UUID v7 will appear here."></div>
        </article>
    </div>
</section>

<script>
'use strict';

const input = document.getElementById('input');
const output = document.getElementById('output');
const statusBox = document.getElementById('status');
let activeAction = 'base64-encode';

function setStatus(message, type = '') {
    statusBox.textContent = message;
    statusBox.className = 'status' + (type ? ' ' + type : '');
}

function updateCounter() {
    const text = input.value;
    const words = text.trim() ? text.trim().split(/\s+/u).length : 0;
    const bytes = new TextEncoder().encode(text).length;
    document.getElementById('counter').innerHTML = '<strong>' + Array.from(text).length.toLocaleString() + '</strong> characters · <strong>' + words.toLocaleString() + '</strong> words · <strong>' + bytes.toLocaleString() + '</strong> bytes';
}

function bytesToBase64(bytes) {
    let binary = '';
    for (let i = 0; i < bytes.length; i += 8192) {
        binary += String.fromCharCode(...bytes.subarray(i, i + 8192));
    }
    return btoa(binary);
}

function base64ToString(value) {
    const normalized = value.replace(/\s+/g, '');
    const binary = atob(normalized);
    const bytes = Uint8Array.from(binary, character => character.charCodeAt(0));
    return new TextDecoder('utf-8', {fatal: true}).decode(bytes);
}

function transform(action) {
    try {
        const value = input.value;
        if (!value.length) throw new Error('Enter some text first.');
        const conversions = {
            'base64-encode': () => bytesToBase64(new TextEncoder().encode(value)),
            'base64-decode': () => base64ToString(value),
            'url-encode': () => encodeURIComponent(value),
            'url-decode': () => decodeURIComponent(value.replace(/\+/g, ' ')),
            'uppercase': () => value.toLocaleUpperCase(),
            'lowercase': () => value.toLocaleLowerCase()
        };
        output.textContent = conversions[action]();
        document.getElementById('copy').disabled = false;
        setStatus('Conversion complete · ' + Array.from(output.textContent).length.toLocaleString() + ' characters', 'success');
    } catch (error) {
        output.textContent = '';
        document.getElementById('copy').disabled = true;
        setStatus('Could not convert: ' + error.message, 'error');
    }
}

function selectAction(button) {
    activeAction = button.dataset.action;
    document.querySelectorAll('[data-action]').forEach(option => {
        const selected = option === button;
        option.classList.toggle('active', selected);
        option.setAttribute('aria-pressed', String(selected));
    });
}

function uuidV7() {
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    let timestamp = BigInt(Date.now());
    for (let i = 5; i >= 0; i--) {
        bytes[i] = Number(timestamp & 255n);
        timestamp >>= 8n;
    }
    bytes[6] = 0x70 | (bytes[6] & 0x0f);
    bytes[8] = 0x80 | (bytes[8] & 0x3f);
    const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    return hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' + hex.slice(16, 20) + '-' + hex.slice(20);
}

input.addEventListener('input', updateCounter);
document.querySelectorAll('[data-action]').forEach(button => {
    button.addEventListener('click', () => {
        selectAction(button);
        transform(activeAction);
    });
});
document.getElementById('clear').onclick = () => {
    input.value = '';
    output.textContent = '';
    document.getElementById('copy').disabled = true;
    updateCounter();
    setStatus('Choose a conversion. Ctrl / Cmd + Enter runs the selected action.');
    input.focus();
};
document.getElementById('copy').onclick = async () => {
    await navigator.clipboard.writeText(output.textContent);
    setStatus('Output copied.', 'success');
};
document.getElementById('uuid4').onclick = () => {
    document.getElementById('uuid4Result').textContent = crypto.randomUUID();
};
document.getElementById('uuid7').onclick = () => {
    document.getElementById('uuid7Result').textContent = uuidV7();
};
document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        transform(activeAction);
    }
});
</script>

<?php renderFooter(); ?>
