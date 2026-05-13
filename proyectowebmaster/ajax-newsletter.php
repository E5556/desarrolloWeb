<?php
session_start();
error_reporting(0);
include('includes/config.php');
header('Content-Type: application/json');

// Auto-create table if not exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    name VARCHAR(100) DEFAULT '',
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'subscribe') {
    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name']  ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'msg' => 'Correo electronico no valido.']);
        exit;
    }

    $email_esc = mysqli_real_escape_string($con, $email);
    $name_esc  = mysqli_real_escape_string($con, substr($name, 0, 100));

    $chk = mysqli_query($con, "SELECT id, active FROM newsletter WHERE email='$email_esc'");
    if ($chk && $row = mysqli_fetch_assoc($chk)) {
        if ($row['active']) {
            echo json_encode(['ok' => false, 'msg' => 'Este correo ya esta suscrito.']);
        } else {
            mysqli_query($con, "UPDATE newsletter SET active=1, name='$name_esc', subscribed_at=NOW() WHERE email='$email_esc'");
            echo json_encode(['ok' => true, 'msg' => 'Bienvenido de nuevo! Suscripcion reactivada.']);
        }
        exit;
    }

    if (mysqli_query($con, "INSERT INTO newsletter(email,name) VALUES('$email_esc','$name_esc')")) {
        echo json_encode(['ok' => true, 'msg' => 'Gracias por suscribirte!']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Error al suscribirte. Intentalo de nuevo.']);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Accion no reconocida.']);
