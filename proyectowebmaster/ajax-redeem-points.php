<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/points.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) { echo json_encode(['ok'=>false,'msg'=>'No has iniciado sesión.']); exit; }

$action = $_POST['action'] ?? 'redeem';
$uid = intval($_SESSION['id']);

if ($action === 'remove') {
    unset($_SESSION['points_redeemed'], $_SESSION['points_discount']);
    echo json_encode(['ok'=>true]);
    exit;
}

$pts_to_use = intval($_POST['points'] ?? 0);
$available  = ps_points_get($con, $uid);

if ($pts_to_use < 1) { echo json_encode(['ok'=>false,'msg'=>'Ingresa al menos 1 punto.']); exit; }
if ($pts_to_use > $available) { echo json_encode(['ok'=>false,'msg'=>'No tienes suficientes puntos.']); exit; }

$discount = $pts_to_use * 10; // 1 pto = $10
$_SESSION['points_redeemed'] = $pts_to_use;
$_SESSION['points_discount'] = $discount;

echo json_encode(['ok'=>true,'pts'=>$pts_to_use,'discount'=>$discount,'available'=>$available]);
