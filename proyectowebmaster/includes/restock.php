<?php
// BB2: Send restock notifications when a product comes back in stock
include_once(__DIR__ . '/mailer.php');

function ps_restock_notify(mysqli $con, int $product_id, string $product_name): void {
    $r = mysqli_query($con, "SELECT id, email FROM restock_notifications WHERE product_id=$product_id AND notified=0");
    if (!$r || mysqli_num_rows($r) === 0) return;

    $site_q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='site_name' LIMIT 1");
    $site   = $site_q ? (mysqli_fetch_assoc($site_q)['setting_value'] ?? 'Mi Tienda') : 'Mi Tienda';
    $host   = 'http://'.$_SERVER['HTTP_HOST'];
    $url    = $host.'/proyectowebmaster/product-details.php?id='.$product_id;

    $ids = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $subject = "¡$product_name volvió a estar disponible!";
        $body = "
        <div style='font-family:sans-serif;max-width:520px;margin:auto'>
          <h2 style='color:#e8233a'>¡Buenas noticias!</h2>
          <p>El producto que marcaste como favorito ya está disponible:</p>
          <h3 style='color:#333'>$product_name</h3>
          <a href='$url' style='background:#e8233a;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;display:inline-block;margin:16px 0'>Ver producto</a>
          <p style='color:#999;font-size:12px'>Recibiste este correo porque solicitaste notificación de disponibilidad en $site.</p>
        </div>";
        send_email_raw($row['email'], $subject, $body);
        $ids[] = intval($row['id']);
    }

    if (!empty($ids)) {
        $in = implode(',', $ids);
        mysqli_query($con, "UPDATE restock_notifications SET notified=1 WHERE id IN ($in)");
    }
}
