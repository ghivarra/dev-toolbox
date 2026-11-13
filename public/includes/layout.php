<?php

function renderHeader(string $title, string $description, string $active = ''): void
{
    $links = [
        '' => ['Home', '/'],
        'json' => ['JSON', '/json/index.php'],
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
    ?>
        </main>
        <footer class="site-footer">
            <span>Built for quick, everyday developer tasks.</span>
            <span>No third-party requests, tracking, or stored data.</span>
        </footer>
    </body>
    </html>
    <?php
}

