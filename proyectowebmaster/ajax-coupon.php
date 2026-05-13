<?php
session_start();
require_once 'includes/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ── APLICAR CUPÓN ─────────────────────────────────────────────────────────
if ($action === 'apply') {
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if (empty($code)) {
        echo json_encode(['ok'=>false,'msg'=>'Ingresa un código de cupón.']);
        exit();
    }

    // Calcular total actual del carrito
    $cart_total = 0;
    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $st  = mysqli_prepare($con, "SELECT id, productPrice, shippingCharge FROM products WHERE id IN ($ph)");
        mysqli_stmt_bind_param($st, str_repeat('i', count($ids)), ...$ids);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        while ($r = mysqli_fetch_assoc($res)) {
            $qty = $_SESSION['cart'][$r['id']]['quantity'];
            $cart_total += $qty * $r['productPrice'] + $r['shippingCharge'];
        }
        mysqli_stmt_close($st);
    }

    if ($cart_total <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'El carrito está vacío.']);
        exit();
    }

    // Buscar cupón
    $stmt = mysqli_prepare($con, "SELECT * FROM coupons WHERE code=? AND active=1");
    mysqli_stmt_bind_param($stmt, 's', $code);
    mysqli_stmt_execute($stmt);
    $coupon = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$coupon) {
        echo json_encode(['ok'=>false,'msg'=>'Código de cupón inválido o inactivo.']);
        exit();
    }

    // Expiración
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < strtotime('today')) {
        echo json_encode(['ok'=>false,'msg'=>'Este cupón ha expirado.']);
        exit();
    }

    // Usos máximos
    if ($coupon['max_uses'] > 0 && $coupon['uses_count'] >= $coupon['max_uses']) {
        echo json_encode(['ok'=>false,'msg'=>'Este cupón ha alcanzado su límite de usos.']);
        exit();
    }

    // Compra mínima
    if ($cart_total < $coupon['min_purchase']) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Compra mínima requerida: $'.number_format($coupon['min_purchase'],0,'.',',')
        ]);
        exit();
    }

    // Calcular descuento
    if ($coupon['type'] === 'percent') {
        $discount = round($cart_total * ($coupon['value'] / 100));
    } else {
        $discount = min(floatval($coupon['value']), $cart_total);
    }

    $final = max(0, $cart_total - $discount);

    // Guardar en sesión
    $_SESSION['coupon'] = [
        'id'       => $coupon['id'],
        'code'     => $coupon['code'],
        'type'     => $coupon['type'],
        'value'    => $coupon['value'],
        'discount' => $discount,
        'original' => $cart_total,
        'final'    => $final,
    ];

    echo json_encode([
        'ok'       => true,
        'code'     => $coupon['code'],
        'type'     => $coupon['type'],
        'value'    => floatval($coupon['value']),
        'discount' => $discount,
        'original' => $cart_total,
        'final'    => $final,
        'msg'      => '¡Cupón aplicado! Descuento de $'.number_format($discount,0,'.',','),
    ]);
    exit();
}

// ── QUITAR CUPÓN ──────────────────────────────────────────────────────────
if ($action === 'remove') {
    unset($_SESSION['coupon']);
    echo json_encode(['ok'=>true]);
    exit();
}

echo json_encode(['ok'=>false,'msg'=>'Acción no válida.']);
