<?php
$s = settings();
$st = open_status();
layout_head('home', t('nav.home'), t('meta.home'));
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <p class="eyebrow eyebrow-light reveal"><?= h(t('hero.eyebrow')) ?></p>
      <h1 class="hero-title reveal"><?= h(t('hero.title')) ?></h1>
      <p class="lead reveal"><?= h(t('hero.lead')) ?></p>
      <div class="actions reveal">
        <a class="btn btn-brass" href="<?= h(page_url('menu')) ?>"><?= h(t('cta.menu')) ?> <?= icon('arrow') ?></a>
        <?php if ($s['reservation_url'] !== ''): ?>
          <a class="btn btn-ghost" href="<?= h($s['reservation_url']) ?>" target="_blank" rel="noopener"><?= icon('calendar') ?> <?= h(t('cta.reserve')) ?></a>
        <?php else: ?>
          <a class="btn btn-ghost" href="<?= h(page_url('contact')) ?>"><?= icon('pin') ?> <?= h(t('cta.contact')) ?></a>
        <?php endif; ?>
      </div>
      <div class="reveal"><?= status_pill() ?></div>
    </div>
    <div class="hero-media reveal">
      <?= arch_image(site_image('hero'), t('home.brunch.title') . ' – ' . $s['name'], 'arch-hero', true) ?>
      <span class="hero-badge" aria-hidden="true">N°<b>53</b></span>
    </div>
  </div>
</section>

<section class="section section-light" aria-labelledby="duo-title">
  <div class="wrap">
    <header class="section-head reveal">
      <p class="eyebrow"><span class="num">01</span><?= h(t('home.duo.eyebrow')) ?></p>
      <h2 id="duo-title"><?= h(t('home.duo.title')) ?></h2>
    </header>
    <div class="duo">
      <article class="duo-card reveal">
        <?= arch_image(site_image('brunch'), t('home.brunch.title')) ?>
        <div class="duo-text">
          <p class="duo-time"><?= icon('sun') ?></p>
          <h3><?= h(t('home.brunch.title')) ?></h3>
          <p><?= h(t('home.brunch.text')) ?></p>
        </div>
      </article>
      <article class="duo-card duo-card-offset reveal">
        <?= arch_image(site_image('tapas'), t('home.tapas.title')) ?>
        <div class="duo-text">
          <p class="duo-time"><?= icon('moon') ?></p>
          <h3><?= h(t('home.tapas.title')) ?></h3>
          <p><?= h(t('home.tapas.text')) ?></p>
        </div>
      </article>
    </div>
    <p class="center reveal"><a class="link-arrow" href="<?= h(page_url('menu')) ?>"><?= h(t('cta.menu')) ?> <?= icon('arrow') ?></a></p>
  </div>
</section>

<section class="section section-stone" aria-labelledby="values-title">
  <div class="wrap values-grid">
    <header class="section-head reveal">
      <p class="eyebrow"><span class="num">02</span><?= h(t('home.values.eyebrow')) ?></p>
      <h2 id="values-title"><?= h(t('home.values.title')) ?></h2>
      <?php if ($img = site_image('intro')): ?>
        <?= arch_image($img, t('home.values.title'), 'arch-small') ?>
      <?php endif; ?>
    </header>
    <ul class="values" role="list">
      <?php foreach (['fresh' => 'pan', 'regional' => 'map', 'vegan' => 'leaf', 'gf' => 'wheat'] as $k => $ic): ?>
        <li class="value reveal">
          <span class="value-icon"><?= icon($ic) ?></span>
          <h3><?= h(t('value.' . $k . '.title')) ?></h3>
          <p><?= h(t('value.' . $k . '.text')) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section section-dark" aria-labelledby="visit-title">
  <div class="wrap visit">
    <div class="reveal">
      <p class="eyebrow eyebrow-light"><span class="num">03</span><?= h(t('home.visit.eyebrow')) ?></p>
      <h2 id="visit-title"><?= h(t('home.visit.title')) ?></h2>
      <p class="lead-sm"><?= h(t('home.visit.text')) ?></p>
      <address class="visit-address">
        <?= icon('pin') ?> <span><?= h(full_address()) ?></span><br>
        <?= icon('phone') ?> <a href="<?= h(tel_link($s['phone'])) ?>"><?= h($s['phone']) ?></a>
      </address>
      <div class="actions">
        <a class="btn btn-brass" href="<?= h(page_url('contact')) ?>"><?= h(t('cta.contact')) ?> <?= icon('arrow') ?></a>
      </div>
    </div>
    <div class="visit-hours reveal">
      <h3><?= icon('clock') ?> <?= h(t('hours.title')) ?></h3>
      <?php if ($st['has_data']): ?>
        <?= status_pill() ?>
        <dl class="hours">
          <?php foreach (grouped_hours() as $row): ?>
            <div><dt><?= h($row['label']) ?></dt><dd><?= h($row['text']) ?></dd></div>
          <?php endforeach; ?>
        </dl>
        <?php if (tr(hours()['note']) !== ''): ?><p class="hours-note"><?= h(tr(hours()['note'])) ?></p><?php endif; ?>
      <?php else: ?>
        <p><?= h(t('hours.missing')) ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php layout_foot();
