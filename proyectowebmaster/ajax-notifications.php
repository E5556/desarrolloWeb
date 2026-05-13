<?php
session_start();
error_reporting(0);
include('includes/config.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) { echo json_encode(['ok'=>false,'items'=>[]]); exit; }

$uid = intval($_SESSION['id']);

// Auto-create table
mysqli_query($con, "CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT '',
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (userId, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'mark_read') {
    $nid = intval($_POST['id'] ?? 0);
    if ($nid) mysqli_query($con, "UPDATE user_notifications SET is_read=1 WHERE id=$nid AND userId=$uid");
    if (!$nid) mysqli_query($con, "UPDATE user_notifications SET is_read=1 WHERE userId=$uid");
    echo json_encode(['ok'=>true]);
    exit;
}

// Generate system notifications (only once per order/product — uses UNIQUE key trick via message+userId)
mysqli_query($con, "ALTER TABLE user_notifications ADD COLUMN IF NOT EXISTS ref_key VARCHAR(100) DEFAULT NULL");
mysqli_query($con, "ALTER TABLE user_notifications ADD UNIQUE IF NOT EXISTS uq_uid_ref (userId, ref_key)");

// New orders
$orders_q = mysqli_query($con, "SELECT id FROM orders WHERE userId=$uid ORDER BY id DESC LIMIT 5");
while ($orders_q && $o = mysqli_fetch_assoc($orders_q)) {
    $ref = 'order_'.$o['id'];
    $ref_e = mysqli_real_escape_string($con, $ref);
    $msg_e = 'Orden #'.$o['id'].' recibida correctamente';
    $link_e = 'order-details.php?orderid='.$o['id'];
    mysqli_query($con, "INSERT IGNORE INTO user_notifications (userId, message, link, ref_key) VALUES ($uid, '$msg_e', '$link_e', '$ref_e')");
}

// Wishlist items available
$wl_q = mysqli_query($con, "SELECT p.id, p.productName FROM wishlist w JOIN products p ON p.id=w.productId WHERE w.userId=$uid AND p.productAvailability='In Stock' LIMIT 3");
while ($wl_q && $wl = mysqli_fetch_assoc($wl_q)) {
    $ref = 'wl_avail_'.$wl['id'];
    $ref_e = mysqli_real_escape_string($con, $ref);
    $name_e = mysqli_real_escape_string($con, $wl['productName']);
    mysqli_query($con, "INSERT IGNORE INTO user_notifications (userId, message, link, ref_key) VALUES ($uid, '$name_e de tu wishlist está disponible', 'product-details.php?pid=".$wl['id']."', '$ref_e')");
}

$res = mysqli_query($con, "SELECT * FROM user_notifications WHERE userId=$uid ORDER BY created_at DESC LIMIT 15");
$items = [];
$unread = 0;
while ($res && $r = mysqli_fetch_assoc($res)) {
    $items[] = ['id'=>(int)$r['id'],'msg'=>$r['message'],'link'=>$r['link'],'read'=>(bool)$r['is_read'],'time'=>$r['created_at']];
    if (!$r['is_read']) $unread++;
}

echo json_encode(['ok'=>true,'items'=>$items,'unread'=>$unread]);
