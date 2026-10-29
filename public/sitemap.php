<?php
/**
 * Dynamic Sitemap for Clicks Leather
 * Outputs XML sitemap for search engines
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = PUBLIC_URL;
$baseUrl = rtrim($baseUrl, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

function outputUrl($loc, $lastmod = null) {
    $locEscaped = htmlspecialchars($loc, ENT_XML1, 'UTF-8');
    echo "  <url>\n";
    echo "    <loc>$locEscaped</loc>\n";
    if ($lastmod) {
        $lastmodEscaped = htmlspecialchars(date('Y-m-d', strtotime($lastmod)), ENT_XML1, 'UTF-8');
        echo "    <lastmod>$lastmodEscaped</lastmod>\n";
    }
    echo "  </url>\n";
}

outputUrl($baseUrl . '/');

$categories = get_all_categories($pdo);
foreach ($categories as $category) {
    $slug = strtolower(str_replace(' ', '-', $category['naam']));
    outputUrl($baseUrl . '/products.php?category=' . $slug);
}

$stmt = $pdo->query("SELECT id, created_at FROM products ORDER BY id");
while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    outputUrl($baseUrl . '/product-detail.php?id=' . $product['id'], $product['created_at']);
}

$staticPages = ['about.php', 'contact.php', 'faq.php', 'return-policy.php', 'warranty.php', 'shipping-info.php', 'privacy-policy.php', 'terms-and-conditions.php'];
foreach ($staticPages as $page) {
    outputUrl($baseUrl . '/' . $page);
}

echo '</urlset>';
