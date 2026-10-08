<?php
/**
 * Konvergenz 53 – Verwaltung (passwortgeschützt)
 * Speisekarte, Öffnungszeiten, Bilder und Kontaktdaten pflegen.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/i18n.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/images.php';

const SESSION_HOURS = 8;
const MAX_ATTEMPTS = 5;
const LOCK_MINUTES = 15;
const TAG_LABELS = ['vegan' => 'vegan', 'vegetarian' => 'vegetarisch', 'glutenfree' => 'glutenfrei möglich', 'lactosefree' => 'laktosefrei möglich', 'new' => 'neu'];
const OTHER_LANGS = ['en' => 'Englisch', 'da' => 'Dänisch', 'sv' => 'Schwedisch'];

/** Bilder der bisherigen Website konvergenz.me (einmaliger Import) */
const OLD_SITE_IMAGES = [
    'logo' => 'https://konvergenz.me/wp-content/uploads/2025/11/logo-1500x1500-white53.png',
    'hero' => 'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202046_com_instagram_android_InstagramMainActivity_edit_343062267067359-822x1024.jpg',
    'brunch' => 'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202227_com_instagram_android_InstagramMainActivity_edit_342820610224951-786x1024.jpg',
    'tapas' => 'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202117_com_instagram_android_InstagramMainActivity_edit_342947977854799-1024x1011.jpg',
    'intro' => 'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202114_com_instagram_android_InstagramMainActivity_edit_342967021707239-1024x1019.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202108_com_instagram_android_InstagramMainActivity_edit_342987045187544-820x1024.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202052_com_instagram_android_InstagramMainActivity_edit_343050872368666-826x1024.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202025_com_instagram_android_InstagramMainActivity_edit_343130292959208-1-824x1024.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202033_com_instagram_android_InstagramMainActivity_edit_343117551264838-821x1024.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202103_com_instagram_android_InstagramMainActivity_edit_343003291785611-1024x669.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202059_com_instagram_android_InstagramMainActivity_edit_343016721363835-1024x1015.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202056_com_instagram_android_InstagramMainActivity_edit_343033271669037-812x1024.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202129_com_instagram_android_InstagramMainActivity_edit_342930491185482-1024x580.jpg',
    'https://konvergenz.me/wp-content/uploads/2025/11/Screenshot_20251120_202212_com_instagram_android_InstagramMainActivity_edit_342857893889468-1024x1012.jpg',
];

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

start_session();

/* ============================================================
   Hilfsfunktionen
   ============================================================ */
function admin_url(string $section = '', array $q = []): string
{
    $q = array_filter(['s' => $section ?: null] + $q, fn($v) => $v !== null && $v !== '');
    return url('admin/') . ($q ? '?' . http_build_query($q) : '');
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">';
}

function check_csrf(): void
{
    $token = (string) ($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || $token === '' || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        exit('Sitzung abgelaufen. Bitte die Seite neu laden und noch einmal versuchen.');
    }
}

