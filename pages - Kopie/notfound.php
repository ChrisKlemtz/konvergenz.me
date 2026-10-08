<?php
layout_head('notfound', t('notfound.title'), t('meta.home'), ['noindex' => true]);
?>
<section class="page-hero page-hero-full">
  <div class="wrap narrow center">
    <p class="err-code" aria-hidden="true">404</p>
    <img class="mark-center" src="<?= h(logo_src('bildmarke', 'weiss')) ?>" alt="" width="96" height="96">
    <h1><?= h(t('notfound.title')) ?></h1>
    <p class="lead"><?= h(t('notfound.text')) ?></p>
    <div class="actions center">
      <a class="btn btn-brass" href="<?= h(page_url('home')) ?>"><?= h(t('notfound.home')) ?></a>
      <a class="btn btn-ghost" href="<?= h(page_url('menu')) ?>"><?= h(t('nav.menu')) ?></a>
      <a class="btn btn-ghost" href="<?= h(page_url('contact')) ?>"><?= h(t('nav.contact')) ?></a>
    </div>
  </div>
</section>
<?php layout_foot();
