<?php
require_once dirname(__DIR__) . '/includes/layout.php';
renderHeader('JSON Formatter', 'Beautify, minify, validate, escape, and unescape JSON locally.', 'json');
?>

<header class="page-heading">
    <span class="eyebrow">JSON workspace</span>
    <h1>JSON Formatter</h1>
    <p>Clean up, validate, and transform JSON without sending it anywhere.</p>
</header>

<div class="toolbar action-tabs" aria-label="JSON action">
    <button class="action-tab active" id="beautify" data-json-action="beautify" aria-pressed="true">Beautify</button>
    <button class="action-tab" id="minify" data-json-action="minify" aria-pressed="false">Minify</button>
    <button class="action-tab" id="validate" data-json-action="validate" aria-pressed="false">Validate</button>
    <button class="action-tab" id="unescape" data-json-action="unescape" aria-pressed="false">Unescape</button>
    <button class="action-tab" id="escape" data-json-action="escape" aria-pressed="false">Escape</button>
    <label for="indent">Indent</label>
    <select id="indent"><option value="2">2 spaces</option><option value="4">4 spaces</option><option value="tab">Tabs</option></select>
    <label class="upload">Open JSON<input id="file" type="file" accept=".json,.txt,application/json" hidden></label>
    <button id="sample">Sample</button>
    <button id="clear">Clear</button>
</div>

<div class="workspace">
    <section class="panel">
        <div class="panel-bar"><strong><label for="input">Input</label></strong><span id="inputStats">0 characters</span></div>
        <textarea id="input" spellcheck="false" placeholder='Paste your JSON here, e.g. {"hello":"world"}' aria-describedby="status"></textarea>
    </section>
    <section class="panel">
        <div class="panel-bar"><strong id="outputLabel">Output</strong><div><button id="copy" disabled>Copy</button> <button id="download" disabled>Download</button></div></div>
        <pre id="output" class="code-output" tabindex="0" aria-labelledby="outputLabel"><code id="highlighted" class="empty-output" data-placeholder="Formatted JSON will appear here."></code></pre>
    </section>
</div>

<div id="status" class="status" role="status" aria-live="polite">Paste JSON or open a file. Ctrl / Cmd + Enter runs the selected action.</div>

<script>
'use strict';

const $ = id => document.getElementById(id);
const input = $('input');
const output = $('output');
let outputText = '';
let outputMode = '';
let activeAction = 'beautify';

function setStatus(message, type = '') {
    $('status').textContent = message;
    $('status').className = 'status' + (type ? ' ' + type : '');
}

function selectOutput() {
    output.focus();
    const range = document.createRange();
    range.selectNodeContents($('highlighted'));
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
}

function highlightJSON(source) {
    return source.replace(/"(?:[^"\\]|\\.)*"|-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?|true|false|null|[{}\[\],:]/g, (token, offset) => {
        let type = 'punctuation';
        if (token[0] === '"') type = /^\s*:/.test(source.slice(offset + token.length)) ? 'key' : 'string';
        else if (token === 'null') type = 'null';
        else if (token === 'true' || token === 'false') type = 'boolean';
        else if (/^-?\d/.test(token)) type = 'number';
        const escaped = token.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return '<span class="json-' + type + '">' + escaped + '</span>';
    });
}

function renderOutput() {
    try {
        JSON.parse(outputText);
        $('highlighted').innerHTML = highlightJSON(outputText);
    } catch (error) {
        $('highlighted').textContent = outputText;
    }
}

function resetOutput() {
    outputText = '';
    outputMode = '';
    renderOutput();
    $('copy').disabled = true;
    $('download').disabled = true;
}

function update() {
    resetOutput();
    $('inputStats').textContent = input.value.length.toLocaleString() + ' characters';
    setStatus(input.value.trim() ? 'Ready to run ' + activeAction + '.' : 'Paste JSON or open a file. Ctrl / Cmd + Enter runs the selected action.');
}

function validateInput() {
    if (!input.value.trim()) throw new Error('Enter some JSON first.');
    JSON.parse(input.value);
}

