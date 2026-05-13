<?php
session_start();
require_once 'includes/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ── ADD TO CART ──────────────────────────────────────────────
if ($action === 'add') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $stmt = mysqli_prepare($con, "SELECT * FROM products WHERE id=? AND productAvailability IN ('In Stock','On Order')");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
            if ($row) {
                $_SESSION['cart'][$id] = ['quantity' => 1, 'price' => $row['productPrice']];
                // Track cart add event
                $sid = session_id();
                $uid = intval($_SESSION['id'] ?? 0) ?: null;
                $stmt_ce = mysqli_prepare($con, "INSERT INTO cart_events(product_id,session_id,user_id) VALUES(?,?,?)");
                mysqli_stmt_bind_param($stmt_ce, 'isi', $id, $sid, $uid);
                mysqli_stmt_execute($stmt_ce);
                mysqli_stmt_close($stmt_ce);
            }
        }
    }
}

// ── REMOVE FROM CART ─────────────────────────────────────────
if ($action === 'remove') {
    $id = intval($_POST['id'] ?? 0);
    unset($_SESSION['cart'][$id]);
}

// ── UPDATE QUANTITY ──────────────────────────────────────────
if ($action === 'update') {
    $id  = intval($_POST['id']  ?? 0);
    $qty = intval($_POST['qty'] ?? 1);
    if ($qty <= 0) {
        unset($_SESSION['cart'][$id]);
    } elseif (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] = $qty;
    }
}

// ── BUILD RESPONSE ───────────────────────────────────────────
$items      = [];
$total      = 0;
$totalQty   = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = mysqli_prepare($con, "SELECT id, productName, productPrice, shippingCharge, productImage1 FROM products WHERE id IN ($placeholders)");
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $qty      = $_SESSION['cart'][$row['id']]['quantity'];
        $subtotal = ($row['productPrice'] + $row['shippingCharge']) * $qty;
        $total   += $subtotal;
        $totalQty += $qty;
        $items[]  = [
            'id'       => $row['id'],
            'name'     => $row['productName'],
            'price'    => $row['productPrice'],
            'shipping' => $row['shippingCharge'],
            'image'    => 'admin/productimages/' . $row['id'] . '/' . $row['productImage1'],
            'qty'      => $qty,
            'subtotal' => $subtotal,
        ];
    }
    mysqli_stmt_close($stmt);
}

$_SESSION['tp']   = $total . '.00';
$_SESSION['qnty'] = $totalQty;

echo json_encode([
    'success'  => true,
    'count'    => $totalQty,
    'total'    => number_format($total, 0, ',', '.'),
    'items'    => $items,
]);
?>
