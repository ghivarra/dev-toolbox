<?php
require_once dirname(__DIR__) . '/includes/layout.php';
renderHeader('Table Generator', 'Turn JSON arrays or CSV data into readable HTML tables.', 'tables');
?>

<header class="page-heading">
    <span class="eyebrow">Table workspace</span>
    <h1>Table Generator</h1>
    <p>Paste a JSON array or CSV document and preview the data as a clean table.</p>
</header>

<div class="compact-grid">
    <section class="tool-box">
        <h2>JSON array</h2>
        <p>Objects may use different keys; the table combines every discovered column.</p>
        <div class="field">
            <label for="jsonInput">JSON input</label>
            <textarea id="jsonInput" spellcheck="false">[
  {"name":"Ada","role":"Engineer","active":true},
  {"name":"Linus","role":"Maintainer","active":true}
]</textarea>
        </div>
        <button class="primary" id="jsonTable" type="button">Generate from JSON</button>
    </section>
    <section class="tool-box">
        <h2>CSV document</h2>
        <p>The first row is used as the table header. Quoted fields and newlines are supported.</p>
        <div class="field">
            <label for="csvInput">CSV input</label>
            <textarea id="csvInput" spellcheck="false">name,role,active
Ada,Engineer,true
Linus,Maintainer,true</textarea>
        </div>
        <button class="primary" id="csvTable" type="button">Generate from CSV</button>
    </section>
</div>

<div id="status" class="status" role="status" aria-live="polite">Choose a source to generate a table.</div>

<section class="tool-section" aria-labelledby="preview-title">
    <div class="section-heading">
        <div><span class="eyebrow">Preview</span><h2 id="preview-title">Generated table</h2></div>
        <p id="tableStats">No data yet</p>
    </div>
    <div class="table-wrap" id="tableOutput"><div class="table-empty">Your generated table will appear here.</div></div>
</section>

<script>
'use strict';

const tableOutput = document.getElementById('tableOutput');
const statusBox = document.getElementById('status');

function setStatus(message, type = '') {
    statusBox.textContent = message;
    statusBox.className = 'status' + (type ? ' ' + type : '');
}

function printable(value) {
    if (value === null) return 'null';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value ?? '');
}

function renderTable(columns, rows, source) {
    const table = document.createElement('table');
    const thead = table.createTHead();
    const headerRow = thead.insertRow();
    for (const column of columns) {
        const th = document.createElement('th');
        th.scope = 'col';
        th.textContent = column;
        headerRow.appendChild(th);
    }

    const tbody = table.createTBody();
    for (const row of rows) {
        const tr = tbody.insertRow();
        for (const column of columns) {
            const cell = tr.insertCell();
            cell.textContent = printable(row[column]);
        }
    }

    // Always discard the prior preview so either source can regenerate it.
    tableOutput.textContent = '';
    tableOutput.appendChild(table);
    document.getElementById('tableStats').textContent = rows.length.toLocaleString() + ' rows · ' + columns.length.toLocaleString() + ' columns';
    setStatus('Table generated from ' + source + '. You can edit either source and generate again.', 'success');
}

function parseCSV(source) {
    const rows = [];
    let row = [];
    let field = '';
    let quoted = false;

    for (let i = 0; i < source.length; i++) {
        const character = source[i];
        if (quoted) {
            if (character === '"' && source[i + 1] === '"') {
                field += '"';
                i++;
            } else if (character === '"') {
                quoted = false;
            } else {
                field += character;
            }
        } else if (character === '"' && field === '') {
            quoted = true;
        } else if (character === ',') {
            row.push(field);
            field = '';
        } else if (character === '\n' || character === '\r') {
            if (character === '\r' && source[i + 1] === '\n') i++;
            row.push(field);
            rows.push(row);
            row = [];
            field = '';
        } else {
            field += character;
        }
    }
    if (quoted) throw new Error('CSV contains an unclosed quoted field.');
    if (field !== '' || row.length) {
        row.push(field);
        rows.push(row);
    }
    return rows;
}

document.getElementById('jsonTable').onclick = () => {
    try {
        const data = JSON.parse(document.getElementById('jsonInput').value);
        if (!Array.isArray(data) || !data.length) throw new Error('JSON must be a non-empty array.');

        let rows;
        let columns;
        if (data.every(item => item && typeof item === 'object' && !Array.isArray(item))) {
            rows = data;
            columns = [...new Set(data.flatMap(item => Object.keys(item)))];
        } else if (data.every(Array.isArray)) {
            const width = Math.max(...data.map(item => item.length));
            columns = Array.from({length: width}, (_, index) => String(index + 1));
            rows = data.map(item => Object.fromEntries(columns.map((column, index) => [column, item[index]])));
        } else {
            columns = ['value'];
            rows = data.map(value => ({value}));
        }
        if (!columns.length) throw new Error('The array objects do not contain any fields.');
        renderTable(columns, rows, 'JSON');
    } catch (error) {
        setStatus('Could not generate JSON table: ' + error.message, 'error');
    }
};

document.getElementById('csvTable').onclick = () => {
    try {
        const parsed = parseCSV(document.getElementById('csvInput').value.replace(/^\uFEFF/, ''));
        if (parsed.length < 2) throw new Error('CSV needs a header and at least one data row.');
        const columns = parsed[0].map((column, index) => column || 'Column ' + (index + 1));
        const rows = parsed.slice(1).map(values => Object.fromEntries(columns.map((column, index) => [column, values[index] ?? ''])));
        renderTable(columns, rows, 'CSV');
    } catch (error) {
        setStatus('Could not generate CSV table: ' + error.message, 'error');
    }
};
</script>

<?php renderFooter(); ?>
