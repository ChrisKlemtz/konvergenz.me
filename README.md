# Konvergenz 53 – Website

Schlanke PHP-Website ohne Datenbank. Läuft auf jedem Webhosting mit **PHP 8.1 oder neuer** und Apache (Strato, IONOS und andere). Alle Inhalte liegen als JSON-Dateien im Ordner `data/`, Bilder in `uploads/`.

## Aufbau

| Ordner / Datei | Inhalt |
|---|---|
| `index.php` | Frontcontroller: alle Seitenaufrufe laufen hierüber |
| `pages/` | Startseite, Speisekarte, Kontakt, Impressum, Datenschutz, 404 |
| `lang/` | Texte in Deutsch, Englisch, Dänisch, Schwedisch |
| `inc/` | Grundfunktionen, Layout, Bildverarbeitung |
| `admin/` | Passwortgeschütztes Dashboard für den Inhaber |
| `data/` | Inhalte (Speisekarte, Öffnungszeiten, Einstellungen, Passwort-Hash). Per `.htaccess` gesperrt |
| `uploads/` | Hochgeladene Bilder (WebP). PHP-Ausführung per `.htaccess` gesperrt |
| `errors/` | Statische Fehlerseiten 500 und 503 |
| `assets/` | CSS, JavaScript, Schriften, Favicon |

Seiten-Adressen: `/`, `/speisekarte`, `/kontakt`, `/impressum`, `/datenschutz`, für andere Sprachen mit Präfix, z. B. `/en/menu`, `/da/menukort`, `/sv/meny`. Dazu `/sitemap.xml`.

## Lokal ansehen

```bash
php -S localhost:8000 index.php
```

Dann `http://localhost:8000` und `http://localhost:8000/admin/` öffnen.

## Online stellen

1. Gesamten Ordnerinhalt per SFTP/FTP in das Webverzeichnis der Domain laden (inklusive der versteckten `.htaccess`-Dateien und `data/.setup-erlaubt`).
2. Schreibrechte prüfen: `data/` und `uploads/` müssen für PHP beschreibbar sein (bei Strato/IONOS meist schon so).
3. Im Hosting-Paket PHP 8.1 oder neuer einstellen und SSL aktivieren. Danach in `.htaccess` die zwei Zeilen „HTTPS erzwingen“ einkommentieren.
4. `/admin/` aufrufen und **sofort** das Passwort festlegen. Die Datei `data/.setup-erlaubt` wird dabei automatisch gelöscht.
5. Unter „Bilder“ auf **Jetzt übernehmen** klicken, solange die alte Website noch erreichbar ist (lädt Logo und Instagram-Fotos von konvergenz.me).
6. Unter „Einstellungen“ den resmio-Link eintragen und den Webhoster für die Datenschutzerklärung prüfen.

Die Seite muss im Hauptverzeichnis der Domain liegen, damit die Fehlerseiten 500/503 greifen. Alles andere funktioniert auch in einem Unterordner.

## An den Kunden übertragen

Es gibt keine Datenbank. Umzug = Ordner kopieren:

1. Kompletten Ordner vom alten Webspace herunterladen (inklusive `data/` und `uploads/`).
2. Auf den neuen Webspace hochladen, Domain umstellen. Fertig.

## Passwort vergessen

Per FTP `data/auth.json` löschen und eine leere Datei `data/.setup-erlaubt` anlegen. Dann unter `/admin/` ein neues Passwort festlegen.

## Schrift Nantes

Überschriften nutzen Nantes (Luzi Type). Die Schrift ist kostenpflichtig, für die Website wird eine **Webfont-Lizenz** gebraucht. Mit Lizenz die Dateien als `assets/fonts/nantes-regular.woff2` (optional `nantes-italic.woff2`) ablegen, sie werden dann automatisch eingebunden. Bis dahin wird Lora (freie Lizenz, SIL OFL) angezeigt. Fließtext: Inter (SIL OFL). Alle Schriften liegen lokal, es gibt keine Verbindung zu Google Fonts.

## Datensicherung

Regelmäßig `data/` und `uploads/` herunterladen. Mehr ist nicht nötig.

## Technische Eckdaten

- Kein Tracking, keine Cookies für Besucher, keine externen Skripte
- Passwort mit `password_hash`, Sperre nach 5 Fehlversuchen für 15 Minuten, CSRF-Schutz, Session-Cookie `HttpOnly` + `SameSite=Strict`
- Hochgeladene Bilder werden geprüft, neu kodiert (entfernt Metadaten/GPS), auf 1800 px verkleinert und als WebP gespeichert
- Strukturierte Daten (schema.org/Restaurant) mit Öffnungszeiten, hreflang für 4 Sprachen, Sitemap
- `prefers-reduced-motion` wird berücksichtigt, Navigation per Tastatur bedienbar