// Format original tokens so large numbers, duplicate keys, key order,
// exponent notation, and the original string escapes stay intact.
function formatJSON(source, indent) {
    const tokens = source.match(/"(?:[^"\\]|\\.)*"|-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?|true|false|null|[{}\[\],:]/g) || [];
    if (indent === null) return tokens.join('');
    let result = '';
    let depth = 0;
    const line = () => '\n' + indent.repeat(depth);

    for (let i = 0; i < tokens.length; i++) {
        const token = tokens[i];
        if (token === '{' || token === '[') {
            result += token;
            if (tokens[i + 1] !== (token === '{' ? '}' : ']')) {
                depth++;
                result += line();
            }
        } else if (token === '}' || token === ']') {
            if (tokens[i - 1] !== (token === '}' ? '{' : '[')) {
                depth--;
                result += line();
            }
            result += token;
        } else if (token === ',') result += token + line();
        else if (token === ':') result += ': ';
        else result += token;
    }
    return result;
}

function run(mode) {
    try {
        validateInput();
        if (mode === 'validate') {
            setStatus('Valid JSON.', 'success');
            return;
        }
        const indent = $('indent').value;
        outputText = formatJSON(input.value, mode === 'minify' ? null : indent === 'tab' ? '\t' : ' '.repeat(Number(indent)));
        outputMode = mode;
        renderOutput();
        $('copy').disabled = false;
        $('download').disabled = false;
        setStatus('Valid JSON · ' + (mode === 'minify' ? 'Minified' : 'Beautified') + ' · ' + outputText.length.toLocaleString() + ' characters', 'success');
    } catch (error) {
        resetOutput();
        setStatus('Invalid JSON: ' + error.message, 'error');
    }
}

function transform(mode) {
    try {
        if (!input.value.length) throw new Error('Enter some text first.');
        if (mode === 'escape') {
            outputText = JSON.stringify(input.value).slice(1, -1);
        } else {
            let parsed;
            try { parsed = JSON.parse(input.value); }
            catch (error) { parsed = JSON.parse('"' + input.value + '"'); }
            if (typeof parsed !== 'string') throw new Error('Input must be an escaped JSON string.');
            outputText = parsed;
        }
        outputMode = mode;
        renderOutput();
        $('copy').disabled = false;
        $('download').disabled = false;
        setStatus((mode === 'escape' ? 'Escaped' : 'Unescaped') + ' · ' + outputText.length.toLocaleString() + ' characters', 'success');
    } catch (error) {
        resetOutput();
        setStatus('Could not ' + mode + ': ' + error.message, 'error');
    }
}

function executeAction(mode) {
    if (mode === 'escape' || mode === 'unescape') transform(mode);
    else run(mode);
}

function selectAction(button) {
    activeAction = button.dataset.jsonAction;
    document.querySelectorAll('[data-json-action]').forEach(option => {
        const selected = option === button;
        option.classList.toggle('active', selected);
        option.setAttribute('aria-pressed', String(selected));
    });
}

input.addEventListener('input', update);
document.querySelectorAll('[data-json-action]').forEach(button => {
    button.addEventListener('click', () => {
        selectAction(button);
        executeAction(activeAction);
    });
});
$('indent').onchange = () => {
    if (activeAction === 'beautify') executeAction(activeAction);
};
$('clear').onclick = () => { input.value = ''; update(); input.focus(); };
$('sample').onclick = () => {
    input.value = '{"name":"Dev Toolbox","active":true,"items":[{"id":1,"tags":["offline","simple"]},{"id":2,"value":null}],"largeNumber":9007199254740993}';
    update();
    selectAction($('beautify'));
    executeAction(activeAction);
};
$('file').onchange = async event => {
    const file = event.target.files[0];
    if (!file) return;
    try {
        input.value = (await file.text()).replace(/^\uFEFF/, '');
        update();
        selectAction($('beautify'));
        executeAction(activeAction);
    } catch (error) {
        setStatus('Could not read file: ' + error.message, 'error');
    } finally {
        event.target.value = '';
    }
};
$('copy').onclick = async () => {
    try {
        await navigator.clipboard.writeText(outputText);
        setStatus('Output copied.', 'success');
    } catch (error) {
        selectOutput();
        setStatus('Press Ctrl / Cmd + C to copy the selected output.');
    }
};
$('download').onclick = () => {
    const url = URL.createObjectURL(new Blob([outputText], {type: 'application/json;charset=utf-8'}));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'formatted.json';
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    setStatus('JSON downloaded.', 'success');
};
document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        executeAction(activeAction);
    }
});
</script>

<?php renderFooter(); ?>
