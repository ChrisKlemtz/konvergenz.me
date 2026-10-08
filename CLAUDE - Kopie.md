# Konvergenz 53 – Projektkontext

Website für das Café **Konvergenz 53** (Brunch & Tapas), Badenstraße 53, Stralsund. Inhaber: Christoph Sandt. Gebaut von Christoph Klemtz (klemtz.de) als Referenzprojekt.

## Stand
- Noch **kein Webhosting** gebucht (später Strato oder IONOS, normales PHP-Webhosting, kein eigener Server).
- Vorschau für den Kunden über **Vercel** (`vercel.json`, Laufzeit `vercel-php`, Einstieg `api/`). Auf Vercel kann nichts gespeichert werden, das Dashboard funktioniert dort nicht.
- Lokal: `php -S localhost:8000 index.php` (Windows, PHP 8.3 per winget).
- `.github/workflows/deploy.yml` (FTPS-Upload) ist nur manuell startbar, bis Hosting und Secrets stehen.

## Technik
- PHP 8.1+, **keine Datenbank**: Inhalte als JSON in `data/`, Bilder als WebP in `uploads/library/`.
- Frontcontroller `index.php`, Seiten in `pages/`, Layout in `inc/layout.php`, Texte in `lang/{de,en,da,sv}.php`.
- Dashboard `admin/`: Speisekarte, Öffnungszeiten, Bilder, Einstellungen, Wartungsmodus. Ein Passwort (Hash in `data/auth.json`, in `.gitignore`).
- Design-Tokens in `assets/css/site.css` (`:root`). Kein Framework, kein Build-Schritt, kein Tracking, keine externen Ressourcen.

## Entscheidungen
- Sprachen: Deutsch, Englisch, Dänisch, Schwedisch. Ansprache durchgehend **Du**.
- Stil: ruhiges Art déco. Tannengrün, helles Betongrau, Messing als Akzent; Bogen-Bildrahmen, Fächer-Ornament.
- Überschriften: **Nantes** (lizenzpflichtig, Datei als `assets/fonts/nantes-regular.woff2` ablegen), bis dahin Lora. Fließtext: Inter.
- Keine Reservierung/Bestellung auf der Seite, nur Link zu resmio (Link noch offen, im Dashboard eintragbar). Keine Galerie, keine Extras (Newsletter, Events, Gutscheine).
- Frühstück/Brunch und Tapas gleichwertig.

## Logo
- Offizielle SVGs in `assets/img/logo/` (logo, bildmarke, wortmarke; je schwarz, tannengruen, weiss). Logo-Grün: #1E3B2F = `--green-800`.
- Header: komplettes Logo weiß. Footer, 404, Hero-Abzeichen, Bild-Platzhalter: Bildmarke. Helper `logo_src($typ, $farbe)` in `inc/layout.php`.
- Den Schriftzug **nie** in Nantes/Lora nachbauen: Die Überschriften-Schrift ist nur für Seitenüberschriften, nicht fürs Logo.
- Favicons (`favicon.ico`, `assets/img/favicon.svg`, `apple-touch-icon.png`, `icon-192/512.png`, `site.webmanifest`) und `assets/img/og-image.jpg` (1200×630) sind aus den SVGs erzeugt.

## Offene Punkte
- PLZ prüfen: eingetragen 18439 (Altstadt), Nutzer nannte 18437.
- Telefon prüfen: eingetragen 03831 9419143, Telefonbuch nennt 01520 4312900.
- resmio-Link, Kartenbild (z. B. OpenStreetMap-Export), Nantes-Webfont-Lizenz.
- Dänische/schwedische Texte von Muttersprachlern gegenlesen lassen; Rechtstexte prüfen lassen.
- Startseite später mit weiteren Animationen ausbauen.

## Arbeitsweise
- Ergebnisse und Kommunikation auf Deutsch.
- Code-Änderungen per Git; Inhalte (Speisekarte usw.) über das Dashboard. Für die Vercel-Vorschau Inhalte lokal pflegen und `data/` + `uploads/` mitcommitten.
- Auf Windows achten: Pfade mit `/` normalisieren (siehe `base_path()` in `inc/bootstrap.php`).
