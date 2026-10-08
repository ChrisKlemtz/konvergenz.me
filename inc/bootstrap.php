<?php
/**
 * Konvergenz 53 – Grundfunktionen
 * Läuft auf jedem Webhosting mit PHP 8.1+ (Strato, IONOS …). Keine Datenbank nötig.
 */
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const DATA_DIR = APP_ROOT . '/data';
const UPLOAD_DIR = APP_ROOT . '/uploads';
const LANGS = ['de', 'en', 'da', 'sv'];
const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

/** Bildplätze der Website (im Dashboard unter „Bilder“ austauschbar) */
const IMAGE_SLOTS = [
    'logo' => ['label' => 'Logo', 'hint' => 'Helle Version auf transparentem Hintergrund (PNG), erscheint oben im grünen Kopfbereich.'],
    'hero' => ['label' => 'Startseite: großes Bild', 'hint' => 'Hochformat wirkt am besten, wird im Bogen-Rahmen gezeigt.'],
    'brunch' => ['label' => 'Startseite: Frühstück & Brunch', 'hint' => 'Hochformat oder quadratisch.'],
    'tapas' => ['label' => 'Startseite: Tapas am Abend', 'hint' => 'Hochformat oder quadratisch.'],
    'intro' => ['label' => 'Startseite: „Einfach gutes Essen.“', 'hint' => 'Optional, kleines quadratisches Bild neben den Vorteilen.'],
    'map' => ['label' => 'Kontakt: Kartenbild', 'hint' => 'Kartenausschnitt der Altstadt, z. B. von openstreetmap.org über „Teilen“ → „Bild herunterladen“. Ein Klick darauf öffnet Google Maps.'],
];

date_default_timezone_set('Europe/Berlin');
mb_internal_encoding('UTF-8');

/* ---------- Basis-URL (funktioniert auch in einem Unterordner) ---------- */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir = rtrim(dirname($script), '/');
        if (str_ends_with($dir, '/admin')) {
            $dir = substr($dir, 0, -6);
        }
        $base = $dir;
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https://' : 'http://') . $host . url($path);
}

function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $v;
}

/* ---------- Escaping ---------- */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- JSON-Datenablage ---------- */
function data_read(string $name, array $default = []): array
{
    $file = DATA_DIR . '/' . $name . '.json';
    if (!is_file($file)) {
        return $default;
    }
    $json = json_decode((string) file_get_contents($file), true);
    return is_array($json) ? array_replace_recursive($default, $json) : $default;
}

function data_write(string $name, array $data): void
{
    $file = DATA_DIR . '/' . $name . '.json';
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException(getenv('VERCEL')
            ? 'Auf der Vercel-Vorschau kann nichts gespeichert werden. Bitte lokal bearbeiten und per Git hochladen.'
            : 'Speichern fehlgeschlagen. Bitte Schreibrechte für den Ordner "data" prüfen.');
    }
}

function empty_i18n(): array
{
    return array_fill_keys(LANGS, '');
}

function settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = data_read('settings', [
            'name' => 'Konvergenz 53',
            'owner' => 'Christoph Sandt',
            'street' => 'Badenstraße 53',
            'zip' => '18439',
            'city' => 'Stralsund',
            'phone' => '03831 9419143',
            'email' => 'christoph.sandt@konvergenz.me',
            'instagram' => 'https://www.instagram.com/konvergenz53',
            'reservation_url' => '',
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Konvergenz+53%2C+Badenstra%C3%9Fe+53%2C+Stralsund',
            'vat_id' => '',
            'hoster' => "STRATO GmbH\nOtto-Ostrowski-Straße 7\n10249 Berlin",
            'notice' => empty_i18n(),
            'directions' => empty_i18n(),
            'map_credit' => '© OpenStreetMap-Mitwirkende',
            'maintenance' => false,
            'images' => [],
        ]);
    }
    return $s;
}

