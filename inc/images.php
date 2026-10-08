<?php
declare(strict_types=1);

const IMG_MAX = 1800;   // längste Kante Vollbild
const IMG_THUMB = 640;  // längste Kante Vorschau
const IMG_MAX_BYTES = 15 * 1024 * 1024;

/**
 * Verarbeitet eine Bilddatei: prüft sie, verkleinert sie, speichert sie als WebP
 * (Vollbild + Vorschau) im Bildarchiv und gibt den Dateinamen relativ zu uploads/ zurück.
 * Durch das Neu-Kodieren werden auch Metadaten (z. B. GPS-Daten) entfernt.
 */
function store_image(string $tmpFile): string
{
    if (!function_exists('imagecreatetruecolor')) {
        throw new RuntimeException('Die PHP-Bildbibliothek GD fehlt auf dem Server.');
    }
    if (!is_file($tmpFile) || filesize($tmpFile) === 0) {
        throw new RuntimeException('Die Datei ist leer.');
    }
    if (filesize($tmpFile) > IMG_MAX_BYTES) {
        throw new RuntimeException('Das Bild ist größer als 15 MB.');
    }
    $info = @getimagesize($tmpFile);
    if (!$info) {
        throw new RuntimeException('Die Datei ist kein unterstütztes Bild (erlaubt: JPG, PNG, WebP, GIF).');
    }
    [$w, $h, $type] = $info;
    if ($w * $h > 40_000_000) {
        throw new RuntimeException('Das Bild hat zu viele Pixel. Bitte vorher verkleinern.');
    }
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($tmpFile),
        IMAGETYPE_PNG => @imagecreatefrompng($tmpFile),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpFile) : false,
        IMAGETYPE_GIF => @imagecreatefromgif($tmpFile),
        default => false,
    };
    if (!$src) {
        throw new RuntimeException('Das Bildformat wird nicht unterstützt (erlaubt: JPG, PNG, WebP, GIF).');
    }

    // Handy-Fotos: EXIF-Ausrichtung berücksichtigen
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmpFile);
        $rot = ['3' => 180, '6' => -90, '8' => 90][(string) ($exif['Orientation'] ?? '')] ?? 0;
        if ($rot) {
            $r = imagerotate($src, $rot, 0);
            if ($r) { imagedestroy($src); $src = $r; }
        }
    }

    $dir = UPLOAD_DIR . '/library';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Der Ordner uploads/library konnte nicht angelegt werden.');
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(5));
    save_resized($src, $dir . '/' . $name . '.webp', IMG_MAX, 82);
    save_resized($src, $dir . '/' . $name . '-sm.webp', IMG_THUMB, 78);
    imagedestroy($src);
    return 'library/' . $name . '.webp';
}

function save_resized(GdImage $src, string $dest, int $max, int $quality): void
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if (!imagewebp($dst, $dest, $quality)) {
        imagedestroy($dst);
        throw new RuntimeException('Das Bild konnte nicht gespeichert werden. Bitte Schreibrechte für "uploads" prüfen.');
    }
    imagedestroy($dst);
}

/** Alle Bilder im Archiv, neueste zuerst */
function library_images(): array
{
    $files = glob(UPLOAD_DIR . '/library/*.webp') ?: [];
    $files = array_filter($files, fn($f) => !str_ends_with($f, '-sm.webp'));
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return array_map(fn($f) => 'library/' . basename($f), $files);
}

function is_library_image(string $file): bool
{
    return (bool) preg_match('#^library/[0-9]{8}-[a-f0-9]{10}\.webp$#', $file) && is_file(UPLOAD_DIR . '/' . $file);
}

/** Wo wird ein Bild verwendet? Liste lesbarer Stellen */
function image_usage(string $file): array
{
    $uses = [];
    foreach (settings()['images'] as $slot => $f) {
        if ($f === $file) $uses[] = 'Website-Bild „' . (IMAGE_SLOTS[$slot]['label'] ?? $slot) . '“';
    }
    foreach (menu_data()['categories'] as $c) {
        foreach ($c['items'] ?? [] as $it) {
            if (($it['image'] ?? '') === $file) $uses[] = 'Gericht „' . ($it['name']['de'] ?? '') . '“';
        }
    }
    return $uses;
}

function delete_library_image(string $file): void
{
    if (!is_library_image($file)) return;
    @unlink(UPLOAD_DIR . '/' . $file);
    @unlink(UPLOAD_DIR . '/' . preg_replace('/\.webp$/', '-sm.webp', $file));
}

/** Bild per URL laden (für den einmaligen Import von der alten Website) */
function fetch_remote_image(string $url): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'k53');
    $ok = false;
    if (function_exists('curl_init')) {
        $fh = fopen($tmp, 'wb');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fh,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'Konvergenz53-Import/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        ]);
        $ok = curl_exec($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200;
        curl_close($ch);
        fclose($fh);
    } elseif (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 25, 'user_agent' => 'Konvergenz53-Import/1.0']]);
        $data = @file_get_contents($url, false, $ctx);
        $ok = $data !== false && file_put_contents($tmp, $data) !== false;
    }
    if (!$ok) {
        @unlink($tmp);
        throw new RuntimeException('Download fehlgeschlagen: ' . $url);
    }
    try {
        return store_image($tmp);
    } finally {
        @unlink($tmp);
    }
}
