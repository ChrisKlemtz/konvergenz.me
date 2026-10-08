<?php
declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('k53adm');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/** Prüft nur, ob ein Admin-Cookie existiert und gültig ist – ohne für Besucher eine Session zu starten */
function is_admin(): bool
{
    if (empty($_COOKIE['k53adm'])) return false;
    start_session();
    return !empty($_SESSION['admin']) && ($_SESSION['expires'] ?? 0) > time();
}