function post(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_i18n(string $k): array
{
    $out = [];
    foreach (LANGS as $l) {
        $v = $_POST[$k][$l] ?? '';
        $out[$l] = is_string($v) ? trim($v) : '';
    }
    return $out;
}

function new_id(string $prefix): string
{
    return $prefix . bin2hex(random_bytes(4));
}

function save_settings(array $changes): void
{
    $s = data_read('settings');
    data_write('settings', array_replace($s, $changes));
}

function auth_data(): array
{
    return data_read('auth', []);
}

function client_key(): string
{
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|k53');
}

function attempts(): array
{
    $a = data_read('login-attempts', []);
    return array_filter($a, fn($r) => ($r['until'] ?? 0) > time() || ($r['last'] ?? 0) > time() - 3600);
}

function is_locked(): int
{
    $r = attempts()[client_key()] ?? null;
    return ($r && ($r['until'] ?? 0) > time()) ? (int) ceil(($r['until'] - time()) / 60) : 0;
}

function register_failure(): void
{
    $a = attempts();
    $k = client_key();
    $r = $a[$k] ?? ['count' => 0, 'until' => 0];
    $r['count']++;
    $r['last'] = time();
    if ($r['count'] >= MAX_ATTEMPTS) {
        $r['until'] = time() + LOCK_MINUTES * 60;
        $r['count'] = 0;
    }
    $a[$k] = $r;
    data_write('login-attempts', $a);
}

function clear_failures(): void
{
    $a = attempts();
    unset($a[client_key()]);
    data_write('login-attempts', $a);
}

function logged_in(): bool
{
    if (empty($_SESSION['admin']) || ($_SESSION['expires'] ?? 0) < time()) return false;
    $_SESSION['expires'] = time() + SESSION_HOURS * 3600;
    return true;
}

/** Findet Kategorie- und Gericht-Index */
function &find_cat(array &$menu, string $id): ?array
{
    foreach ($menu['categories'] as &$c) {
        if ($c['id'] === $id) return $c;
    }
    $null = null;
    return $null;
}

function move(array &$list, int $i, int $dir): void
{
    $j = $i + $dir;
    if ($j < 0 || $j >= count($list)) return;
    [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
}

function valid_time(string $t): bool
{
    return (bool) preg_match('/^(([01]\d|2[0-3]):[0-5]\d|24:00)$/', $t);
}

function valid_url(string $u): bool
{
    return $u === '' || (filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u));
}

/** Verarbeitet ein Bildfeld (Upload, Auswahl aus Archiv oder Entfernen). Gibt Dateiname zurück. */
function handle_image_field(string $current): string
{
    if (!empty($_POST['image_remove'])) return '';
    if (!empty($_FILES['image_upload']['tmp_name']) && is_uploaded_file($_FILES['image_upload']['tmp_name'])) {
        return store_image($_FILES['image_upload']['tmp_name']);
    }
    if (isset($_FILES['image_upload']) && ($_FILES['image_upload']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException(upload_error($_FILES['image_upload']['error']));
    }
    $pick = post('image_pick');
    if ($pick !== '' && is_library_image($pick)) return $pick;
    return $current;
}

function upload_error(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Das Bild ist zu groß für den Server (max. ' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_PARTIAL => 'Das Bild wurde nur teilweise hochgeladen. Bitte noch einmal versuchen.',
        default => 'Das Hochladen hat nicht geklappt (Fehler ' . $code . ').',
    };
}

/* ============================================================
   Aktionen (POST)
   ============================================================ */
$section = (string) ($_GET['s'] ?? 'start');
$hasPassword = !empty(auth_data()['hash']);
$setupAllowed = !$hasPassword && is_file(DATA_DIR . '/.setup-erlaubt');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $a = (string) ($_POST['a'] ?? '');

    try {
        /* ---- Ersteinrichtung ---- */
        if ($a === 'setup') {
            if (!$setupAllowed) redirect(admin_url());
            $p1 = (string) ($_POST['pw'] ?? '');
            $p2 = (string) ($_POST['pw2'] ?? '');
            if (mb_strlen($p1) < 10) throw new RuntimeException('Das Passwort braucht mindestens 10 Zeichen.');
            if ($p1 !== $p2) throw new RuntimeException('Die beiden Passwörter stimmen nicht überein.');
            data_write('auth', ['hash' => password_hash($p1, PASSWORD_DEFAULT), 'changed' => date('c')]);
            @unlink(DATA_DIR . '/.setup-erlaubt');
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['expires'] = time() + SESSION_HOURS * 3600;
            flash('Passwort gespeichert. Willkommen im Dashboard!');
            redirect(admin_url());
        }

        /* ---- Anmelden ---- */
        if ($a === 'login') {
            if ($m = is_locked()) throw new RuntimeException("Zu viele Fehlversuche. Bitte in $m Minuten noch einmal versuchen.");
            $hash = auth_data()['hash'] ?? '';
            if ($hash !== '' && password_verify((string) ($_POST['pw'] ?? ''), $hash)) {
                clear_failures();
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    data_write('auth', ['hash' => password_hash((string) $_POST['pw'], PASSWORD_DEFAULT), 'changed' => date('c')]);
                }
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                $_SESSION['expires'] = time() + SESSION_HOURS * 3600;
                redirect(admin_url());
            }
            register_failure();
            usleep(400000);
            throw new RuntimeException('Das Passwort stimmt nicht.');
        }

        if (!logged_in()) redirect(admin_url());

        /* ---- Abmelden ---- */
        if ($a === 'logout') {
            $_SESSION = [];
            session_destroy();
            setcookie('k53adm', '', time() - 3600, base_path() . '/');
            redirect(admin_url());
        }

        $menu = menu_data();

        switch ($a) {
            /* ---- Kategorien ---- */
            case 'cat_save':
                $name = post_i18n('name');
                if ($name['de'] === '') throw new RuntimeException('Bitte einen deutschen Namen für die Kategorie eingeben.');
                $id = post('id');
                $data = ['name' => $name, 'note' => post_i18n('note'), 'hidden' => !empty($_POST['hidden'])];
                if ($id === '') {
                    $menu['categories'][] = ['id' => new_id('c'), 'items' => []] + $data;
                    flash('Kategorie „' . $name['de'] . '“ angelegt.');
                } else {
                    $c = &find_cat($menu, $id);
                    if (!$c) throw new RuntimeException('Kategorie nicht gefunden.');
                    $c = array_replace($c, $data);
                    unset($c);
                    flash('Kategorie gespeichert.');
                }
                data_write('menu', $menu);
                redirect(admin_url('menu'));

            case 'cat_move':
            case 'cat_toggle':
            case 'cat_delete':
                foreach ($menu['categories'] as $i => $c) {
                    if ($c['id'] !== post('id')) continue;
                    if ($a === 'cat_move') move($menu['categories'], $i, post('dir') === 'up' ? -1 : 1);
                    if ($a === 'cat_toggle') $menu['categories'][$i]['hidden'] = empty($c['hidden']);
                    if ($a === 'cat_delete') {
                        array_splice($menu['categories'], $i, 1);
                        flash('Kategorie „' . $c['name']['de'] . '“ mit ' . count($c['items'] ?? []) . ' Gericht(en) gelöscht.');
                    }
                    break;
                }
                data_write('menu', $menu);
                redirect(admin_url('menu') . '#' . post('id'));

            /* ---- Gerichte ---- */
            case 'item_save':
                $name = post_i18n('name');
                if ($name['de'] === '') throw new RuntimeException('Bitte einen deutschen Namen für das Gericht eingeben.');
                $price = post('price');
                if (mb_strlen($price) > 30) throw new RuntimeException('Der Preis ist zu lang (max. 30 Zeichen).');
                $tags = array_values(array_intersect(array_keys(TAG_LABELS), (array) ($_POST['tags'] ?? [])));
                $catId = post('cat');
                $itemId = post('id');
                $target = &find_cat($menu, $catId);
                if (!$target) throw new RuntimeException('Bitte eine Kategorie wählen.');
                unset($target);

                // vorhandenes Gericht suchen (ggf. in anderer Kategorie)
                $existing = null;
                foreach ($menu['categories'] as $ci => $c) {
                    foreach ($c['items'] ?? [] as $ii => $it) {
                        if ($it['id'] === $itemId) {
                            $existing = $it;
                            if ($c['id'] !== $catId) array_splice($menu['categories'][$ci]['items'], $ii, 1);
                        }
                    }
                }
                $item = [
                    'id' => $existing['id'] ?? new_id('i'),
                    'name' => $name,
                    'desc' => post_i18n('desc'),
                    'price' => $price,
                    'tags' => $tags,
                    'image' => handle_image_field($existing['image'] ?? ''),
                    'hidden' => !empty($_POST['hidden']),
                ];
                $target = &find_cat($menu, $catId);
                $placed = false;
                foreach ($target['items'] as &$it) {
                    if ($it['id'] === $item['id']) { $it = $item; $placed = true; }
                }
                unset($it);
                if (!$placed) $target['items'][] = $item;
                unset($target);
                data_write('menu', $menu);
                flash('Gericht „' . $name['de'] . '“ gespeichert.');
                redirect(admin_url('menu') . '#' . $item['id']);

            case 'item_move':
            case 'item_toggle':
            case 'item_delete':
                foreach ($menu['categories'] as $ci => $c) {
                    foreach ($c['items'] ?? [] as $ii => $it) {
                        if ($it['id'] !== post('id')) continue;
                        if ($a === 'item_move') move($menu['categories'][$ci]['items'], $ii, post('dir') === 'up' ? -1 : 1);
                        if ($a === 'item_toggle') $menu['categories'][$ci]['items'][$ii]['hidden'] = empty($it['hidden']);
                        if ($a === 'item_delete') {
                            array_splice($menu['categories'][$ci]['items'], $ii, 1);
                            flash('Gericht „' . $it['name']['de'] . '“ gelöscht.');
                        }
                        break 2;
                    }
                }
                data_write('menu', $menu);
                redirect(admin_url('menu') . '#' . ($a === 'item_delete' ? '' : post('id')));

            /* ---- Öffnungszeiten ---- */
            case 'hours_save':
                $days = [];
                foreach (DAYS as $d) {
                    $ranges = [];
                    foreach ([1, 2] as $n) {
                        $from = post("{$d}_from$n");
                        $to = post("{$d}_to$n");
                        if ($from === '' && $to === '') continue;
                        if (!valid_time($from) || !valid_time($to)) {
                            throw new RuntimeException('Bitte bei ' . t('day.' . $d) . ' beide Uhrzeiten im Format HH:MM angeben.');
                        }
                        $ranges[] = [$from, $to];
                    }
                    $days[$d] = ['closed' => !empty($_POST[$d . '_closed']), 'ranges' => $ranges];
                }
                data_write('hours', ['days' => $days, 'note' => post_i18n('note')]);
                save_settings(['notice' => post_i18n('notice')]);
                flash('Öffnungszeiten gespeichert.');
                redirect(admin_url('hours'));

            /* ---- Bilder ---- */
            case 'slot_save':
                $slot = post('slot');
                if (!isset(IMAGE_SLOTS[$slot])) throw new RuntimeException('Unbekannter Bildplatz.');
                $images = settings()['images'];
                $images[$slot] = handle_image_field($images[$slot] ?? '');
                save_settings(['images' => $images]);
                flash('Bild „' . IMAGE_SLOTS[$slot]['label'] . '“ gespeichert.');
                redirect(admin_url('images') . '#slot-' . $slot);

            case 'library_upload':
                $files = $_FILES['images'] ?? null;
                $n = 0;
                $errors = [];
                if ($files && is_array($files['tmp_name'])) {
                    foreach ($files['tmp_name'] as $k => $tmp) {
                        if (($files['error'][$k] ?? 4) === UPLOAD_ERR_NO_FILE) continue;
                        try {
                            if ($files['error'][$k] !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error($files['error'][$k]));
                            if (!is_uploaded_file($tmp)) throw new RuntimeException('Ungültiger Upload.');
                            store_image($tmp);
                            $n++;
                        } catch (RuntimeException $e) {
                            $errors[] = $files['name'][$k] . ': ' . $e->getMessage();
                        }
                    }
                }
                if ($n) flash("$n Bild(er) hochgeladen.");
                foreach ($errors as $e) flash($e, 'error');
                if (!$n && !$errors) flash('Bitte mindestens ein Bild auswählen.', 'error');
                redirect(admin_url('images') . '#archiv');

            case 'library_delete':
                $file = post('file');
                if ($uses = image_usage($file)) {
                    throw new RuntimeException('Dieses Bild wird noch verwendet: ' . implode(', ', $uses) . '. Bitte dort zuerst ein anderes Bild wählen.');
                }
                delete_library_image($file);
                flash('Bild gelöscht.');
                redirect(admin_url('images') . '#archiv');

            case 'import_old':
                @set_time_limit(180);
                $done = settings()['imported'] ?? [];
                $images = settings()['images'];
                $n = 0;
                $errors = [];
                foreach (OLD_SITE_IMAGES as $slot => $u) {
                    if (in_array($u, $done, true)) continue;
                    try {
                        $file = fetch_remote_image($u);
                        $done[] = $u;
                        $n++;
                        if (is_string($slot) && empty($images[$slot])) $images[$slot] = $file;
                    } catch (RuntimeException $e) {
                        $errors[] = $e->getMessage();
                    }
                }
                save_settings(['images' => $images, 'imported' => $done]);
                flash($n ? "$n Bild(er) von der alten Website übernommen." : 'Keine neuen Bilder übernommen.', $n ? 'ok' : 'error');
                if ($errors) flash(count($errors) . ' Bild(er) konnten nicht geladen werden. Ist die alte Website noch online?', 'error');
                redirect(admin_url('images'));

            /* ---- Einstellungen ---- */
            case 'settings_save':
                $fields = ['name', 'owner', 'street', 'zip', 'city', 'phone', 'email', 'instagram', 'reservation_url', 'maps_url', 'vat_id', 'hoster', 'map_credit'];
                $new = [];
                foreach ($fields as $f) $new[$f] = post($f);
                foreach (['name', 'street', 'zip', 'city', 'phone', 'email', 'owner'] as $req) {
                    if ($new[$req] === '') throw new RuntimeException('Bitte alle Pflichtfelder ausfüllen (Name, Inhaber, Adresse, Telefon, E-Mail).');
                }
                if (!filter_var($new['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Die E-Mail-Adresse ist ungültig.');
                foreach (['instagram' => 'Instagram', 'reservation_url' => 'Reservierung', 'maps_url' => 'Google Maps'] as $f => $label) {
                    if (!valid_url($new[$f])) throw new RuntimeException("Der Link bei „{$label}“ muss mit https:// beginnen.");
                }
                $new['directions'] = post_i18n('directions');
                save_settings($new);
                flash('Einstellungen gespeichert.');
                redirect(admin_url('settings'));

            case 'maintenance':
                save_settings(['maintenance' => post('on') === '1']);
                flash(post('on') === '1' ? 'Wartungsmodus ist an. Besucher sehen eine Hinweisseite.' : 'Wartungsmodus ist aus. Die Website ist wieder für alle sichtbar.');
                redirect(admin_url(post('back') ?: 'start'));

            case 'password':
                $hash = auth_data()['hash'] ?? '';
                if (!password_verify((string) ($_POST['old'] ?? ''), $hash)) throw new RuntimeException('Das aktuelle Passwort stimmt nicht.');
                $p1 = (string) ($_POST['pw'] ?? '');
                if (mb_strlen($p1) < 10) throw new RuntimeException('Das neue Passwort braucht mindestens 10 Zeichen.');
                if ($p1 !== (string) ($_POST['pw2'] ?? '')) throw new RuntimeException('Die beiden neuen Passwörter stimmen nicht überein.');
                data_write('auth', ['hash' => password_hash($p1, PASSWORD_DEFAULT), 'changed' => date('c')]);
                session_regenerate_id(true);
                flash('Passwort geändert.');
                redirect(admin_url('password'));
        }
        redirect(admin_url());
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
        $_SESSION['old_post'] = array_diff_key($_POST, array_flip(['pw', 'pw2', 'old', 'csrf']));
        $back = $_SERVER['HTTP_REFERER'] ?? admin_url();
        redirect(str_starts_with($back, absolute_url('admin/')) || str_starts_with($back, url('admin/')) ? $back : admin_url());
    }
}

/* ============================================================
   Ansichten (GET)
   ============================================================ */
$authed = logged_in();
$old = $_SESSION['old_post'] ?? [];
unset($_SESSION['old_post']);

function old(string $k, $default = '')
{
    global $old;
    return $old[$k] ?? $default;
}

function i18n_fields(string $name, array $values, string $label, bool $textarea = false, bool $required = false, string $hint = ''): string
{
    $id = 'f-' . $name;
    $field = fn($l, $v, $id) => $textarea
        ? '<textarea id="' . $id . '" name="' . $name . '[' . $l . ']" rows="3">' . h($v) . '</textarea>'
        : '<input id="' . $id . '" type="text" name="' . $name . '[' . $l . ']" value="' . h($v) . '"' . ($required && $l === 'de' ? ' required' : '') . ' maxlength="400">';
    $html = '<div class="field"><label for="' . $id . '-de">' . h($label) . ($required ? ' <span class="req">*</span>' : '') . '</label>';
    if ($hint) $html .= '<p class="hint">' . h($hint) . '</p>';
    $html .= $field('de', $values['de'] ?? '', $id . '-de');
    $filled = count(array_filter(array_intersect_key($values, OTHER_LANGS), fn($v) => trim((string) $v) !== ''));
    $html .= '<details class="translations"><summary>Übersetzungen <span class="muted">(' . $filled . ' von 3 ausgefüllt, leer = deutscher Text)</span></summary>';
    foreach (OTHER_LANGS as $l => $ln) {
        $html .= '<div class="field-sub"><label for="' . $id . '-' . $l . '">' . h($ln) . '</label>' . $field($l, $values[$l] ?? '', $id . '-' . $l) . '</div>';
    }
    return $html . '</details></div>';
}

function image_picker(string $current, string $context): string
{
    $lib = library_images();
    $html = '<div class="field image-field"><span class="label">Bild</span>';
    if ($current && ($u = upload_thumb($current))) {
        $html .= '<div class="current-img"><img src="' . h($u) . '" alt="Aktuelles Bild" width="120" height="120"><label class="check"><input type="checkbox" name="image_remove" value="1"> Bild entfernen</label></div>';
    }
    $html .= '<label class="upload"><span>Neues Bild hochladen</span><input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif"></label>';
    $html .= '<p class="hint">JPG, PNG oder WebP, gern direkt vom Handy. Wird automatisch verkleinert.</p>';
    if ($lib) {
        $html .= '<details class="picker"><summary>Oder aus vorhandenen Bildern wählen (' . count($lib) . ')</summary><div class="pick-grid">';
        foreach ($lib as $f) {
            $html .= '<label class="pick"><input type="radio" name="image_pick" value="' . h($f) . '"' . ($f === $current ? ' checked' : '') . '><img src="' . h(upload_thumb($f)) . '" alt="" loading="lazy" width="96" height="96"></label>';
        }
        $html .= '</div></details>';
    }
    return $html . '</div>';
}

function admin_head(string $title, string $section = ''): void
{
    $nav = ['start' => 'Übersicht', 'menu' => 'Speisekarte', 'hours' => 'Öffnungszeiten', 'images' => 'Bilder', 'settings' => 'Einstellungen'];
    ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> · Konvergenz 53 Verwaltung</title>
<link rel="icon" href="<?= h(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= h(asset('admin/admin.css')) ?>">
</head>
<body>
<?php if ($section !== ''): ?>
<header class="top">
  <a class="top-brand" href="<?= h(admin_url()) ?>">Konvergenz <span>53</span> <small>Verwaltung</small></a>
  <a class="top-site" href="<?= h(url('')) ?>" target="_blank" rel="noopener">Website ansehen ↗</a>
  <form method="post" class="top-logout"><?= csrf_field() ?><input type="hidden" name="a" value="logout"><button class="btn-link">Abmelden</button></form>
</header>
<nav class="tabs" aria-label="Bereiche">
  <?php foreach ($nav as $k => $label): ?>
    <a href="<?= h(admin_url($k === 'start' ? '' : $k)) ?>"<?= $section === $k ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php if (!empty(settings()['maintenance'])): ?>
  <div class="banner banner-warn">Wartungsmodus ist an: Besucher sehen gerade nur eine Hinweisseite.
    <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="maintenance"><input type="hidden" name="on" value="0"><input type="hidden" name="back" value="<?= h($section) ?>"><button class="btn btn-sm">Ausschalten</button></form>
  </div>
<?php endif; ?>
<?php endif; ?>
<main class="main">
<?php foreach ($_SESSION['flash'] ?? [] as [$type, $msg]): ?>
  <div class="flash flash-<?= h($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= h($msg) ?></div>
<?php endforeach; unset($_SESSION['flash']); ?>
<?php
}

function admin_foot(): void
{
    echo '</main><script src="' . h(asset('admin/admin.js')) . '" defer></script></body></html>';
}

/* ---------- Nicht angemeldet: Setup oder Login ---------- */
if (!$authed) {
    admin_head('Anmelden');
    ?>
  <div class="login">
    <p class="login-brand">Konvergenz <span>53</span></p>
    <?php if ($setupAllowed): ?>
      <h1>Passwort festlegen</h1>
      <p class="muted">Willkommen! Lege einmalig das Passwort für die Verwaltung fest. Mindestens 10 Zeichen, am besten ein Satz, den du dir gut merken kannst.</p>
      <form method="post" class="card">
        <?= csrf_field() ?><input type="hidden" name="a" value="setup">
        <div class="field"><label for="pw">Neues Passwort</label><input id="pw" type="password" name="pw" minlength="10" required autocomplete="new-password"></div>
        <div class="field"><label for="pw2">Passwort wiederholen</label><input id="pw2" type="password" name="pw2" minlength="10" required autocomplete="new-password"></div>
        <button class="btn btn-primary btn-block">Speichern und anmelden</button>
      </form>
    <?php elseif (!$hasPassword): ?>
      <h1>Einrichtung gesperrt</h1>
      <div class="card"><p>Es ist noch kein Passwort festgelegt. Lege per FTP eine leere Datei <code>data/.setup-erlaubt</code> an und lade diese Seite neu.</p></div>
    <?php else: ?>
      <h1>Anmelden</h1>
      <form method="post" class="card">
        <?= csrf_field() ?><input type="hidden" name="a" value="login">
        <div class="field"><label for="pw">Passwort</label><input id="pw" type="password" name="pw" required autocomplete="current-password" autofocus></div>
        <button class="btn btn-primary btn-block">Anmelden</button>
      </form>
      <p class="muted small">Passwort vergessen? Lösche per FTP die Datei <code>data/auth.json</code> und lege eine leere Datei <code>data/.setup-erlaubt</code> an. Danach kannst du hier ein neues Passwort festlegen.</p>
    <?php endif; ?>
    <p class="small"><a href="<?= h(url('')) ?>">← Zur Website</a></p>
  </div>
    <?php
    admin_foot();
    exit;
}

/* ---------- Bereiche ---------- */
$menu = menu_data();
$s = settings();

switch ($section) {

/* ===== Speisekarte ===== */
case 'menu':
    admin_head('Speisekarte', 'menu');
    $itemCount = array_sum(array_map(fn($c) => count($c['items'] ?? []), $menu['categories']));
    ?>
    <div class="head-row">
      <div><h1>Speisekarte</h1><p class="muted"><?= count($menu['categories']) ?> Kategorien · <?= $itemCount ?> Gerichte</p></div>
      <a class="btn btn-primary" href="<?= h(admin_url('cat')) ?>">+ Kategorie</a>
    </div>
    <?php if (!$menu['categories']): ?>
      <div class="card empty">
        <h2>Noch keine Kategorien</h2>
        <p>Lege zuerst eine Kategorie an, zum Beispiel „Kleine Frühstücke“, „Große Frühstücke“, „Tapas“ oder „Getränke“. Danach kannst du Gerichte hinzufügen.</p>
        <a class="btn btn-primary" href="<?= h(admin_url('cat')) ?>">Erste Kategorie anlegen</a>
      </div>
    <?php endif; ?>
    <?php foreach ($menu['categories'] as $ci => $c): ?>
      <section class="card cat<?= !empty($c['hidden']) ? ' is-hidden' : '' ?>" id="<?= h($c['id']) ?>">
        <div class="cat-head">
          <h2><?= h($c['name']['de']) ?><?php if (!empty($c['hidden'])): ?> <span class="badge">ausgeblendet</span><?php endif; ?></h2>
          <div class="row-actions">
            <?= action_btn('cat_move', $c['id'], '↑', 'Kategorie nach oben', ['dir' => 'up'], $ci === 0) ?>
            <?= action_btn('cat_move', $c['id'], '↓', 'Kategorie nach unten', ['dir' => 'down'], $ci === count($menu['categories']) - 1) ?>
            <a class="btn btn-sm" href="<?= h(admin_url('cat', ['id' => $c['id']])) ?>">Bearbeiten</a>
          </div>
        </div>
        <?php if (!empty($c['note']['de'])): ?><p class="muted small"><?= h($c['note']['de']) ?></p><?php endif; ?>
        <?php if (empty($c['items'])): ?>
          <p class="muted">Noch keine Gerichte in dieser Kategorie.</p>
        <?php else: ?>
          <ul class="items">
            <?php foreach ($c['items'] as $ii => $it): ?>
              <li class="item<?= !empty($it['hidden']) ? ' is-hidden' : '' ?>" id="<?= h($it['id']) ?>">
                <?php if ($th = upload_thumb($it['image'] ?? '')): ?><img src="<?= h($th) ?>" alt="" width="56" height="56" loading="lazy"><?php else: ?><span class="noimg" aria-hidden="true"></span><?php endif; ?>
                <div class="item-text">
                  <strong><?= h($it['name']['de']) ?></strong>
                  <?php if (!empty($it['hidden'])): ?><span class="badge">ausgeblendet</span><?php endif; ?>
                  <span class="muted small"><?= h($it['price'] !== '' ? $it['price'] . (is_numeric(str_replace(',', '.', $it['price'])) ? ' €' : '') : 'ohne Preis') ?><?= $it['tags'] ? ' · ' . h(implode(', ', array_map(fn($t) => TAG_LABELS[$t] ?? $t, $it['tags']))) : '' ?></span>
                </div>
                <div class="row-actions">
                  <?= action_btn('item_move', $it['id'], '↑', 'Nach oben', ['dir' => 'up'], $ii === 0) ?>
                  <?= action_btn('item_move', $it['id'], '↓', 'Nach unten', ['dir' => 'down'], $ii === count($c['items']) - 1) ?>
                  <a class="btn btn-sm" href="<?= h(admin_url('item', ['id' => $it['id']])) ?>">Bearbeiten</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <a class="btn btn-sm btn-soft" href="<?= h(admin_url('item', ['cat' => $c['id']])) ?>">+ Gericht in „<?= h($c['name']['de']) ?>“</a>
      </section>
    <?php endforeach;
    admin_foot();
    break;

/* ===== Kategorie bearbeiten ===== */
case 'cat':
    $c = null;
    foreach ($menu['categories'] as $x) if ($x['id'] === ($_GET['id'] ?? '')) $c = $x;
    $c ??= ['id' => '', 'name' => empty_i18n(), 'note' => empty_i18n(), 'hidden' => false, 'items' => []];
    if ($old) { $c['name'] = $old['name'] ?? $c['name']; $c['note'] = $old['note'] ?? $c['note']; }
    admin_head($c['id'] ? 'Kategorie bearbeiten' : 'Neue Kategorie', 'menu');
    ?>
    <p><a href="<?= h(admin_url('menu')) ?>">← Zurück zur Speisekarte</a></p>
    <h1><?= $c['id'] ? 'Kategorie bearbeiten' : 'Neue Kategorie' ?></h1>
    <form method="post" class="card form">
      <?= csrf_field() ?><input type="hidden" name="a" value="cat_save"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
      <?= i18n_fields('name', $c['name'], 'Name', false, true) ?>
      <?= i18n_fields('note', $c['note'], 'Hinweis (optional)', true, false, 'Erscheint klein unter der Überschrift, z. B. „Bis 14 Uhr“ oder „Alle Frühstücke mit Kaffee oder Tee“.') ?>
      <label class="check"><input type="checkbox" name="hidden" value="1"<?= !empty($c['hidden']) ? ' checked' : '' ?>> Auf der Website ausblenden</label>
      <div class="form-actions"><button class="btn btn-primary">Speichern</button><a class="btn" href="<?= h(admin_url('menu')) ?>">Abbrechen</a></div>
    </form>
    <?php if ($c['id']): ?>
      <div class="card danger">
        <h2>Kategorie löschen</h2>
        <p>Löscht die Kategorie und alle <?= count($c['items'] ?? []) ?> Gerichte darin. Das lässt sich nicht rückgängig machen. Tipp: Zum vorübergehenden Verstecken lieber „ausblenden“ nutzen.</p>
        <?= delete_form('cat_delete', $c['id'], 'Kategorie endgültig löschen') ?>
      </div>
    <?php endif;
    admin_foot();
    break;

/* ===== Gericht bearbeiten ===== */
case 'item':
    $it = null;
    $catId = (string) ($_GET['cat'] ?? '');
    foreach ($menu['categories'] as $x) foreach ($x['items'] ?? [] as $y) if ($y['id'] === ($_GET['id'] ?? '')) { $it = $y; $catId = $x['id']; }
    $it ??= ['id' => '', 'name' => empty_i18n(), 'desc' => empty_i18n(), 'price' => '', 'tags' => [], 'image' => '', 'hidden' => false];
    if ($old) { foreach (['name', 'desc', 'price', 'tags'] as $k) if (isset($old[$k])) $it[$k] = $old[$k]; $catId = $old['cat'] ?? $catId; }
    if (!$menu['categories']) { flash('Bitte zuerst eine Kategorie anlegen.', 'error'); redirect(admin_url('cat')); }
    admin_head($it['id'] ? 'Gericht bearbeiten' : 'Neues Gericht', 'menu');
    ?>
    <p><a href="<?= h(admin_url('menu')) ?>">← Zurück zur Speisekarte</a></p>
    <h1><?= $it['id'] ? 'Gericht bearbeiten' : 'Neues Gericht' ?></h1>
    <form method="post" class="card form" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="a" value="item_save"><input type="hidden" name="id" value="<?= h($it['id']) ?>">
      <div class="field"><label for="cat">Kategorie</label>
        <select id="cat" name="cat"><?php foreach ($menu['categories'] as $x): ?><option value="<?= h($x['id']) ?>"<?= $x['id'] === $catId ? ' selected' : '' ?>><?= h($x['name']['de']) ?></option><?php endforeach; ?></select>
      </div>
      <?= i18n_fields('name', $it['name'], 'Name', false, true) ?>
      <?= i18n_fields('desc', $it['desc'], 'Beschreibung (optional)', true, false, 'Kurz und appetitlich, z. B. „Sauerteigbrot, Avocado, pochiertes Ei, Kresse“.') ?>
      <div class="field"><label for="price">Preis</label>
        <p class="hint">Nur die Zahl, z. B. <code>12,50</code>. Das €-Zeichen kommt automatisch. Auch Text ist möglich, z. B. „Tagespreis“.</p>
        <input id="price" type="text" name="price" value="<?= h($it['price']) ?>" inputmode="decimal" maxlength="30" class="w-sm"></div>
      <fieldset class="field"><legend>Kennzeichnung</legend>
        <div class="chips">
        <?php foreach (TAG_LABELS as $k => $label): ?>
          <label class="chip"><input type="checkbox" name="tags[]" value="<?= h($k) ?>"<?= in_array($k, $it['tags'], true) ? ' checked' : '' ?>> <?= h($label) ?></label>
        <?php endforeach; ?>
        </div>
      </fieldset>
      <?= image_picker($it['image'] ?? '', 'item') ?>
      <label class="check"><input type="checkbox" name="hidden" value="1"<?= !empty($it['hidden']) ? ' checked' : '' ?>> Auf der Website ausblenden (z. B. gerade nicht verfügbar)</label>
      <div class="form-actions"><button class="btn btn-primary">Speichern</button><a class="btn" href="<?= h(admin_url('menu')) ?>">Abbrechen</a></div>
    </form>
    <?php if ($it['id']): ?>
      <div class="card danger">
        <h2>Gericht löschen</h2>
        <p>Entfernt das Gericht dauerhaft von der Karte. Zum vorübergehenden Verstecken lieber „ausblenden“ nutzen.</p>
        <?= delete_form('item_delete', $it['id'], 'Gericht endgültig löschen') ?>
      </div>
    <?php endif;
    admin_foot();
    break;

/* ===== Öffnungszeiten ===== */
case 'hours':
    $hrs = hours();
    admin_head('Öffnungszeiten', 'hours');
    ?>
    <h1>Öffnungszeiten</h1>
    <p class="muted">Bis zu zwei Zeiträume pro Tag, z. B. 09:00–14:00 für Frühstück und 17:00–22:00 für Tapas. Leere Felder werden ignoriert.</p>
    <form method="post" class="card form">
      <?= csrf_field() ?><input type="hidden" name="a" value="hours_save">
      <div class="hours-table">
        <?php foreach (DAYS as $d): $day = $hrs['days'][$d]; $r = $day['ranges']; ?>
          <fieldset class="hours-row" data-day>
            <legend><?= h(t('day.' . $d)) ?></legend>
            <label class="check closed-toggle"><input type="checkbox" name="<?= $d ?>_closed" value="1"<?= $day['closed'] ? ' checked' : '' ?> data-closed> Ruhetag</label>
            <div class="ranges">
              <?php foreach ([1, 2] as $n): ?>
                <div class="range">
                  <label><span class="sr-only"><?= h(t('day.' . $d)) ?> Zeitraum <?= $n ?> von</span><input type="time" name="<?= $d ?>_from<?= $n ?>" value="<?= h($r[$n - 1][0] ?? '') ?>"></label>
                  <span aria-hidden="true">–</span>
                  <label><span class="sr-only">bis</span><input type="time" name="<?= $d ?>_to<?= $n ?>" value="<?= h($r[$n - 1][1] ?? '') ?>"></label>
                </div>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endforeach; ?>
      </div>
      <p class="small"><button type="button" class="btn-link" data-copy-monday>Montag auf Dienstag bis Freitag übertragen</button></p>
      <?= i18n_fields('note', $hrs['note'], 'Zusatz zu den Öffnungszeiten (optional)', false, false, 'Erscheint unter den Zeiten, z. B. „Küche schließt 30 Minuten vorher.“') ?>
      <?= i18n_fields('notice', $s['notice'], 'Hinweis-Leiste ganz oben (optional)', true, false, 'Für Betriebsferien, Feiertage oder Sonderöffnungen. Leer lassen, wenn es nichts zu sagen gibt.') ?>
      <div class="form-actions"><button class="btn btn-primary">Speichern</button></div>
    </form>
    <?php
    admin_foot();
    break;

/* ===== Bilder ===== */
case 'images':
    $lib = library_images();
    admin_head('Bilder', 'images');
    ?>
    <h1>Bilder</h1>
    <p class="muted">Hier tauschst du die festen Bilder der Website aus und verwaltest alle hochgeladenen Bilder.</p>

    <?php $missingImport = array_diff(array_values(OLD_SITE_IMAGES), $s['imported'] ?? []); if ($missingImport): ?>
      <div class="card import">
        <h2>Bilder von der alten Website übernehmen</h2>
        <p>Lädt das Logo und <?= count($missingImport) - (in_array(OLD_SITE_IMAGES['logo'], $missingImport, true) ? 1 : 0) ?> Fotos von konvergenz.me ins Bildarchiv und setzt sie auf die noch leeren Bildplätze. Das funktioniert nur, solange die alte Website noch online ist.</p>
        <form method="post" data-busy="Bilder werden geladen …"><?= csrf_field() ?><input type="hidden" name="a" value="import_old"><button class="btn btn-primary">Jetzt übernehmen</button></form>
      </div>
    <?php endif; ?>

    <h2 class="h-section">Bilder der Website</h2>
    <div class="slots">
      <?php foreach (IMAGE_SLOTS as $slot => $info): $cur = $s['images'][$slot] ?? ''; ?>
        <form method="post" enctype="multipart/form-data" class="card slot" id="slot-<?= h($slot) ?>">
          <?= csrf_field() ?><input type="hidden" name="a" value="slot_save"><input type="hidden" name="slot" value="<?= h($slot) ?>">
          <h3><?= h($info['label']) ?></h3>
          <p class="hint"><?= h($info['hint']) ?></p>
          <div class="slot-preview<?= $slot === 'logo' ? ' is-logo' : '' ?>">
            <?php if ($u = upload_thumb($cur)): ?><img src="<?= h($u) ?>" alt="Aktuelles Bild: <?= h($info['label']) ?>" loading="lazy"><?php else: ?><span class="muted small">Noch kein Bild, die Website zeigt einen Platzhalter.</span><?php endif; ?>
          </div>
          <?= image_picker($cur, $slot) ?>
          <button class="btn btn-primary btn-sm">Speichern</button>
        </form>
      <?php endforeach; ?>
    </div>

    <h2 class="h-section" id="archiv">Bildarchiv <span class="muted">(<?= count($lib) ?>)</span></h2>
    <form method="post" enctype="multipart/form-data" class="card form" data-busy="Bilder werden hochgeladen …">
      <?= csrf_field() ?><input type="hidden" name="a" value="library_upload">
      <label class="upload"><span>Mehrere Bilder auswählen</span><input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif"></label>
      <button class="btn btn-primary btn-sm">Hochladen</button>
    </form>
    <?php if ($lib): ?>
      <ul class="library">
        <?php foreach ($lib as $f): $uses = image_usage($f); ?>
          <li class="card lib-item">
            <img src="<?= h(upload_thumb($f)) ?>" alt="" loading="lazy">
            <p class="small <?= $uses ? '' : 'muted' ?>"><?= $uses ? 'Verwendet: ' . h(implode(', ', $uses)) : 'Nicht verwendet' ?></p>
            <?php if (!$uses): ?><?= delete_form('library_delete', '', 'Löschen', ['file' => $f]) ?><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif;
    admin_foot();
    break;

/* ===== Einstellungen ===== */
case 'settings':
    $v = fn($k) => old($k, $s[$k] ?? '');
    admin_head('Einstellungen', 'settings');
    ?>
    <h1>Einstellungen</h1>
    <form method="post" class="card form">
      <?= csrf_field() ?><input type="hidden" name="a" value="settings_save">
      <h2>Kontakt und Adresse</h2>
      <p class="hint">Diese Angaben erscheinen auf der Website, im Impressum und in der Datenschutzerklärung.</p>
      <div class="grid-2">
        <?php foreach (['name' => 'Name des Cafés', 'owner' => 'Inhaber', 'street' => 'Straße und Hausnummer', 'zip' => 'PLZ', 'city' => 'Ort', 'phone' => 'Telefon', 'email' => 'E-Mail'] as $k => $label): ?>
          <div class="field"><label for="<?= $k ?>"><?= h($label) ?> <span class="req">*</span></label><input id="<?= $k ?>" name="<?= $k ?>" value="<?= h($v($k)) ?>" required<?= $k === 'email' ? ' type="email"' : ($k === 'phone' ? ' type="tel"' : '') ?>></div>
        <?php endforeach; ?>
        <div class="field"><label for="vat_id">USt-IdNr. (optional)</label><input id="vat_id" name="vat_id" value="<?= h($v('vat_id')) ?>"></div>
      </div>
      <h2>Links</h2>
      <div class="field"><label for="reservation_url">Reservierungslink (resmio)</label><p class="hint">Sobald hier ein Link steht, erscheint auf der Website ein Button „Tisch reservieren“.</p><input id="reservation_url" name="reservation_url" type="url" value="<?= h($v('reservation_url')) ?>" placeholder="https://…"></div>
      <div class="field"><label for="instagram">Instagram</label><input id="instagram" name="instagram" type="url" value="<?= h($v('instagram')) ?>"></div>
      <div class="field"><label for="maps_url">Google-Maps-Link</label><p class="hint">Ziel beim Klick auf das Kartenbild. Am besten in Google Maps beim Café auf „Teilen“ gehen und den Link hier einfügen.</p><input id="maps_url" name="maps_url" type="url" value="<?= h($v('maps_url')) ?>"></div>
      <div class="field"><label for="map_credit">Quellenangabe unter dem Kartenbild</label><input id="map_credit" name="map_credit" value="<?= h($v('map_credit')) ?>"></div>
      <h2>Anfahrt</h2>
      <?= i18n_fields('directions', old('directions', $s['directions']), 'Anfahrtstext (Kontaktseite)', true, false, 'Leer lassen für den Standardtext. Absätze mit Zeilenumbruch trennen.') ?>
      <h2>Hosting</h2>
      <div class="field"><label for="hoster">Webhoster (für die Datenschutzerklärung)</label><textarea id="hoster" name="hoster" rows="3"><?= h($v('hoster')) ?></textarea></div>
      <div class="form-actions"><button class="btn btn-primary">Speichern</button></div>
    </form>

    <div class="card">
      <h2>Wartungsmodus</h2>
      <p>Zeigt Besuchern eine freundliche Hinweisseite („Wir sind gleich wieder da“). Du selbst siehst die Website weiterhin, solange du angemeldet bist.</p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="maintenance"><input type="hidden" name="back" value="settings">
        <input type="hidden" name="on" value="<?= empty($s['maintenance']) ? '1' : '0' ?>">
        <button class="btn <?= empty($s['maintenance']) ? '' : 'btn-primary' ?>"><?= empty($s['maintenance']) ? 'Wartungsmodus einschalten' : 'Wartungsmodus ausschalten' ?></button>
      </form>
    </div>
    <div class="card"><h2>Passwort</h2><p><a class="btn" href="<?= h(admin_url('password')) ?>">Passwort ändern</a></p></div>
    <?php
    admin_foot();
    break;

/* ===== Passwort ===== */
case 'password':
    admin_head('Passwort ändern', 'settings');
    ?>
    <p><a href="<?= h(admin_url('settings')) ?>">← Zurück zu den Einstellungen</a></p>
    <h1>Passwort ändern</h1>
    <form method="post" class="card form narrow">
      <?= csrf_field() ?><input type="hidden" name="a" value="password">
      <div class="field"><label for="old">Aktuelles Passwort</label><input id="old" type="password" name="old" required autocomplete="current-password"></div>
      <div class="field"><label for="pw">Neues Passwort (mind. 10 Zeichen)</label><input id="pw" type="password" name="pw" minlength="10" required autocomplete="new-password"></div>
      <div class="field"><label for="pw2">Neues Passwort wiederholen</label><input id="pw2" type="password" name="pw2" minlength="10" required autocomplete="new-password"></div>
      <div class="form-actions"><button class="btn btn-primary">Passwort ändern</button></div>
    </form>
    <?php
    admin_foot();
    break;

/* ===== Übersicht ===== */
default:
    $st = open_status();
    $itemCount = array_sum(array_map(fn($c) => count($c['items'] ?? []), $menu['categories']));
    $missingSlots = array_filter(array_keys(IMAGE_SLOTS), fn($k) => !site_image($k));
    admin_head('Übersicht', 'start');
    ?>
    <h1>Moin<?= $s['owner'] ? ', ' . h(explode(' ', $s['owner'])[0]) : '' ?>!</h1>
    <p class="muted">Was möchtest du heute ändern?</p>
    <div class="tiles">
      <a class="tile" href="<?= h(admin_url('menu')) ?>"><span class="tile-k">Speisekarte</span><strong><?= $itemCount ?> Gerichte</strong><span class="muted small">in <?= count($menu['categories']) ?> Kategorien</span></a>
      <a class="tile" href="<?= h(admin_url('hours')) ?>"><span class="tile-k">Öffnungszeiten</span><strong><?= !$st['has_data'] ? 'Noch nicht eingetragen' : ($st['closed_today'] ? 'Heute geschlossen' : 'Heute ' . h(format_ranges($st['today']))) ?></strong><span class="muted small"><?= tr($s['notice']) !== '' ? 'Hinweis-Leiste ist aktiv' : 'Keine Hinweis-Leiste' ?></span></a>
      <a class="tile" href="<?= h(admin_url('images')) ?>"><span class="tile-k">Bilder</span><strong><?= count(library_images()) ?> im Archiv</strong><span class="muted small"><?= $missingSlots ? count($missingSlots) . ' Bildplätze noch leer' : 'Alle Bildplätze belegt' ?></span></a>
      <a class="tile" href="<?= h(admin_url('settings')) ?>"><span class="tile-k">Einstellungen</span><strong>Kontakt & Links</strong><span class="muted small"><?= $s['reservation_url'] ? 'Reservierungslink aktiv' : 'Reservierungslink fehlt noch' ?></span></a>
    </div>
    <?php
    admin_foot();
}

/* ---------- kleine Formular-Bausteine ---------- */
function action_btn(string $action, string $id, string $label, string $title, array $extra = [], bool $disabled = false): string
{
    $html = '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="a" value="' . h($action) . '"><input type="hidden" name="id" value="' . h($id) . '">';
    foreach ($extra as $k => $v) $html .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
    return $html . '<button class="btn btn-sm btn-icon" title="' . h($title) . '" aria-label="' . h($title) . '"' . ($disabled ? ' disabled' : '') . '>' . h($label) . '</button></form>';
}

function delete_form(string $action, string $id, string $label, array $extra = []): string
{
    $html = '<details class="confirm"><summary class="btn btn-sm btn-danger-soft">' . h($label) . '</summary><form method="post">' . csrf_field()
        . '<input type="hidden" name="a" value="' . h($action) . '"><input type="hidden" name="id" value="' . h($id) . '">';
    foreach ($extra as $k => $v) $html .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
    return $html . '<p class="small">Wirklich löschen?</p><button class="btn btn-sm btn-danger">Ja, löschen</button></form></details>';
}
