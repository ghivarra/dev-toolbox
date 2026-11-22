<?php

function renderHeader(string $title, string $description, string $active = ''): void
{
    $links = [
        '' => ['Home', '/'],
        'json' => ['JSON', '/json/index.php'],
        'html' => ['HTML', '/html/index.php'],
        'string' => ['String', '/string/index.php'],
        'password' => ['Password', '/password/index.php'],
        'jwt' => ['JWT', '/jwt/index.php'],
        'tables' => ['Tables', '/tables/index.php'],
    ];
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= htmlspecialchars($description) ?>">
        <title><?= htmlspecialchars($title) ?> · Dev Toolbox</title>
        <link rel="stylesheet" href="/assets/styles.css">
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="/" aria-label="Dev Toolbox home">
                <span class="brand-mark">{ }</span>
                <span>Dev Toolbox</span>
            </a>
            <nav class="site-nav" aria-label="Main navigation">
                <?php foreach ($links as $key => [$label, $href]): ?>
                    <a href="<?= $href ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a>
                <?php endforeach; ?>
            </nav>
            <span class="local-badge">100% local</span>
        </header>
        <main class="site-main">
    <?php
}

function renderFooter(): void
{
    $versionFile = __DIR__ . "/../../version.txt";
    $version = isset($versionFile) ? file_get_contents($versionFile) : "v0.0.1-beta";
    ?>
        </main>
        <footer class="site-footer">
            <div class="footer-copy">
                <span><?= htmlspecialchars($version) ?> - Built for quick, everyday developer tasks.</span>
                <span>No third-party requests, tracking, or application data storage.</span>
            </div>
            <nav class="footer-nav" aria-label="Footer navigation">
                <a href="/json/index.php">JSON</a>
                <a href="/html/index.php">HTML</a>
                <a href="/string/index.php">String</a>
                <a href="/password/index.php">Password</a>
                <a href="/jwt/index.php">JWT</a>
                <a href="/tables/index.php">Tables</a>
            </nav>
        </footer>
    </body>
    </html>
    <?php
}

