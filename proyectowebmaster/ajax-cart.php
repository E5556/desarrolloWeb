<?php
session_start();
error_reporting(0);
require_once 'includes/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Helper: parsear clave de carrito → [product_id, variant_id]
function cart_key_parse($key) {
    if (strpos($key, '_') !== false) {
        $parts = explode('_', $key, 2);
        return [intval($parts[0]), intval($parts[1])];
    }
    return [intval($key), 0];
}
// Helper: construir clave de carrito
function cart_key($pid, $vid) {
    return $vid > 0 ? "{$pid}_{$vid}" : (string)$pid;
}

// ── ADD TO CART ──────────────────────────────────────────────
if ($action === 'add') {
    $id  = intval($_POST['id']  ?? 0);
    $vid = intval($_POST['variant_id'] ?? 0);
    if ($id > 0) {
        $ckey = cart_key($id, $vid);
        if (isset($_SESSION['cart'][$ckey])) {
            $_SESSION['cart'][$ckey]['quantity']++;
        } else {
            $stmt = mysqli_prepare($con, "SELECT * FROM products WHERE id=? AND productAvailability IN ('In Stock','On Order')");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if ($row) {
                $price = floatval($row['productPrice']);
                // Si hay variante, sumar price_extra de esa variante
                $variant_label = '';
                if ($vid > 0) {
                    $vr = mysqli_fetch_assoc(mysqli_query($con,
                        "SELECT pv.price_extra,
                            GROUP_CONCAT(CONCAT(vav.attr_name,': ',vav.attr_value) ORDER BY vav.attr_name SEPARATOR ' / ') as combo
                         FROM product_variants pv
                         LEFT JOIN variant_attribute_values vav ON vav.variant_id=pv.id
                         WHERE pv.id=$vid AND pv.product_id=$id AND pv.is_active=1
                         GROUP BY pv.id"));
                    if ($vr) {
                        $price += floatval($vr['price_extra']);
                        $variant_label = $vr['combo'] ?? '';
                    }
                }
                $_SESSION['cart'][$ckey] = [
                    'quantity'      => 1,
                    'price'         => $price,
                    'variant_id'    => $vid,
                    'variant_label' => $variant_label,
                ];
                // Track cart add event
                $sid = session_id();
                $uid = intval($_SESSION['id'] ?? 0) ?: null;
                $stmt_ce = mysqli_prepare($con, "INSERT INTO cart_events(product_id,session_id,user_id) VALUES(?,?,?)");
                if ($stmt_ce) {
                    mysqli_stmt_bind_param($stmt_ce, 'isi', $id, $sid, $uid);
                    mysqli_stmt_execute($stmt_ce);
                    mysqli_stmt_close($stmt_ce);
                }
            }
        }
    }
}

// ── REMOVE FROM CART ─────────────────────────────────────────
if ($action === 'remove') {
    $id  = intval($_POST['id']  ?? 0);
    $vid = intval($_POST['variant_id'] ?? 0);
    $ckey = cart_key($id, $vid);
    // Intentar con clave compuesta y también clave simple por compatibilidad
    if (isset($_SESSION['cart'][$ckey])) unset($_SESSION['cart'][$ckey]);
    elseif (isset($_SESSION['cart'][$id]))  unset($_SESSION['cart'][$id]);
}

// ── UPDATE QUANTITY ──────────────────────────────────────────
if ($action === 'update') {
    $id  = intval($_POST['id']  ?? 0);
    $vid = intval($_POST['variant_id'] ?? 0);
    $qty = intval($_POST['qty'] ?? 1);
    $ckey = cart_key($id, $vid);
    if (!isset($_SESSION['cart'][$ckey]) && isset($_SESSION['cart'][$id])) $ckey = (string)$id;
    if ($qty <= 0) unset($_SESSION['cart'][$ckey]);
    elseif (isset($_SESSION['cart'][$ckey])) $_SESSION['cart'][$ckey]['quantity'] = $qty;
}

// ── BUILD RESPONSE ───────────────────────────────────────────
$items    = [];
$total    = 0;
$totalQty = 0;

if (!empty($_SESSION['cart'])) {
    // Extraer product_ids únicos
    $pid_map = [];
    foreach ($_SESSION['cart'] as $ckey => $item) {
        [$pid] = cart_key_parse($ckey);
        $pid_map[$pid] = true;
    }
    $pids = array_keys($pid_map);
    if ($pids) {
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $types = str_repeat('i', count($pids));
        $stmt = mysqli_prepare($con, "SELECT id, productName, productPrice, shippingCharge, productImage1 FROM products WHERE id IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $types, ...$pids);
        mysqli_stmt_execute($stmt);
        $prod_rows = [];
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) $prod_rows[$r['id']] = $r;
        mysqli_stmt_close($stmt);

        foreach ($_SESSION['cart'] as $ckey => $item) {
            [$pid, $vid] = cart_key_parse($ckey);
            if (!isset($prod_rows[$pid])) continue;
            $row      = $prod_rows[$pid];
            $qty      = $item['quantity'];
            $price    = $item['price'] ?? floatval($row['productPrice']);
            $subtotal = ($price + $row['shippingCharge']) * $qty;
            $total   += $subtotal;
            $totalQty += $qty;
            $items[] = [
                'id'            => $pid,
                'variant_id'    => $vid,
                'cart_key'      => $ckey,
                'variant_label' => $item['variant_label'] ?? '',
                'name'          => $row['productName'],
                'price'         => $price,
                'shipping'      => $row['shippingCharge'],
                'image'         => 'admin/productimages/' . $pid . '/' . $row['productImage1'],
                'qty'           => $qty,
                'subtotal'      => $subtotal,
            ];
        }
    }
}

$_SESSION['tp']   = $total . '.00';
$_SESSION['qnty'] = $totalQty;

echo json_encode([
    'success' => true,
    'count'   => $totalQty,
    'total'   => number_format($total, 0, ',', '.'),
    'items'   => $items,
]);
?>
