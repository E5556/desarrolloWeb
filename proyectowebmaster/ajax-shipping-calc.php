<?php
session_start();
error_reporting(0);
include('includes/config.php');
include_once('includes/shipping.php');

header('Content-Type: application/json');

$city  = trim($_POST['city'] ?? '');
$dept  = trim($_POST['dept'] ?? '');
$total = floatval($_POST['total'] ?? 0);

if (empty($city)) {
    echo json_encode(['ok' => false, 'msg' => 'Ingresa una ciudad.']);
    exit();
}

$info = ps_shipping_cost($con, $city, $total, $dept);

if ($info['zone'] === 'Sin zona') {
    echo json_encode(['ok' => false, 'msg' => 'No encontramos cobertura para esa ciudad. Intenta con el nombre del departamento.']);
    exit();
}

$is_premium = false;
if (!empty($_SESSION['login'])) {
    include_once('includes/membership.php');
    $is_premium = ps_membership_active($con, intval($_SESSION['id']));
}

$cost = $is_premium ? 0 : $info['cost'];
$free = $is_premium || $info['free'];

echo json_encode([
    'ok'       => true,
    'zone'     => $info['zone'],
    'cost'     => $cost,
    'cost_fmt' => $free ? 'Gratis' : '$' . number_format($cost, 0, '.', ','),
    'free'     => $free,
    'days'     => $info['days_min'] . '–' . $info['days_max'] . ' días hábiles',
    'premium'  => $is_premium,
]);
