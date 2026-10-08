<?php
declare(strict_types=1);

/** Seiten-Slugs je Sprache */
const ROUTES = [
    'de' => ['home' => '', 'menu' => 'speisekarte', 'contact' => 'kontakt', 'imprint' => 'impressum', 'privacy' => 'datenschutz'],
    'en' => ['home' => '', 'menu' => 'menu', 'contact' => 'contact', 'imprint' => 'imprint', 'privacy' => 'privacy'],
    'da' => ['home' => '', 'menu' => 'menukort', 'contact' => 'kontakt', 'imprint' => 'impressum', 'privacy' => 'privatliv'],
    'sv' => ['home' => '', 'menu' => 'meny', 'contact' => 'kontakt', 'imprint' => 'impressum', 'privacy' => 'integritet'],
];

const LANG_NAMES = ['de' => 'Deutsch', 'en' => 'English', 'da' => 'Dansk', 'sv' => 'Svenska'];

$GLOBALS['lang'] = 'de';

function lang(): string
{
    return $GLOBALS['lang'];
}

function set_lang(string $l): void
{
    $GLOBALS['lang'] = in_array($l, LANGS, true) ? $l : 'de';
}

function strings(string $l): array
{
    static $cache = [];
    if (!isset($cache[$l])) {
        $file = APP_ROOT . '/lang/' . $l . '.php';
        $cache[$l] = is_file($file) ? require $file : [];
    }
    return $cache[$l];
}

/** UI-Text in aktueller Sprache, Fallback Deutsch */
function t(string $key, array $vars = []): string
{
    $s = strings(lang())[$key] ?? strings('de')[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}

/** Mehrsprachiges Inhaltsfeld (aus dem Dashboard), Fallback Deutsch */
function tr($field): string
{
    if (!is_array($field)) return (string) $field;
    $v = trim((string) ($field[lang()] ?? ''));
    return $v !== '' ? $v : trim((string) ($field['de'] ?? ''));
}

/** URL einer Seite in einer Sprache */
function page_url(string $page, ?string $l = null): string
{
    $l ??= lang();
    $slug = ROUTES[$l][$page] ?? '';
    $prefix = $l === 'de' ? '' : $l . '/';
    return url($prefix . $slug);
}

/** Löst einen Pfad in [lang, page] auf; page = null → 404 */
function resolve_route(string $path): array
{
    $parts = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
    $l = 'de';
    if ($parts && in_array($parts[0], ['en', 'da', 'sv'], true)) {
        $l = array_shift($parts);
    }
    if (count($parts) > 1) return [$l, null];
    $slug = $parts[0] ?? '';
    $page = array_search($slug, ROUTES[$l], true);
    return [$l, $page === false ? null : $page];
}
