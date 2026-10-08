# Konvergenz 53 – Projektkontext

Website für das Café **Konvergenz 53** (Brunch & Tapas), Badenstraße 53, Stralsund. Inhaber: Christoph Sandt. Gebaut von Christoph Klemtz (klemtz.de) als Referenzprojekt (0 €, im Tausch gegen Referenz).

## Stand (Oktober 2026)
- **Vorschau für den Kunden läuft auf Vercel** (funktioniert). Jeder Push auf `main` aktualisiert sie automatisch.
- **Noch kein Webhosting gebucht.** Geplant: IONOS **Webhosting Standard** (PHP 8.2–8.4, SFTP, SSH, Domain + SSL inklusive). Kein Deploy Now, kein eigener Server.
- Repository auf GitHub: `kovergenz.me` (privat). Lokaler Ordner: `X:\klemtz.de\websites\konvergenz.me` (Windows, VS Code, PowerShell).
- Als Nächstes: an der Website selbst weiterarbeiten (Design-Feinschliff, Animationen auf der Startseite, Inhalte).

## Lokal starten
```powershell
php -S localhost:8000 index.php
```
- Seite: http://localhost:8000 · Dashboard: http://localhost:8000/admin/ (Passwort gilt nur lokal, steht in `data/auth.json`, nie committen).
- PHP 8.3 per winget installiert. Falls VS Code `php` nicht findet: VS Code komplett neu starten oder im Terminal
  `$env:Path = [Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [Environment]::GetEnvironmentVariable("Path","User")`
- `php.ini` mit aktivierten Extensions gd, curl, openssl, mbstring, fileinfo, exif (nötig für Bild-Upload und Import).

## Technik
- PHP 8.1+, **keine Datenbank**, kein Framework, kein Build-Schritt. Inhalte als JSON in `data/`, Bilder als WebP in `uploads/library/`.
- `index.php` = Frontcontroller (+ Router für den lokalen PHP-Server, liefert statische Dateien selbst aus).
- `pages/` (home, menu, contact, imprint, privacy, notfound), `inc/layout.php` (Head, Header, Footer, Icons, Logo-Helper), `inc/bootstrap.php` (Pfade, Daten, Öffnungszeiten-Logik), `inc/i18n.php` (Routen je Sprache), `inc/images.php` (GD → WebP), `lang/{de,en,da,sv}.php` (alle UI-Texte).
- Dashboard `admin/index.php`: Speisekarte, Öffnungszeiten (+ Hinweis-Leiste), Bilder (Bildplätze + Archiv + Import von konvergenz.me), Einstellungen, Wartungsmodus, Passwort. CSRF, Login-Sperre nach 5 Fehlversuchen, Bilder werden neu kodiert.
- Design-Tokens in `assets/css/site.css` unter `:root`. Kein Tracking, keine externen Ressourcen, Schriften lokal.
- Routen: DE ohne Präfix (`/speisekarte`, `/kontakt`, `/impressum`, `/datenschutz`), sonst `/en/menu`, `/da/menukort`, `/sv/meny` usw. Rechtstexte nur auf Deutsch (mit Hinweis).
- Pfade immer mit `/` normalisieren, wegen Windows (`base_path()` in `inc/bootstrap.php`).

## Vercel (Vorschau)
- `vercel.json` mit Laufzeit `vercel-php@0.7.3`, Einstiege `api/index.php` und `api/admin.php` (setzen `K53_ENTRY` und `SCRIPT_NAME`).
- **Wichtig:** Die Route `"/" → /api/index.php` muss vor `"handle": "filesystem"` stehen, sonst lädt Vercel `index.php` als Datei herunter. Ebenso bleiben `data|inc|lang|pages|api` und `*.php|md|json` gesperrt.
- Auf Vercel kann nichts gespeichert werden: Dashboard dort nicht nutzbar. Inhalte lokal im Dashboard pflegen, dann `data/*.json` und `uploads/` committen.
- Auf Vercel automatisch `noindex` (Header + Meta), damit die Vorschau nicht bei Google landet.

## Späteres Hosting
- `.github/workflows/deploy.yml` (FTP-Deploy-Action) ist **nur manuell** startbar. Beim Buchen von IONOS: auf **SFTP** umstellen (IONOS listet kein FTP/FTPS), dann Push-Trigger wieder einkommentieren und Secrets setzen.
- Deploy darf `data/*.json` und `uploads/library/**` nie überschreiben (Inhalte leben auf dem Server).
- Nach Live-Gang: in `.htaccess` HTTPS erzwingen einkommentieren, im Dashboard Passwort setzen (`data/.setup-erlaubt`), Hoster in den Einstellungen auf IONOS ändern (Datenschutz).

## Entscheidungen
- Sprachen: Deutsch, Englisch, Dänisch, Schwedisch. Ansprache durchgehend **Du**.
- Stil: ruhiges Art déco (Ausdrucks-Regler 4/10). Tannengrün, helles Betongrau, Messing als Akzent; Bogen-Bildrahmen, Fächer-Ornament, dezente Einblend-Animationen mit `prefers-reduced-motion`.
- Überschriften: **Nantes** (lizenzpflichtig; Datei als `assets/fonts/nantes-regular.woff2` ablegen, wird dann automatisch eingebunden), bis dahin Lora. Fließtext: Inter.
- Keine Reservierung/Bestellung auf der Seite, nur Link zu resmio (Link noch offen, im Dashboard eintragbar). Keine Galerie, keine Extras (Newsletter, Events, Gutscheine).
- Frühstück/Brunch und Tapas gleichwertig.
- Karte auf der Kontaktseite: statisches Bild (Bildplatz „map“), Klick öffnet Google Maps. Keine eingebettete Karte (DSGVO).

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
- Startseite mit weiteren Animationen ausbauen.
- Echte Fotos vom Café folgen; bis dahin Instagram-Bilder von der alten Seite (Import im Dashboard).

## Arbeitsweise
- Ergebnisse und Kommunikation auf Deutsch.
- Code-Änderungen per Git; Inhalte (Speisekarte usw.) über das Dashboard.
- Vor jedem Push lokal prüfen: Startseite, Speisekarte, Kontakt, eine Fremdsprache, Handy-Breite (DevTools), Dashboard.
- Keine Bibliotheken oder externen Dienste einbauen, ohne zu fragen; Seite soll schlank und übertragbar bleiben.
