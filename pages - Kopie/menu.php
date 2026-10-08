<?php
$cats = array_values(array_filter(menu_data()['categories'], fn($c) => empty($c['hidden'])));
foreach ($cats as &$c) {
    $c['items'] = array_values(array_filter($c['items'] ?? [], fn($i) => empty($i['hidden'])));
}
unset($c);
$cats = array_values(array_filter($cats, fn($c) => tr($c['name']) !== ''));

// lesbare Sprungmarken, z. B. #kleine-fruehstuecke
$seen = [];
foreach ($cats as &$c) {
    $slug = strtolower(strtr(tr($c['name']), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'å' => 'aa', 'æ' => 'ae', 'ø' => 'oe', 'Å' => 'aa', 'Æ' => 'ae', 'Ø' => 'oe']));
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-') ?: $c['id'];
    $c['anchor'] = isset($seen[$slug]) ? $slug . '-' . $c['id'] : $slug;
    $seen[$slug] = true;
}
unset($c);

function money(string $p): string
{
    $p = trim($p);
    $n = str_replace(',', '.', $p);
    if ($p === '' || !is_numeric($n)) return $p;
    $f = number_format((float) $n, 2, lang() === 'en' ? '.' : ',', lang() === 'en' ? ',' : '.');
    return lang() === 'en' ? '€' . $f : $f . ' €';
}

const TAGS = ['vegan' => 'VG', 'vegetarian' => 'V', 'glutenfree' => 'GF', 'lactosefree' => 'LF', 'new' => '★'];
$usedTags = [];

layout_head('menu', t('menu.title'), t('meta.menu'));
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow eyebrow-light reveal"><?= h(settings()['name']) ?></p>
    <h1 class="reveal"><?= h(t('menu.title')) ?></h1>
    <p class="lead reveal"><?= h(t('menu.lead')) ?></p>
  </div>
</section>

<?php if (!$cats): ?>
<section class="section section-light">
  <div class="wrap narrow empty-state reveal">
    <img class="mark-center" src="<?= h(logo_src('bildmarke', 'tannengruen')) ?>" alt="" width="96" height="96">
    <h2><?= h(t('menu.empty.title')) ?></h2>
    <p><?= h(t('menu.empty.text')) ?></p>
    <p class="actions center">
      <a class="btn btn-green" href="<?= h(tel_link(settings()['phone'])) ?>"><?= icon('phone') ?> <?= h(settings()['phone']) ?></a>
    </p>
  </div>
</section>
<?php else: ?>
<nav class="menu-jump" aria-label="<?= h(t('menu.jump')) ?>">
  <div class="wrap">
    <ul role="list">
      <?php foreach ($cats as $c): ?>
        <li><a href="#<?= h($c['anchor']) ?>"><?= h(tr($c['name'])) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
<section class="section section-light menu">
  <div class="wrap menu-wrap">
    <?php foreach ($cats as $ci => $c): ?>
      <section class="menu-cat" id="<?= h($c['anchor']) ?>" aria-labelledby="h-<?= h($c['anchor']) ?>">
        <header class="menu-cat-head reveal">
          <span class="num"><?= sprintf('%02d', $ci + 1) ?></span>
          <h2 id="h-<?= h($c['anchor']) ?>"><?= h(tr($c['name'])) ?></h2>
          <?php if (tr($c['note'] ?? '') !== ''): ?><p class="menu-cat-note"><?= h(tr($c['note'])) ?></p><?php endif; ?>
        </header>
        <?php if ($c['items']): ?>
        <ul class="menu-items" role="list">
          <?php foreach ($c['items'] as $it):
              $thumb = upload_thumb($it['image'] ?? null);
              $tags = array_values(array_intersect(array_keys(TAGS), $it['tags'] ?? []));
              $usedTags = array_unique(array_merge($usedTags, $tags));
          ?>
            <li class="menu-item reveal<?= $thumb ? ' has-img' : '' ?>">
              <?php if ($thumb): ?>
                <img class="menu-img" src="<?= h($thumb) ?>" alt="<?= h(tr($it['name'])) ?>" loading="lazy" decoding="async" width="112" height="112">
              <?php endif; ?>
              <div class="menu-body">
                <div class="menu-line">
                  <h3 class="menu-name"><?= h(tr($it['name'])) ?></h3>
                  <span class="menu-dots" aria-hidden="true"></span>
                  <?php if (trim((string) ($it['price'] ?? '')) !== ''): ?>
                    <span class="menu-price"><?= h(money((string) $it['price'])) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (tr($it['desc'] ?? '') !== ''): ?><p class="menu-desc"><?= h(tr($it['desc'])) ?></p><?php endif; ?>
                <?php if ($tags): ?>
                  <ul class="tags" role="list">
                    <?php foreach ($tags as $tg): ?>
                      <li class="tag tag-<?= h($tg) ?>"><abbr title="<?= h(t('tag.' . $tg)) ?>"><?= h(TAGS[$tg]) ?></abbr><span class="tag-long"> <?= h(t('tag.' . $tg)) ?></span></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <aside class="menu-foot reveal">
      <?php if ($usedTags): ?>
        <p><strong><?= h(t('menu.legend')) ?>:</strong>
          <?php foreach (array_keys(TAGS) as $tg): if (!in_array($tg, $usedTags, true)) continue; ?>
            <span class="legend-item"><span class="tag tag-<?= h($tg) ?>"><?= h(TAGS[$tg]) ?></span> <?= h(t('tag.' . $tg)) ?></span>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
      <p><?= h(t('menu.allergens')) ?> <?= h(t('menu.prices')) ?></p>
    </aside>
  </div>
</section>
<?php endif; ?>
<?php layout_foot();
