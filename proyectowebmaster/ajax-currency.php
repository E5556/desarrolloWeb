<?php
session_start();
$allowed = ['COP','USD','EUR','BRL'];
$cur = $_POST['currency'] ?? $_GET['currency'] ?? 'COP';
if (in_array($cur, $allowed)) $_SESSION['currency'] = $cur;
header('Content-Type: application/json');
echo json_encode(['ok'=>true,'currency'=>$_SESSION['currency']]);
