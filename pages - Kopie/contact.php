<?php
$s = settings();
$st = open_status();
$map = site_image('map');
$directions = tr($s['directions']) !== '' ? tr($s['directions']) : t('directions.default');
layout_head('contact', t('contact.title'), t('meta.contact'));
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow eyebrow-light reveal"><?= h($s['name']) ?></p>
    <h1 class="reveal"><?= h(t('contact.title')) ?></h1>
    <p class="lead reveal"><?= h(t('contact.lead')) ?></p>
  </div>
</section>

<section class="section section-light">
  <div class="wrap contact-grid">
    <div class="contact-info reveal">
      <h2 class="h3"><?= h(t('contact.reach')) ?></h2>
      <ul class="contact-list" role="list">
        <li><?= icon('pin') ?><div><span class="label"><?= h(t('contact.address')) ?></span>
          <address><?= h($s['name']) ?><br><?= h($s['street']) ?><br><?= h($s['zip'] . ' ' . $s['city']) ?></address></div></li>
        <li><?= icon('phone') ?><div><span class="label"><?= h(t('contact.phone')) ?></span>
          <a href="<?= h(tel_link($s['phone'])) ?>"><?= h($s['phone']) ?></a></div></li>
        <li><?= icon('mail') ?><div><span class="label"><?= h(t('contact.email')) ?></span>
          <a href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a></div></li>
        <?php if ($s['instagram'] !== ''): ?>
        <li><?= icon('instagram') ?><div><span class="label"><?= h(t('contact.instagram')) ?></span>
          <a href="<?= h($s['instagram']) ?>" target="_blank" rel="noopener">@<?= h(basename(rtrim(strtok($s['instagram'], '?'), '/'))) ?></a></div></li>
        <?php endif; ?>
      </ul>
      <?php if ($s['reservation_url'] !== ''): ?>
        <div class="reserve-box">
          <p><?= h(t('reserve.text')) ?></p>
          <a class="btn btn-green" href="<?= h($s['reservation_url']) ?>" target="_blank" rel="noopener"><?= icon('calendar') ?> <?= h(t('cta.reserve')) ?></a>
        </div>
      <?php endif; ?>
    </div>

    <div class="contact-hours reveal">
      <h2 class="h3"><?= h(t('hours.title')) ?></h2>
      <?php if ($st['has_data']): ?>
        <?= status_pill() ?>
        <dl class="hours hours-full">
          <?php foreach (DAYS as $d): $day = hours()['days'][$d]; ?>
            <div<?= (int) date('N') - 1 === array_search($d, DAYS, true) ? ' class="is-today"' : '' ?>>
              <dt><?= h(t('day.' . $d)) ?></dt>
              <dd><?= $day['closed'] || !$day['ranges'] ? h($day['closed'] ? t('hours.closed') : '–') : h(trim(format_ranges($day['ranges']) . ' ' . t('hours.suffix'))) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
        <?php if (tr(hours()['note']) !== ''): ?><p class="hours-note"><?= h(tr(hours()['note'])) ?></p><?php endif; ?>
      <?php else: ?>
        <p><?= h(t('hours.missing')) ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section-stone" aria-labelledby="anfahrt">
  <div class="wrap map-grid">
    <div class="map-col reveal">
    <a class="map-card" href="<?= h($s['maps_url']) ?>" target="_blank" rel="noopener" aria-describedby="map-hint">
      <?php if ($map): ?>
        <img src="<?= h($map) ?>" alt="<?= h(t('contact.map_alt')) ?>" loading="lazy" decoding="async">
      <?php else: ?>
        <span class="map-placeholder" aria-hidden="true">
          <svg viewBox="0 0 400 260" preserveAspectRatio="xMidYMid slice">
            <rect width="400" height="260" fill="currentColor" opacity=".06"/>
            <g fill="none" stroke="currentColor" stroke-width="1" opacity=".22">
              <path d="M-10 70 Q120 40 210 90 T420 60"/><path d="M-10 180 Q100 150 200 170 T420 150"/>
              <path d="M80 -10 Q100 120 70 270"/><path d="M250 -10 Q230 130 270 270"/><path d="M330 -10 Q350 120 320 270"/>
            </g>
            <path d="M0 230 Q140 200 260 240 T400 220 V260 H0z" fill="currentColor" opacity=".1"/>
          </svg>
          <span class="map-pin"><?= icon('pin') ?></span>
        </span>
      <?php endif; ?>
      <span class="map-label"><?= icon('map') ?> <?= h(t('contact.map_open')) ?> <?= icon('external') ?></span>
      <span id="map-hint" class="sr-only"><?= h(t('contact.map_hint')) ?></span>
    </a>
    <?php if ($map && trim($s['map_credit']) !== ''): ?><p class="map-credit"><?= h($s['map_credit']) ?></p><?php endif; ?>
    </div>
    <div class="directions reveal">
      <p class="address-line"><?= icon('pin') ?> <?= h(full_address()) ?></p>
      <h2 id="anfahrt"><?= h(t('contact.directions')) ?></h2>
      <?php foreach (preg_split('/\R+/', $directions) as $para): ?>
        <p><?= h($para) ?></p>
      <?php endforeach; ?>
      <p><a class="link-arrow" href="<?= h($s['maps_url']) ?>" target="_blank" rel="noopener"><?= h(t('cta.maps')) ?> <?= icon('external') ?></a></p>
    </div>
  </div>
</section>
<?php layout_foot();
