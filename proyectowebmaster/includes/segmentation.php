<?php
/* S4 — Segmentación automática de clientes
   Etiquetas: VIP | Frecuente | Nuevo | Inactivo | En riesgo | Fiel
   Se recalculan al llamar ps_segment_user() o vía admin/segmentation.php
*/

function ps_segment_init($con): void {
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS user_segments (
        user_id INT PRIMARY KEY,
        segments VARCHAR(255) DEFAULT '',
        total_orders INT DEFAULT 0,
        total_spent DECIMAL(14,2) DEFAULT 0,
        last_order_date DATE DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ps_segment_user($con, int $uid): string {
    ps_segment_init($con);

    // Estadísticas del usuario
    $q = mysqli_query($con, "SELECT COUNT(*) as orders,
            COALESCE(SUM(p.productPrice * o.quantity), 0) as spent,
            MAX(DATE(o.orderDate)) as last_date
        FROM orders o JOIN products p ON p.id=o.productId
        WHERE o.userId=$uid AND o.paymentMethod IS NOT NULL");
    $s = $q ? mysqli_fetch_assoc($q) : null;
    if (!$s) return '';

    $orders    = intval($s['orders']);
    $spent     = floatval($s['spent']);
    $last_date = $s['last_date'];
    $days_since = $last_date ? (int)((time() - strtotime($last_date)) / 86400) : 9999;

    $tags = [];

    // VIP: gasto > 500.000 o más de 10 pedidos
    if ($spent > 500000 || $orders > 10) $tags[] = 'VIP';
    // Frecuente: 4+ pedidos
    elseif ($orders >= 4) $tags[] = 'Frecuente';
    // Nuevo: primer pedido en los últimos 30 días
    elseif ($orders <= 1 && $days_since <= 30) $tags[] = 'Nuevo';

    // Inactivo: más de 90 días sin comprar y al menos 1 pedido
    if ($orders > 0 && $days_since > 90) $tags[] = 'Inactivo';
    // En riesgo: entre 30 y 90 días sin comprar, pero era Frecuente/VIP
    elseif ($orders >= 4 && $days_since > 30 && $days_since <= 90) $tags[] = 'En riesgo';
    // Fiel: más de 6 pedidos y activo en los últimos 60 días
    if ($orders >= 6 && $days_since <= 60) $tags[] = 'Fiel';

    if (empty($tags)) $tags[] = 'Regular';

    $seg_str = implode(',', array_unique($tags));

    // Guardar / actualizar
    $safe_seg = mysqli_real_escape_string($con, $seg_str);
    $safe_date = $last_date ? "'$last_date'" : 'NULL';
    mysqli_query($con, "INSERT INTO user_segments (user_id, segments, total_orders, total_spent, last_order_date)
        VALUES ($uid, '$safe_seg', $orders, $spent, $safe_date)
        ON DUPLICATE KEY UPDATE segments='$safe_seg', total_orders=$orders, total_spent=$spent, last_order_date=$safe_date");

    return $seg_str;
}

function ps_segment_all($con): int {
    ps_segment_init($con);
    $users = mysqli_query($con, "SELECT DISTINCT userId FROM orders WHERE paymentMethod IS NOT NULL");
    $count = 0;
    while ($users && $u = mysqli_fetch_assoc($users)) {
        ps_segment_user($con, intval($u['userId']));
        $count++;
    }
    return $count;
}

function ps_segment_badge(string $seg): string {
    $colors = [
        'VIP'       => '#f39c12',
        'Frecuente' => '#9b59b6',
        'Fiel'      => '#27ae60',
        'Nuevo'     => '#3498db',
        'Inactivo'  => '#95a5a6',
        'En riesgo' => '#e8233a',
        'Regular'   => '#bdc3c7',
    ];
    $c = $colors[$seg] ?? '#bdc3c7';
    return "<span style='background:{$c};color:#fff;border-radius:8px;padding:1px 7px;font-size:11px;margin:1px;display:inline-block'>{$seg}</span>";
}
