<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$type = $_GET['type'] ?? '';

function csv_output(string $filename, array $headers, $result): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF"; // BOM UTF-8 para Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
    exit();
}

switch ($type) {

    case 'orders':
        $r = mysqli_query($con,
            "SELECT o.id, u.name, u.email, p.productName, o.quantity,
                    p.productPrice, (o.quantity*p.productPrice) total,
                    o.paymentMethod, o.orderStatus, o.orderDate
             FROM orders o
             JOIN users u ON o.userId=u.id
             JOIN products p ON o.productId=p.id
             ORDER BY o.orderDate DESC");
        csv_output('pedidos_' . date('Ymd') . '.csv',
            ['ID','Cliente','Email','Producto','Cantidad','Precio','Total','Método pago','Estado','Fecha'], $r);
        break;

    case 'users':
        $r = mysqli_query($con,
            "SELECT id, name, email, contactno, billingCity, billingState, regDate
             FROM users ORDER BY regDate DESC");
        csv_output('clientes_' . date('Ymd') . '.csv',
            ['ID','Nombre','Email','Teléfono','Ciudad','Departamento','Registro'], $r);
        break;

    case 'products':
        $r = mysqli_query($con,
            "SELECT p.id, p.productName, p.productCompany,
                    c.catName, p.productPrice, p.productPriceBeforeDiscount,
                    p.productAvailability, p.stock_qty, p.shippingCharge
             FROM products p LEFT JOIN category c ON p.catId=c.id
             ORDER BY p.productName");
        csv_output('productos_' . date('Ymd') . '.csv',
            ['ID','Nombre','Marca','Categoría','Precio','Precio antes dto','Disponibilidad','Stock','Envío'], $r);
        break;

    case 'reviews':
        $r = mysqli_query($con,
            "SELECT r.id, p.productName, u.name, u.email, r.rating, r.review, r.status, r.created_at
             FROM productreviews r
             JOIN products p ON r.productId=p.id
             JOIN users u ON r.userId=u.id
             ORDER BY r.created_at DESC");
        csv_output('resenas_' . date('Ymd') . '.csv',
            ['ID','Producto','Usuario','Email','Rating','Reseña','Estado','Fecha'], $r);
        break;

    case 'subscribers':
        $r = mysqli_query($con,
            "SELECT id, email, is_active, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
        csv_output('suscriptores_' . date('Ymd') . '.csv',
            ['ID','Email','Activo','Fecha suscripción'], $r);
        break;

    default:
        http_response_code(400);
        echo 'Tipo de exportación inválido.';
}
