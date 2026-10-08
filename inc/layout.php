<?php
declare(strict_types=1);

/** Einfache Strich-Icons (24×24, currentColor) */
function icon(string $name, string $class = 'icon'): string
{
    $p = [
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
        'flame' => '<path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-10-2 2-3 4-3 6-1-1-2-2-2-4-2 2-5 5-5 8a7 7 0 0 0 7 7z"/>',
        'leaf' => '<path d="M5 19C5 10 11 4 20 4c0 9-6 15-15 15z"/><path d="M5 19 13 11"/>',
        'wheat' => '<path d="M12 21V9M12 9c-2-1-3-3-3-5 2 1 3 3 3 5zm0 0c2-1 3-3 3-5-2 1-3 3-3 5zM12 14c-2-1-3-3-3-5 2 1 3 3 3 5zm0 0c2-1 3-3 3-5-2 1-3 3-3 5zM4 4l16 16"/>',
        'pan' => '<circle cx="10" cy="13" r="6"/><path d="M16 13h6"/><path d="M8 6c0-1 1-2 1-3M11 6c0-1 1-2 1-3"/>',
        'map' => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
    ][$name] ?? '';
    return '<svg class="' . h($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/** Art-Déco-Fächer als Trenner */
function ornament(string $class = 'ornament'): string
{
    return '<svg class="' . h($class) . '" viewBox="0 0 120 24" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1"><path d="M0 20h44M76 20h44"/><path d="M60 20 48 6M60 20 52 3M60 20V2M60 20l8-17M60 20 72 6"/><path d="M50 20a10 10 0 0 1 20 0"/></g></svg>';
}

function full_address(): string
{
    $s = settings();
    return $s['street'] . ', ' . $s['zip'] . ' ' . $s['city'];
}

function tel_link(string $phone): string
{
    $digits = preg_replace('/[^\d+]/', '', $phone);
    if (str_starts_with($digits, '0')) $digits = '+49' . substr($digits, 1);
    return 'tel:' . $digits;
}

/** Bindet Nantes nur ein, wenn die lizenzierten Dateien hochgeladen wurden */
function nantes_fontface(): string
{
    $css = '';
    foreach (['regular' => 'normal', 'italic' => 'italic'] as $file => $style) {
        $src = [];
        foreach (['woff2', 'woff'] as $ext) {
            if (is_file(APP_ROOT . "/assets/fonts/nantes-$file.$ext")) {
                $src[] = 'url("' . url("assets/fonts/nantes-$file.$ext") . '") format("' . $ext . '")';
            }
        }
        if ($src) {
            $css .= '@font-face{font-family:"Nantes";src:' . implode(',', $src) . ";font-weight:400;font-style:$style;font-display:swap}";
        }
    }
    return $css ? "<style>$css</style>" : '';
}

function json_ld(): string
{
    $s = settings();
    $h = hours();
    $map = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
    $spec = [];
    foreach (DAYS as $d) {
        if ($h['days'][$d]['closed']) continue;
        foreach ($h['days'][$d]['ranges'] as [$a, $b]) {
            $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $map[$d], 'opens' => $a, 'closes' => $b];
        }
    }
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => $s['name'],
        'url' => absolute_url(page_url('home')),
        'telephone' => $s['phone'],
        'email' => $s['email'],
        'servesCuisine' => ['Frühstück', 'Brunch', 'Tapas', 'Vegan'],
        'priceRange' => '€€',
        'hasMenu' => absolute_url(page_url('menu')),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $s['street'],
            'postalCode' => $s['zip'],
            'addressLocality' => $s['city'],
            'addressCountry' => 'DE',
        ],
        'sameAs' => array_values(array_filter([$s['instagram']])),
    ];
    if ($spec) $data['openingHoursSpecification'] = $spec;
    $data['acceptsReservations'] = $s['reservation_url'] !== '' ? $s['reservation_url'] : 'False';
    if ($logo = site_image('logo')) $data['logo'] = absolute_url(ltrim(strtok($logo, '?'), '/'));
    if ($img = site_image('hero')) $data['image'] = absolute_url(ltrim(strtok($img, '?'), '/'));
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function layout_head(string $page, string $title, string $desc, array $opts = []): void
{
    $s = settings();
    $fullTitle = $page === 'home' ? $s['name'] . ' · ' . t('hero.title') : $title . ' · ' . $s['name'];
    $logo = site_image('logo');
    $navPages = ['home', 'menu', 'contact'];
    ?>
<!doctype html>
<html lang="<?= h(lang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.classList.add('js')</script>
<title><?= h($fullTitle) ?></title>
<meta name="description" content="<?= h($desc) ?>">
<?php if (!empty($opts['noindex'])): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta name="theme-color" content="#173327">
<?php if ($page !== 'notfound'): ?>
<link rel="canonical" href="<?= h(absolute_url(page_url($page))) ?>">
<?php foreach (LANGS as $alt): ?>
<link rel="alternate" hreflang="<?= $alt ?>" href="<?= h(absolute_url(page_url($page, $alt))) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= h(absolute_url(page_url($page, 'de'))) ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($fullTitle) ?>">
<meta property="og:description" content="<?= h($desc) ?>">
<?php if ($og = site_image('hero')): ?><meta property="og:image" content="<?= h(absolute_url(ltrim(strtok($og, '?'), '/'))) ?>"><?php endif; ?>
<link rel="icon" href="<?= h(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= h(asset('assets/img/apple-touch-icon.png')) ?>">
<link rel="preload" href="<?= h(url('assets/fonts/inter-regular.woff')) ?>" as="font" type="font/woff" crossorigin>
<link rel="stylesheet" href="<?= h(asset('assets/css/site.css')) ?>">
<?= nantes_fontface() ?>
<script type="application/ld+json"><?= json_ld() ?></script>
<script src="<?= h(asset('assets/js/site.js')) ?>" defer></script>
</head>
<body class="page-<?= h($page) ?>">
<a class="skip" href="#inhalt"><?= h(t('skip')) ?></a>
<?php if (is_admin() && !empty($s['maintenance'])): ?>
<div class="admin-bar">Wartungsmodus aktiv: Nur du siehst die Seite. <a href="<?= h(url('admin/?s=settings')) ?>">Ändern</a></div>
<?php endif; ?>
<?php if (tr($s['notice']) !== ''): ?>
<div class="notice" role="note"><?= icon('info') ?><p><strong><?= h(t('notice.label')) ?>:</strong> <?= h(tr($s['notice'])) ?></p></div>
<?php endif; ?>
<header class="site-header" data-header>
  <div class="wrap header-inner">
    <a class="brand" href="<?= h(page_url('home')) ?>" aria-label="<?= h($s['name']) ?>, <?= h(t('nav.home')) ?>">
      <?php if ($logo): ?>
        <img src="<?= h($logo) ?>" alt="<?= h($s['name']) ?>" width="56" height="56">
      <?php else: ?>
        <span class="brand-word">Konvergenz <span>53</span></span>
      <?php endif; ?>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="hauptnav" data-nav-toggle>
      <?= icon('menu', 'icon icon-open') ?><?= icon('close', 'icon icon-close') ?>
      <span class="sr-only"><?= h(t('nav.toggle')) ?></span>
    </button>
    <nav class="nav" id="hauptnav" aria-label="<?= h(t('nav.main')) ?>" data-nav>
      <ul class="nav-list">
        <?php foreach ($navPages as $np): ?>
          <li><a href="<?= h(page_url($np)) ?>"<?= $np === $page ? ' aria-current="page"' : '' ?>><?= h(t('nav.' . $np)) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-extra">
        <details class="lang">
          <summary><?= icon('globe') ?><span class="sr-only"><?= h(t('lang.label')) ?>: </span><?= strtoupper(h(lang())) ?></summary>
          <ul>
            <?php foreach (LANGS as $alt): ?>
              <li><a href="<?= h(page_url($page === 'notfound' ? 'home' : $page, $alt)) ?>" hreflang="<?= $alt ?>" lang="<?= $alt ?>"<?= $alt === lang() ? ' aria-current="true"' : '' ?>><?= h(LANG_NAMES[$alt]) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </details>
        <?php if ($s['reservation_url'] !== ''): ?>
          <a class="btn btn-brass btn-sm" href="<?= h($s['reservation_url']) ?>" target="_blank" rel="noopener"><?= h(t('nav.reserve')) ?></a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>
<main id="inhalt">
<?php
}

