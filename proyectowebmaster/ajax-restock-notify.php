<?php
session_start();
error_reporting(0);
include('includes/config.php');
header('Content-Type: application/json');

// BB2: Subscribe / unsubscribe to back-in-stock notifications
mysqli_query($con, "CREATE TABLE IF NOT EXISTS restock_notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id    INT DEFAULT NULL,
    email      VARCHAR(180) NOT NULL,
    notified   TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prod_email (product_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? 'subscribe';
$pid    = intval($_POST['pid'] ?? 0);
$email  = trim($_POST['email'] ?? '');

if ($pid <= 0) { echo json_encode(['ok'=>false,'msg'=>'Producto inválido']); exit(); }

if ($action === 'subscribe') {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok'=>false,'msg'=>'Email inválido']);
        exit();
    }
    $uid  = !empty($_SESSION['id']) ? intval($_SESSION['id']) : null;
    $esc  = mysqli_real_escape_string($con, $email);
    $stmt = mysqli_prepare($con, "INSERT IGNORE INTO restock_notifications (product_id,user_id,email) VALUES (?,?,?)");
    mysqli_stmt_bind_param($stmt, 'iis', $pid, $uid, $email);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if ($affected > 0) {
        echo json_encode(['ok'=>true,'msg'=>'¡Te avisaremos cuando vuelva a estar disponible!']);
    } else {
        echo json_encode(['ok'=>true,'msg'=>'Ya estás suscrito a las notificaciones de este producto.']);
    }
} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción desconocida']);
}
