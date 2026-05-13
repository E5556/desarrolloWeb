<?php
/**
 * Renderiza el bloque de precios de un producto.
 * $p   = fila del producto (array con hasDiscount, productPrice, productPriceBeforeDiscount)
 * $cur = símbolo de moneda (ej: '$')
 */
function render_price(array $p, string $cur): void {
    $sale     = htmlentities($p['productPriceBeforeDiscount'] ?? 0); // precio de venta
    $discount = htmlentities($p['productPrice']               ?? 0); // precio con descuento
    $has      = !empty($p['hasDiscount']);
    $c        = htmlspecialchars($cur);

    if ($has && floatval($p['productPrice']) > 0) {
        // Tiene descuento activo
        echo '<span class="price">' . $c . $discount . '</span> ';
        echo '<span class="price-before-discount">' . $c . $sale . '</span>';
    } else {
        // Sin descuento: solo precio de venta
        echo '<span class="price">' . $c . $sale . '</span>';
    }
}

/**
 * Renderiza el tag <img> de un producto con data-img0..N para el hover gallery.
 * Incluye imágenes de productImage1/2/3 + extras de la tabla product_images.
 * $p = fila del producto, $w/$h = dimensiones
 */
function product_img_tag(array $p, int $w = 200, int $h = 300): void {
    global $con;
    $id   = intval($p['id']);
    $base = "admin/productimages/$id/";

    // Recopilar todas las imágenes
    $imgs = [];
    foreach (['productImage1','productImage2','productImage3'] as $col) {
        if (!empty($p[$col])) $imgs[] = $base . htmlspecialchars($p[$col]);
    }
    // Extras de product_images
    if ($con) {
        $eq = mysqli_query($con, "SELECT imageName FROM product_images WHERE productId='$id' ORDER BY sortOrder");
        while ($eq && $er = mysqli_fetch_assoc($eq)) {
            if (!empty($er['imageName'])) $imgs[] = $base . htmlspecialchars($er['imageName']);
        }
    }

    $src0 = $imgs ? $imgs[0] : 'assets/images/blank.gif';
    echo '<img src="' . $src0 . '"';
    foreach ($imgs as $i => $src) {
        echo ' data-img' . $i . '="' . $src . '"';
    }
    echo ' width="' . $w . '" height="' . $h . '" alt="' . htmlspecialchars($p['productName'] ?? '') . '">';
}
?>
