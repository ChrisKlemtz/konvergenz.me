<?php
/**
 * Konvergenz 53 – Frontcontroller
 * Alle Seitenaufrufe landen hier (siehe .htaccess). Lokal testen: php -S localhost:8000 index.php
 */
declare(strict_types=1);

// Lokaler PHP-Server: vorhandene Dateien direkt ausliefern
if (PHP_SAPI === 'cli-server' && !defined('K53_ENTRY')) {
    // Statische Dateien selbst ausliefern (funktioniert unabhängig vom Startordner, auch unter Windows)
    $reqPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $file = realpath(__DIR__ . $reqPath);
    $root = realpath(__DIR__);
    $types = [
        'css' => 'text/css', 'js' => 'text/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ico' => 'image/x-icon', 'txt' => 'text/plain',
        'html' => 'text/html; charset=utf-8', 'xml' => 'application/xml',
    ];
    $ext = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
    if ($file && is_file($file) && str_starts_with($file, $root) && isset($types[$ext])
        && !preg_match('#[\\/](data|inc|lang|pages)[\\/]#', substr($file, strlen($root)))) {
        header('Content-Type: ' . $types[$ext]);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
    if (str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/admin')) {
        $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
        require __DIR__ . '/admin/index.php';
        return true;
    }
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/i18n.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/layout.php';

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = rawurldecode($path);

// Sitemap und robots
if ($path === '/sitemap.xml') {
    require __DIR__ . '/inc/sitemap.php';
    exit;
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

[$l, $page] = resolve_route($path);
set_lang($l);

// Wartungsmodus: Besucher sehen 503, angemeldeter Inhaber sieht die Seite
if (!empty(settings()['maintenance']) && !is_admin()) {
    http_response_code(503);
    header('Retry-After: 3600');
    readfile(__DIR__ . '/errors/503.html');
    exit;
}

if ($page === null) {
    http_response_code(404);
    $page = 'notfound';
}

require __DIR__ . '/pages/' . $page . '.php';
