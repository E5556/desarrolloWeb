<?php
/* Sitemap XML dinámico (P1)
   URL: /proyectowebmaster/sitemap.php
   Indexa: páginas estáticas, categorías, subcategorías, productos
*/
include('includes/config.php');
header('Content-Type: application/xml; charset=utf-8');

$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/';

$today = date('Y-m-d');

$urls = [];

// Páginas estáticas
$static = ['index2.php','category.php','contact.php','compare.php','order-history.php'];
foreach ($static as $pg) {
    $urls[] = ['loc'=>$base.$pg,'lastmod'=>$today,'changefreq'=>'weekly','priority'=>'0.8'];
}

// Categorías
$cats = mysqli_query($con, "SELECT id, categoryName FROM category");
while ($cats && $c = mysqli_fetch_assoc($cats)) {
    $urls[] = ['loc'=>$base.'category.php?cat='.urlencode($c['id']),'lastmod'=>$today,'changefreq'=>'daily','priority'=>'0.7'];
}

// Subcategorías
$subs = mysqli_query($con, "SELECT id FROM subcategory");
while ($subs && $s = mysqli_fetch_assoc($subs)) {
    $urls[] = ['loc'=>$base.'sub-category.php?sub='.urlencode($s['id']),'lastmod'=>$today,'changefreq'=>'daily','priority'=>'0.6'];
}

// Productos
$prods = mysqli_query($con, "SELECT id, updated_at FROM products WHERE productAvailability != 'Out of Stock' ORDER BY id DESC LIMIT 5000");
while ($prods && $p = mysqli_fetch_assoc($prods)) {
    $lastmod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
    $urls[] = ['loc'=>$base.'product-details.php?product='.urlencode($p['id']),'lastmod'=>$lastmod,'changefreq'=>'weekly','priority'=>'0.9'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    echo "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    echo "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
