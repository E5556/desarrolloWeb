<?php
/**
 * flash_sale_price($con, $product_id)
 * Retorna el precio flash activo o null si no hay ninguno vigente.
 * Retorna también el label y la fecha de fin para el countdown.
 */
function flash_sale_get($con, $product_id): ?array {
    $pid = intval($product_id);
    $r = mysqli_query($con,
        "SELECT sale_price, label, ends_at FROM flash_sales
         WHERE product_id=$pid AND active=1
           AND starts_at <= NOW() AND ends_at >= NOW()
         ORDER BY sale_price ASC LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        return $row;
    }
    return null;
}

/**
 * flash_sales_active()
 * Retorna todas las flash sales vigentes con datos de producto.
 */
function flash_sales_active($con): array {
    $r = mysqli_query($con,
        "SELECT fs.*, p.productName, p.productPrice, p.productImage1, p.id as pid
         FROM flash_sales fs JOIN products p ON fs.product_id=p.id
         WHERE fs.active=1 AND fs.starts_at<=NOW() AND fs.ends_at>=NOW()
         ORDER BY fs.ends_at ASC LIMIT 12");
    $rows = [];
    if ($r) while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}
