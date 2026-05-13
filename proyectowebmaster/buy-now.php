<?php
/* Compra exprés (O2): agrega el producto al carrito y redirige al checkout rápido */
session_start();
include('includes/config.php');
if (empty($_SESSION['login'])) {
    header('location:login.php?redirect=' . urlencode('buy-now.php?' . $_SERVER['QUERY_STRING']));
    exit();
}
$pid = intval($_GET['id'] ?? 0);
$qty = max(1, intval($_GET['qty'] ?? 1));

if ($pid > 0) {
    $pr = mysqli_query($con, "SELECT id, productAvailability FROM products WHERE id=$pid LIMIT 1");
    if ($pr && ($p = mysqli_fetch_assoc($pr)) && $p['productAvailability'] !== 'Out of Stock') {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$pid] = ['quantity' => $qty];
        }
    }
}
header('location:checkout-onepage.php');
exit();
