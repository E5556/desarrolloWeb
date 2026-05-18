<?php
/* Q1 — Recomendaciones personalizadas
   Devuelve hasta $limit productos que el usuario NO ha comprado,
   priorizando las categorías que sí compró.
*/
function ps_recommendations($con, int $uid, int $limit = 6): array {
    if ($uid <= 0) return [];

    // Categorías más compradas por el usuario
    $cat_q = @mysqli_query($con, "SELECT p.category as catId, COUNT(*) n
        FROM orders o JOIN products p ON p.id=o.productId
        WHERE o.userId=$uid AND o.paymentMethod IS NOT NULL
        GROUP BY p.category ORDER BY n DESC LIMIT 3");

    $cat_ids = [];
    while ($cat_q && $r = mysqli_fetch_assoc($cat_q)) $cat_ids[] = intval($r['catId']);

    // Productos ya comprados
    $bought_q = @mysqli_query($con, "SELECT DISTINCT productId FROM orders WHERE userId=$uid");
    $bought = [];
    while ($bought_q && $r = mysqli_fetch_assoc($bought_q)) $bought[] = intval($r['productId']);

    $not_in = count($bought) > 0 ? 'AND p.id NOT IN (' . implode(',', $bought) . ')' : '';
    $cat_in = count($cat_ids) > 0 ? implode(',', $cat_ids) : '0';

    // Primero productos de categorías favoritas, luego otros
    $sql = "SELECT p.id, p.productName, p.productPrice, p.productImage1 as productImage, p.category as catId,
                   (CASE WHEN p.category IN ($cat_in) THEN 1 ELSE 0 END) as prio
            FROM products p
            WHERE p.productAvailability='In Stock' $not_in
            ORDER BY prio DESC, p.id DESC
            LIMIT $limit";
    $res = mysqli_query($con, $sql);
    $rows = [];
    while ($res && $r = mysqli_fetch_assoc($res)) $rows[] = $r;
    return $rows;
}
