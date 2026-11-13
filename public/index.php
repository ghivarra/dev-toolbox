<?php
require_once __DIR__ . '/includes/layout.php';
renderHeader('Developer tools that stay out of your way', 'A focused collection of local utilities for JSON, strings, passwords, JWTs, and tabular data.');
?>

<section class="hero">
    <div>
        <span class="eyebrow">Everyday developer utilities</span>
        <h1>Small tools.<br>Zero friction.</h1>
        <p>Format payloads, transform strings, create credentials, inspect tokens, and turn raw data into readable tables. Everything runs locally in your browser.</p>
        <div class="hero-actions">
            <a class="button primary" href="/json/">Open JSON Formatter</a>
            <a class="button" href="#tools">Browse all tools</a>
        </div>
    </div>
    <div class="terminal" aria-label="Example JSON formatting preview">
        <div class="terminal-bar"><i></i><i></i><i></i></div>
        <pre>{
  <span class="key">"tool"</span>: <span class="string">"Dev Toolbox"</span>,
  <span class="key">"uploads"</span>: <span class="boolean">false</span>,
  <span class="key">"features"</span>: [
    <span class="string">"format"</span>,
    <span class="string">"convert"</span>,
    <span class="string">"generate"</span>
  ]
}<span class="cursor">_</span></pre>
    </div>
</section>

<section id="tools" aria-labelledby="tools-title">
    <div class="section-heading">
        <div>
            <span class="eyebrow">The toolkit</span>
            <h2 id="tools-title">Pick a workspace</h2>
        </div>
        <p>Five focused pages, one consistent workflow.</p>
    </div>
    <div class="tool-grid">
        <a class="tool-card" href="/json/index.php"><span class="tool-icon">{ }</span><h3>JSON</h3><p>Beautify, minify, validate, escape, and unescape JSON.</p><span>Open formatter →</span></a>
        <a class="tool-card" href="/string/index.php"><span class="tool-icon">Aa</span><h3>String</h3><p>Count, encode, decode, change case, and create UUIDs.</p><span>Open string tools →</span></a>
        <a class="tool-card" href="/password/index.php"><span class="tool-icon">***</span><h3>Password</h3><p>Generate random values and create MD5 or bcrypt hashes.</p><span>Open password tools →</span></a>
        <a class="tool-card" href="/jwt/index.php"><span class="tool-icon">JWT</span><h3>JWT</h3><p>Read token contents and sign HMAC JSON Web Tokens.</p><span>Open JWT tools →</span></a>
        <a class="tool-card" href="/tables/index.php"><span class="tool-icon">▦</span><h3>Tables</h3><p>Preview JSON arrays and CSV data as clean tables.</p><span>Open table tools →</span></a>
    </div>
</section>

<?php renderFooter(); ?>