function hours(): array
{
    $default = ['days' => [], 'note' => empty_i18n()];
    foreach (DAYS as $d) {
        $default['days'][$d] = ['closed' => false, 'ranges' => []];
    }
    $h = data_read('hours', $default);
    foreach (DAYS as $d) {
        $h['days'][$d]['ranges'] = array_values(array_filter(
            $h['days'][$d]['ranges'] ?? [],
            fn($r) => is_array($r) && count($r) === 2 && $r[0] !== '' && $r[1] !== ''
        ));
    }
    return $h;
}

function menu_data(): array
{
    return data_read('menu', ['categories' => []]);
}

/* ---------- Bilder ---------- */
/** Liefert URL eines Website-Bildes (hero, intro, brunch, tapas, map, logo) oder null */
function site_image(string $slot): ?string
{
    $file = settings()['images'][$slot] ?? '';
    if ($file === '' || !is_file(UPLOAD_DIR . '/' . $file)) {
        return null;
    }
    return url('uploads/' . $file) . '?v=' . filemtime(UPLOAD_DIR . '/' . $file);
}

function upload_url(?string $file): ?string
{
    if (!$file || !is_file(UPLOAD_DIR . '/' . $file)) {
        return null;
    }
    return url('uploads/' . $file);
}

/** Thumbnail-Variante (…-sm.webp) falls vorhanden */
function upload_thumb(?string $file): ?string
{
    if (!$file) return null;
    $thumb = preg_replace('/\.webp$/', '-sm.webp', $file);
    if ($thumb && is_file(UPLOAD_DIR . '/' . $thumb)) {
        return url('uploads/' . $thumb);
    }
    return upload_url($file);
}

/* ---------- Öffnungszeiten-Logik ---------- */
function minutes(string $hhmm): int
{
    [$h, $m] = array_map('intval', explode(':', $hhmm) + [0, 0]);
    return $h * 60 + $m;
}

/** Status jetzt: ['open' => bool, 'today' => ranges, 'closed_today' => bool] */
function open_status(?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable('now');
    $h = hours();
    $idx = (int) $now->format('N') - 1; // 0 = Montag
    $today = $h['days'][DAYS[$idx]];
    $yesterday = $h['days'][DAYS[($idx + 6) % 7]];
    $cur = (int) $now->format('G') * 60 + (int) $now->format('i');

    $open = false;
    if (!$today['closed']) {
        foreach ($today['ranges'] as [$a, $b]) {
            $s = minutes($a);
            $e = minutes($b);
            if ($e <= $s) $e += 1440; // über Mitternacht
            if ($cur >= $s && $cur < $e) $open = true;
        }
    }
    // Spätschicht von gestern über Mitternacht
    if (!$yesterday['closed']) {
        foreach ($yesterday['ranges'] as [$a, $b]) {
            if (minutes($b) <= minutes($a) && $cur < minutes($b)) $open = true;
        }
    }
    $hasData = false;
    foreach ($h['days'] as $d) {
        if ($d['closed'] || $d['ranges']) $hasData = true;
    }
    return [
        'open' => $open,
        'today' => $today['ranges'],
        'closed_today' => $today['closed'] || !$today['ranges'],
        'has_data' => $hasData,
    ];
}

function format_ranges(array $ranges): string
{
    return implode(' & ', array_map(fn($r) => $r[0] . '–' . $r[1], $ranges));
}

/** Fasst gleiche aufeinanderfolgende Tage zusammen: [['label'=>'Mo–Fr','text'=>'09:00–15:00'], …] */
function grouped_hours(): array
{
    $h = hours();
    $rows = [];
    foreach (DAYS as $d) {
        $day = $h['days'][$d];
        $text = $day['closed'] ? t('hours.closed') : ($day['ranges'] ? format_ranges($day['ranges']) . ' ' . t('hours.suffix') : '');
        $text = trim($text);
        $last = count($rows) - 1;
        if ($last >= 0 && $rows[$last]['text'] === $text) {
            $rows[$last]['to'] = $d;
        } else {
            $rows[] = ['from' => $d, 'to' => $d, 'text' => $text];
        }
    }
    foreach ($rows as &$r) {
        $r['label'] = $r['from'] === $r['to']
            ? t('day.' . $r['from'])
            : t('day.short.' . $r['from']) . '–' . t('day.short.' . $r['to']);
        if ($r['text'] === '') $r['text'] = '–';
    }
    return $rows;
}
