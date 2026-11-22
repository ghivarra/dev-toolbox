<?php
require_once dirname(__DIR__) . '/includes/layout.php';
renderHeader('HTML Tools', 'Preview, minify, and beautify HTML in your browser.', 'html');
?>

<header class="page-heading">
    <span class="eyebrow">HTML workspace</span>
    <h1>HTML Tools</h1>
    <p>Preview markup safely, reduce its size, or turn compact HTML into readable source.</p>
</header>

<div class="toolbar action-tabs" aria-label="HTML action">
    <button class="action-tab active" data-html-action="viewer" aria-pressed="true">Viewer</button>
    <button class="action-tab" data-html-action="minify" aria-pressed="false">Minify</button>
    <button class="action-tab" data-html-action="beautify" aria-pressed="false">Beautify</button>
    <label for="indent">Indent</label>
    <select id="indent"><option value="2">2 spaces</option><option value="4">4 spaces</option><option value="tab">Tabs</option></select>
    <label class="upload">Open HTML<input id="file" type="file" accept=".html,.htm,.txt,text/html" hidden></label>
    <button id="sample">Sample</button>
    <button id="clear">Clear</button>
</div>

<div class="workspace html-workspace viewer-active" id="workspace">
    <section class="panel">
        <div class="panel-bar"><strong><label for="input">HTML input</label></strong><span id="inputStats">0 characters</span></div>
        <textarea id="input" spellcheck="false" placeholder="Paste HTML here..." aria-describedby="status"></textarea>
    </section>
    <section class="panel">
        <div class="panel-bar"><strong id="outputLabel">Live preview</strong><div><button id="fullscreen" disabled>Full screen</button> <button id="copy" disabled>Copy</button> <button id="download" disabled>Download</button></div></div>
        <iframe id="preview" class="html-preview" title="HTML preview"></iframe>
        <pre id="codeOutput" class="code-output" tabindex="0" aria-labelledby="outputLabel" hidden><code id="output" class="empty-output" data-placeholder="Transformed HTML will appear here."></code></pre>
    </section>
</div>

<dialog id="previewDialog" class="preview-dialog" aria-labelledby="previewDialogTitle">
    <div class="preview-dialog-bar">
        <strong id="previewDialogTitle">Full-screen HTML preview</strong>
        <button id="closePreview" type="button">Close</button>
    </div>
    <iframe id="fullscreenPreview" class="fullscreen-preview" title="Full-screen HTML preview"></iframe>
</dialog>

<div id="status" class="status" role="status" aria-live="polite">Enter HTML to render a live preview. Embedded scripts and styles are allowed. Ctrl / Cmd + Enter runs the selected action.</div>

<script>
'use strict';

const input = document.getElementById('input');
const output = document.getElementById('output');
const preview = document.getElementById('preview');
const previewDialog = document.getElementById('previewDialog');
const fullscreenPreview = document.getElementById('fullscreenPreview');
const codeOutput = document.getElementById('codeOutput');
const statusBox = document.getElementById('status');
let activeAction = 'viewer';
let outputText = '';

function setStatus(message, type = '') {
    statusBox.textContent = message;
    statusBox.className = 'status' + (type ? ' ' + type : '');
}

function setOutputMode(viewerMode) {
    preview.hidden = !viewerMode;
    codeOutput.hidden = viewerMode;
    document.getElementById('workspace').classList.toggle('viewer-active', viewerMode);
    document.getElementById('outputLabel').textContent = viewerMode ? 'Live preview' : 'HTML output';
    document.getElementById('fullscreen').disabled = !viewerMode || !outputText;
}

function minifyHTML(source) {
    const protectedBlocks = [];
    const protectedSource = source.replace(/<(pre|textarea|script|style)\b[^>]*>[\s\S]*?<\/\1\s*>/gi, block => {
        protectedBlocks.push(block);
        return '\u0000' + (protectedBlocks.length - 1) + '\u0000';
    });
    return protectedSource
        .replace(/<!--(?!\[if\b)[\s\S]*?-->/gi, '')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\u0000(\d+)\u0000/g, (_, index) => protectedBlocks[Number(index)]);
}

function beautifyHTML(source, indentUnit) {
    const tokens = source.match(/<(pre|textarea|script|style)\b[^>]*>[\s\S]*?<\/\1\s*>|<!--[\s\S]*?-->|<![^>]*>|<\/?[A-Za-z][^>]*>|[^<]+/gi) || [];
    const voidElements = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
    const lines = [];
    let depth = 0;
    const add = value => lines.push(indentUnit.repeat(Math.max(0, depth)) + value);

    for (const rawToken of tokens) {
        const token = rawToken.trim();
        if (!token) continue;
        if (/^<\//.test(token)) {
            depth--;
            add(token);
            continue;
        }
        if (/^<(pre|textarea|script|style)\b/i.test(token) && /<\/(pre|textarea|script|style)\s*>$/i.test(token)) {
            add(token);
            continue;
        }
        if (/^<!/.test(token)) {
            add(token);
            continue;
        }
        if (/^<[A-Za-z]/.test(token)) {
            add(token);
            const tag = (token.match(/^<([A-Za-z][\w:-]*)/) || [])[1];
            if (tag && !voidElements.has(tag.toLowerCase()) && !/\/\s*>$/.test(token)) depth++;
            continue;
        }
        const text = token.replace(/\s+/g, ' ').trim();
        if (text) add(text);
    }
    return lines.join('\n');
}

