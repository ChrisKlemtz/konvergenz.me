<?php
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach (['home', 'menu', 'contact', 'imprint', 'privacy'] as $p) {
    foreach (LANGS as $l) {
        echo "  <url>\n    <loc>" . h(absolute_url(page_url($p, $l))) . "</loc>\n";
        foreach (LANGS as $alt) {
            echo '    <xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . h(absolute_url(page_url($p, $alt))) . '"/>' . "\n";
        }
        echo "  </url>\n";
    }
}
echo "</urlset>\n";
