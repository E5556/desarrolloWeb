<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_settings');

// Solo super-admin puede hacer backup
if (($_SESSION['arole'] ?? '') !== 'super') {
    echo '<script>alert("Solo el Super Admin puede generar backups.");history.back();</script>'; exit();
}

$tables_q = mysqli_query($con, "SHOW TABLES");
$sql_dump  = "-- Backup generado: " . date('Y-m-d H:i:s') . "\n";
$sql_dump .= "-- Base de datos: " . DB_NAME . "\n\n";
$sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

while ($t = mysqli_fetch_row($tables_q)) {
    $table = $t[0];
    // Estructura
    $create = mysqli_fetch_row(mysqli_query($con,"SHOW CREATE TABLE `$table`"));
    $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
    $sql_dump .= $create[1] . ";\n\n";
    // Datos
    $rows_q = mysqli_query($con, "SELECT * FROM `$table`");
    $cols   = mysqli_num_fields($rows_q);
    while ($row = mysqli_fetch_row($rows_q)) {
        $vals = array_map(function($v) use ($con) {
            return is_null($v) ? 'NULL' : "'" . mysqli_real_escape_string($con,$v) . "'";
        }, $row);
        $sql_dump .= "INSERT INTO `$table` VALUES(" . implode(',', $vals) . ");\n";
    }
    $sql_dump .= "\n";
}
$sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

$filename = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($sql_dump));
echo $sql_dump;
exit();
