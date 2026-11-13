<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $request = json_decode(file_get_contents('php://input'), true, 8, JSON_THROW_ON_ERROR);
        $value = (string) ($request['value'] ?? '');
        $action = $request['action'] ?? '';

        if ($value === '') {
            throw new InvalidArgumentException('Enter a string first.');
        }
        if (strlen($value) > 100000) {
            throw new InvalidArgumentException('Input is too large.');
        }

        if ($action === 'md5') {
            $hash = md5($value);
        } elseif ($action === 'bcrypt') {
            $hash = password_hash($value, PASSWORD_BCRYPT);
        } else {
            throw new InvalidArgumentException('Unknown hash type.');
        }

        echo json_encode(['hash' => $hash], JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        http_response_code(400);
        echo json_encode(['error' => $error->getMessage()]);
    }
    exit;
}

require_once dirname(__DIR__) . '/includes/layout.php';
renderHeader('Password Tools', 'Generate secure random strings and create MD5 or bcrypt hashes.', 'password');
?>

<header class="page-heading">
    <span class="eyebrow">Password workspace</span>
    <h1>Password Tools</h1>
    <p>Generate random alphanumeric values or create hashes from a supplied string.</p>
</header>

<section class="tool-section">
    <div class="tool-box">
        <h2>Random alphanumeric generator</h2>
        <p>Uses the browser's cryptographic random source. Choose any length from 1 to 4,096.</p>
        <div class="generator-controls">
            <div class="field">
                <label for="length">Length</label>
                <input id="length" type="number" min="1" max="4096" value="32" inputmode="numeric">
            </div>
            <button class="primary" id="generate" type="button">Generate</button>
        </div>
        <div class="result-box" id="randomResult" data-placeholder="Your random value will appear here."></div>
        <div class="toolbar" style="margin-top: 12px; margin-bottom: 0"><button id="copyRandom" disabled>Copy</button></div>
    </div>
</section>

<section class="tool-section" aria-labelledby="hash-title">
    <div class="section-heading">
        <div><span class="eyebrow">Hashing</span><h2 id="hash-title">Create a hash</h2></div>
        <p>bcrypt uses a new random salt on every run.</p>
    </div>
    <div class="workspace">
        <section class="panel">
            <div class="panel-bar"><strong><label for="hashInput">Input string</label></strong><span>Processed by this PHP application</span></div>
            <textarea id="hashInput" spellcheck="false" placeholder="Enter the value to hash..."></textarea>
        </section>
        <section class="panel">
            <div class="panel-bar"><strong id="hashOutputLabel">Hash output</strong><button id="copyHash" disabled>Copy</button></div>
            <pre class="code-output" tabindex="0" aria-labelledby="hashOutputLabel"><code id="hashOutput" class="empty-output" data-placeholder="The generated hash will appear here."></code></pre>
        </section>
    </div>
    <div class="toolbar" style="margin-top: 18px">
        <button class="primary" data-hash="bcrypt">Generate bcrypt</button>
        <button data-hash="md5">Generate MD5</button>
    </div>
    <div id="status" class="status" role="status" aria-live="polite">Enter a value and choose a hash algorithm.</div>
</section>

<script>
'use strict';

const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
const randomResult = document.getElementById('randomResult');
const hashOutput = document.getElementById('hashOutput');
const statusBox = document.getElementById('status');

function secureRandomString(length) {
    const result = [];
    const limit = 256 - (256 % characters.length);
    while (result.length < length) {
        const bytes = crypto.getRandomValues(new Uint8Array(Math.min((length - result.length) * 2, 65536)));
        for (const byte of bytes) {
            if (byte < limit) result.push(characters[byte % characters.length]);
            if (result.length === length) break;
        }
    }
    return result.join('');
}

function setStatus(message, type = '') {
    statusBox.textContent = message;
    statusBox.className = 'status' + (type ? ' ' + type : '');
}

document.getElementById('generate').onclick = () => {
    const length = Number(document.getElementById('length').value);
    if (!Number.isInteger(length) || length < 1 || length > 4096) {
        randomResult.textContent = '';
        document.getElementById('copyRandom').disabled = true;
        setStatus('Length must be a whole number between 1 and 4,096.', 'error');
        return;
    }
    randomResult.textContent = secureRandomString(length);
    document.getElementById('copyRandom').disabled = false;
    setStatus('Generated ' + length.toLocaleString() + ' random characters.', 'success');
};

document.querySelectorAll('[data-hash]').forEach(button => {
    button.addEventListener('click', async () => {
        try {
            const value = document.getElementById('hashInput').value;
            if (!value.length) throw new Error('Enter a string first.');
            button.disabled = true;
            setStatus('Generating ' + button.dataset.hash + ' hash...');
            const response = await fetch(location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: button.dataset.hash, value})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Hash generation failed.');
            hashOutput.textContent = data.hash;
            document.getElementById('copyHash').disabled = false;
            setStatus(button.dataset.hash.toUpperCase() + ' hash generated.', 'success');
        } catch (error) {
            hashOutput.textContent = '';
            document.getElementById('copyHash').disabled = true;
            setStatus(error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
});

document.getElementById('copyRandom').onclick = async () => {
    await navigator.clipboard.writeText(randomResult.textContent);
    setStatus('Random value copied.', 'success');
};
document.getElementById('copyHash').onclick = async () => {
    await navigator.clipboard.writeText(hashOutput.textContent);
    setStatus('Hash copied.', 'success');
};
</script>

<?php renderFooter(); ?>
