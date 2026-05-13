<?php
session_start();
include('includes/config.php');
header('Content-Type: application/json');

if (empty($_SESSION['login'])) {
    echo json_encode(['ok' => false, 'msg' => 'login']);
    exit();
}

$pid = intval($_POST['pid'] ?? 0);
if ($pid <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'invalid']);
    exit();
}

$uid = intval($_SESSION['id']);

// Evitar duplicados
$chk = mysqli_prepare($con, "SELECT id FROM wishlist WHERE userId=? AND productId=?");
mysqli_stmt_bind_param($chk, 'ii', $uid, $pid);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
$exists = mysqli_stmt_num_rows($chk) > 0;
mysqli_stmt_close($chk);

if ($exists) {
    echo json_encode(['ok' => true, 'already' => true, 'msg' => 'Ya está en tu lista de deseos']);
    exit();
}

$st = mysqli_prepare($con, "INSERT INTO wishlist(userId, productId) VALUES(?, ?)");
mysqli_stmt_bind_param($st, 'ii', $uid, $pid);
$ok = mysqli_stmt_execute($st);
mysqli_stmt_close($st);

echo json_encode([
    'ok'  => $ok,
    'msg' => $ok ? 'Añadido a tu lista de deseos' : 'Error al guardar'
]);
