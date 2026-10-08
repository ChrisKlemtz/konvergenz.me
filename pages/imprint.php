<?php
$s = settings();
layout_head('imprint', t('imprint.title'), t('meta.legal'));
?>
<section class="page-hero page-hero-sm">
  <div class="wrap"><h1><?= h(t('imprint.title')) ?></h1></div>
</section>
<section class="section section-light">
  <div class="wrap narrow legal" lang="de">
    <?php if (t('legal.de_only') !== ''): ?><p class="legal-note" lang="<?= h(lang()) ?>"><?= h(t('legal.de_only')) ?></p><?php endif; ?>

    <h2>Angaben gemäß § 5 DDG</h2>
    <p><?= h($s['name']) ?><br>Inhaber: <?= h($s['owner']) ?><br><?= h($s['street']) ?><br><?= h($s['zip'] . ' ' . $s['city']) ?><br>Deutschland</p>

    <h2>Kontakt</h2>
    <p>Telefon: <a href="<?= h(tel_link($s['phone'])) ?>"><?= h($s['phone']) ?></a><br>
    E-Mail: <a href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a></p>

    <?php if (trim($s['vat_id']) !== ''): ?>
    <h2>Umsatzsteuer-ID</h2>
    <p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:<br><?= h($s['vat_id']) ?></p>
    <?php endif; ?>

    <h2>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
    <p><?= h($s['owner']) ?><br><?= h($s['street']) ?>, <?= h($s['zip'] . ' ' . $s['city']) ?></p>

    <h2>Verbraucherstreitbeilegung</h2>
    <p>Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>

    <h2>Haftung für Inhalte und Links</h2>
    <p>Die Inhalte dieser Website erstellen wir mit Sorgfalt. Für die Richtigkeit, Vollständigkeit und Aktualität übernehmen wir jedoch keine Gewähr; maßgeblich sind die Angaben vor Ort. Unsere Website enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Für diese Inhalte ist der jeweilige Anbieter verantwortlich. Bei Bekanntwerden von Rechtsverletzungen entfernen wir entsprechende Links umgehend.</p>

    <h2>Urheberrecht</h2>
    <p>Texte, Fotos und Gestaltung dieser Website unterliegen dem deutschen Urheberrecht. Eine Verwendung außerhalb der Grenzen des Urheberrechts bedarf der vorherigen Zustimmung. Fotos: <?= h($s['name']) ?>.</p>

    <h2>Website</h2>
    <p>Gestaltung und Umsetzung: <a href="https://klemtz.de" target="_blank" rel="noopener">klemtz.de</a></p>
  </div>
</section>
<?php layout_foot();