function layout_foot(): void
{
    $s = settings();
    $st = open_status();
    ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <p class="footer-name"><?= h($s['name']) ?></p>
      <p><?= h(t('footer.tagline')) ?></p>
      <?= ornament('ornament ornament-left') ?>
    </div>
    <div>
      <h2 class="footer-h"><?= h(t('contact.address')) ?></h2>
      <p><?= h($s['street']) ?><br><?= h($s['zip'] . ' ' . $s['city']) ?></p>
      <p><a href="<?= h(tel_link($s['phone'])) ?>"><?= h($s['phone']) ?></a><br>
      <a href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a></p>
    </div>
    <div>
      <h2 class="footer-h"><?= h(t('hours.title')) ?></h2>
      <?php if ($st['has_data']): ?>
        <dl class="hours hours-compact">
          <?php foreach (grouped_hours() as $row): ?>
            <div><dt><?= h($row['label']) ?></dt><dd><?= nl2br(h(str_replace(' & ', "\n", $row['text']))) ?></dd></div>
          <?php endforeach; ?>
        </dl>
      <?php else: ?>
        <p><?= h(t('hours.missing')) ?></p>
      <?php endif; ?>
    </div>
    <div>
      <h2 class="footer-h"><?= h(t('footer.legal')) ?></h2>
      <ul class="footer-links">
        <li><a href="<?= h(page_url('imprint')) ?>"><?= h(t('imprint.title')) ?></a></li>
        <li><a href="<?= h(page_url('privacy')) ?>"><?= h(t('privacy.title')) ?></a></li>
        <?php if ($s['instagram'] !== ''): ?>
          <li><a href="<?= h($s['instagram']) ?>" target="_blank" rel="noopener"><?= icon('instagram') ?> Instagram</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <p>© <?= date('Y') ?> <?= h($s['name']) ?>, <?= h($s['city']) ?></p>
  </div>
</footer>
</body>
</html>
<?php
}

/** Öffnungs-Status-Pille */
function status_pill(): string
{
    $st = open_status();
    if (!$st['has_data']) return '';
    $label = $st['open'] ? t('status.open') : t('status.closed');
    $sub = $st['closed_today'] ? t('status.closed_today') : t('status.today', ['time' => format_ranges($st['today'])]);
    return '<p class="status ' . ($st['open'] ? 'is-open' : 'is-closed') . '"><span class="status-dot" aria-hidden="true"></span><span><strong>' . h($label) . '</strong> · ' . h(trim($sub)) . '</span></p>';
}

/** Bild im Bogenrahmen, mit Platzhalter falls (noch) kein Bild da ist */
function arch_image(?string $src, string $alt, string $class = '', bool $eager = false): string
{
    if (!$src) {
        return '<div class="arch arch-empty ' . h($class) . '" aria-hidden="true">' . ornament('ornament') . '</div>';
    }
    return '<div class="arch ' . h($class) . '"><img src="' . h($src) . '" alt="' . h($alt) . '"' . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async"></div>';
}
