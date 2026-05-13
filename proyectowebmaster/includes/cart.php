<?php
// BB1: Persistent cart — sync between $_SESSION['cart'] and DB table `persistent_cart`

function ps_cart_init(mysqli $con): void {
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS persistent_cart (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        product_id INT NOT NULL,
        quantity   INT NOT NULL DEFAULT 1,
        price      DECIMAL(10,2) DEFAULT NULL,
        customization TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_prod (user_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Save current session cart to DB (called on login and on cart changes)
function ps_cart_save(mysqli $con, int $uid): void {
    ps_cart_init($con);
    if (empty($_SESSION['cart'])) {
        mysqli_query($con, "DELETE FROM persistent_cart WHERE user_id=$uid");
        return;
    }
    // Delete items not in session cart anymore
    $pids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    mysqli_query($con, "DELETE FROM persistent_cart WHERE user_id=$uid AND product_id NOT IN ($pids)");

    $stmt = mysqli_prepare($con, "INSERT INTO persistent_cart (user_id,product_id,quantity,price,customization)
        VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), price=VALUES(price), customization=VALUES(customization)");
    foreach ($_SESSION['cart'] as $pid => $item) {
        $pid   = intval($pid);
        $qty   = intval($item['quantity'] ?? 1);
        $price = floatval($item['price'] ?? 0);
        $cust  = !empty($item['customization']) ? json_encode($item['customization']) : null;
        mysqli_stmt_bind_param($stmt, 'iiids', $uid, $pid, $qty, $price, $cust);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Load DB cart into session (called on login — merges with any existing session cart)
function ps_cart_load(mysqli $con, int $uid): void {
    ps_cart_init($con);
    $r = mysqli_query($con, "SELECT product_id, quantity, price, customization FROM persistent_cart WHERE user_id=$uid");
    if (!$r) return;
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $pid = intval($row['product_id']);
        // Session cart takes priority if item already exists
        if (!isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] = [
                'quantity'      => intval($row['quantity']),
                'price'         => floatval($row['price']),
                'customization' => $row['customization'] ? json_decode($row['customization'], true) : [],
            ];
        }
    }
    // After merge, persist the merged result back
    ps_cart_save($con, $uid);
}

// Clear DB cart (called after order placed)
function ps_cart_clear(mysqli $con, int $uid): void {
    mysqli_query($con, "DELETE FROM persistent_cart WHERE user_id=$uid");
}