function executeAction(action = activeAction) {
    try {
        if (!input.value.trim()) throw new Error('Enter some HTML first.');
        activeAction = action;
        if (action === 'viewer') {
            outputText = input.value;
            preview.srcdoc = outputText;
            setOutputMode(true);
            setStatus('Live preview rendered. Embedded scripts, styles, forms, navigation, and external resources are enabled.', 'success');
        } else {
            const indent = document.getElementById('indent').value;
            const indentUnit = indent === 'tab' ? '\t' : ' '.repeat(Number(indent));
            outputText = action === 'minify' ? minifyHTML(input.value) : beautifyHTML(input.value, indentUnit);
            output.textContent = outputText;
            setOutputMode(false);
            setStatus((action === 'minify' ? 'Minified' : 'Beautified') + ' HTML · ' + outputText.length.toLocaleString() + ' characters', 'success');
        }
        document.getElementById('copy').disabled = false;
        document.getElementById('download').disabled = false;
        document.getElementById('fullscreen').disabled = activeAction !== 'viewer';
    } catch (error) {
        outputText = '';
        output.textContent = '';
        preview.srcdoc = '';
        document.getElementById('copy').disabled = true;
        document.getElementById('download').disabled = true;
        document.getElementById('fullscreen').disabled = true;
        setStatus(error.message, 'error');
    }
}

function selectAction(button) {
    activeAction = button.dataset.htmlAction;
    document.querySelectorAll('[data-html-action]').forEach(option => {
        const selected = option === button;
        option.classList.toggle('active', selected);
        option.setAttribute('aria-pressed', String(selected));
    });
    setOutputMode(activeAction === 'viewer');
}

function updateInput() {
    document.getElementById('inputStats').textContent = input.value.length.toLocaleString() + ' characters';
    if (activeAction === 'viewer' && input.value.trim()) executeAction('viewer');
}

input.addEventListener('input', updateInput);
document.querySelectorAll('[data-html-action]').forEach(button => {
    button.addEventListener('click', () => {
        selectAction(button);
        executeAction(activeAction);
    });
});
document.getElementById('indent').onchange = () => {
    if (activeAction === 'beautify' && input.value.trim()) executeAction(activeAction);
};
document.getElementById('sample').onclick = () => {
    input.value = '<!doctype html><html lang="en"><head><meta charset="UTF-8"><title>Preview</title><style>body{font-family:system-ui;padding:2rem;color:#18212f}button{padding:.6rem 1rem}</style></head><body><main><h1>Hello, HTML!</h1><p>Edit the source and try the working script below.</p><button id="counter" type="button">Clicked 0 times</button><script>let count=0;document.getElementById("counter").onclick=event=>{event.currentTarget.textContent="Clicked "+(++count)+" times"};<\/script></main></body></html>';
    updateInput();
    const viewerButton = document.querySelector('[data-html-action="viewer"]');
    selectAction(viewerButton);
    executeAction(activeAction);
};
document.getElementById('clear').onclick = () => {
    input.value = '';
    outputText = '';
    output.textContent = '';
    preview.srcdoc = '';
    document.getElementById('inputStats').textContent = '0 characters';
    document.getElementById('copy').disabled = true;
    document.getElementById('download').disabled = true;
    document.getElementById('fullscreen').disabled = true;
    setStatus('Enter HTML to run the selected action. Ctrl / Cmd + Enter runs it.');
    input.focus();
};
document.getElementById('file').onchange = async event => {
    const file = event.target.files[0];
    if (!file) return;
    try {
        input.value = (await file.text()).replace(/^\uFEFF/, '');
        updateInput();
        executeAction(activeAction);
    } catch (error) {
        setStatus('Could not read file: ' + error.message, 'error');
    } finally {
        event.target.value = '';
    }
};
document.getElementById('copy').onclick = async () => {
    try {
        await navigator.clipboard.writeText(outputText);
        setStatus('HTML copied.', 'success');
    } catch (error) {
        setStatus('Clipboard access was denied. Select and copy the source manually.', 'error');
    }
};
document.getElementById('fullscreen').onclick = () => {
    if (!outputText || activeAction !== 'viewer') return;
    fullscreenPreview.srcdoc = outputText;
    previewDialog.showModal();
};
document.getElementById('closePreview').onclick = () => previewDialog.close();
previewDialog.addEventListener('close', () => {
    fullscreenPreview.srcdoc = '';
    document.getElementById('fullscreen').focus();
});
document.getElementById('download').onclick = () => {
    const url = URL.createObjectURL(new Blob([outputText], {type: 'text/html;charset=utf-8'}));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'document.html';
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    setStatus('HTML downloaded.', 'success');
};
document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        executeAction(activeAction);
    }
});
</script>

<?php renderFooter(); ?>
