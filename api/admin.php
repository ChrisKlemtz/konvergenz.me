<?php
// Dashboard auf der Vercel-Vorschau: nur ansehen, Speichern ist dort nicht möglich.
define('K53_ENTRY', 'vercel');
$_SERVER['SCRIPT_NAME'] = '/admin/index.php';
require __DIR__ . '/../admin/index.php';
