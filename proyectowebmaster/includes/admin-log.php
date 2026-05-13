<?php
/**
 * admin_log($con, $action, $details)
 * Registra una acción en el log de actividad admin.
 * Solo actúa si hay sesión de admin activa.
 */
function admin_log($con, string $action, string $details = ''): void {
    if (empty($_SESSION['alogin'])) return;
    $admin = mysqli_real_escape_string($con, $_SESSION['alogin']);
    $action  = mysqli_real_escape_string($con, mb_substr($action, 0, 100));
    $details = mysqli_real_escape_string($con, mb_substr($details, 0, 500));
    $ip      = mysqli_real_escape_string($con, $_SERVER['REMOTE_ADDR'] ?? '');

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS admin_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_user VARCHAR(100) NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT DEFAULT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(created_at)
    )");

    mysqli_query($con, "INSERT INTO admin_log (admin_user, action, details, ip)
        VALUES ('$admin','$action','$details','$ip')");
}
