<?php
$s = settings();
layout_head('privacy', t('privacy.title'), t('meta.legal'));
?>
<section class="page-hero page-hero-sm">
  <div class="wrap"><h1><?= h(t('privacy.title')) ?></h1></div>
</section>
<section class="section section-light">
  <div class="wrap narrow legal" lang="de">
    <?php if (t('legal.de_only') !== ''): ?><p class="legal-note" lang="<?= h(lang()) ?>"><?= h(t('legal.de_only')) ?></p><?php endif; ?>

    <p class="legal-lead">Kurz gesagt: Diese Website setzt keine Tracking-Tools, keine Werbe-Cookies und keine eingebetteten Dienste von Drittanbietern ein. Wir verarbeiten nur die Daten, die für den Betrieb der Seite technisch nötig sind.</p>

    <h2>1. Verantwortlicher</h2>
    <p><?= h($s['name']) ?>, Inhaber <?= h($s['owner']) ?><br><?= h($s['street']) ?>, <?= h($s['zip'] . ' ' . $s['city']) ?><br>
    Telefon: <?= h($s['phone']) ?><br>E-Mail: <a href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a></p>

    <h2>2. Hosting und Server-Logfiles</h2>
    <p>Diese Website wird bei folgendem Anbieter gehostet:</p>
    <p><?= nl2br(h($s['hoster'])) ?></p>
    <p>Beim Aufruf der Website verarbeitet der Server automatisch Informationen, die dein Browser übermittelt: IP-Adresse, Datum und Uhrzeit des Abrufs, aufgerufene Seite, übertragene Datenmenge, Referrer-URL, Browsertyp und Betriebssystem. Diese Daten sind technisch erforderlich, um die Website auszuliefern und ihre Sicherheit zu gewährleisten (Art. 6 Abs. 1 lit. f DSGVO). Die Logfiles werden vom Hoster nach kurzer Zeit gelöscht. Mit dem Hoster besteht ein Vertrag zur Auftragsverarbeitung (Art. 28 DSGVO).</p>

    <h2>3. Cookies</h2>
    <p>Für Besucherinnen und Besucher setzt diese Website keine Cookies. Lediglich im passwortgeschützten Verwaltungsbereich wird für den Inhaber ein technisch notwendiges Sitzungs-Cookie gesetzt, das beim Schließen des Browsers gelöscht wird (§ 25 Abs. 2 TDDDG).</p>

    <h2>4. Schriftarten</h2>
    <p>Die verwendeten Schriftarten sind lokal auf unserem Server gespeichert. Beim Aufruf der Seite wird keine Verbindung zu Servern von Drittanbietern (z. B. Google Fonts) aufgebaut.</p>

    <h2>5. Kontakt per Telefon oder E-Mail</h2>
    <p>Wenn du uns anrufst oder schreibst, verarbeiten wir deine Angaben (z. B. Name, Telefonnummer, E-Mail-Adresse, Inhalt der Anfrage), um dein Anliegen zu bearbeiten (Art. 6 Abs. 1 lit. b bzw. lit. f DSGVO). Die Daten löschen wir, sobald sie dafür nicht mehr benötigt werden und keine gesetzlichen Aufbewahrungspflichten bestehen.</p>

    <h2>6. Links zu externen Diensten</h2>
    <p>Auf unserer Website verlinken wir auf Google Maps (Google Ireland Limited), unser Instagram-Profil (Meta Platforms Ireland Limited)<?= $s['reservation_url'] !== '' ? ' und unser Online-Reservierungssystem' : '' ?>. Es handelt sich um einfache Links: Die Kartenansicht auf unserer Seite ist ein lokal gespeichertes Bild. Erst wenn du einen dieser Links anklickst, wirst du auf die Website des jeweiligen Anbieters weitergeleitet. Dort gelten dessen Datenschutzbestimmungen. Dabei kann es auch zu einer Übermittlung von Daten in Drittländer wie die USA kommen.</p>

    <h2>7. Deine Rechte</h2>
    <p>Du hast jederzeit das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) und Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21). Wende dich dazu einfach an die oben genannten Kontaktdaten.</p>
    <p>Außerdem kannst du dich bei einer Datenschutz-Aufsichtsbehörde beschweren, zum Beispiel beim Landesbeauftragten für Datenschutz und Informationsfreiheit Mecklenburg-Vorpommern, Werderstraße 74a, 19055 Schwerin.</p>

    <p class="legal-date">Stand: Oktober 2026</p>
  </div>
</section>
<?php layout_foot();
