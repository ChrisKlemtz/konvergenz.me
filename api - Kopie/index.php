<?php
// Einstieg für die Vercel-Vorschau (vercel-php). Auf dem späteren Webhosting nicht nötig.
define('K53_ENTRY', 'vercel');
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../index.php';
