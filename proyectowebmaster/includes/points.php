<?php
/* Sistema de puntos — Personal Shopper
   Ratio: 1 punto por cada $1.000 gastado
   Tabla: user_points (userId, points, updated_at)
   Tabla: user_points_log (id, userId, delta, reason, created_at)
*/

function ps_points_init($con) {
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS user_points (
        userId INT PRIMARY KEY,
        points INT DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS user_points_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        delta INT NOT NULL,
        reason VARCHAR(120) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (userId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ps_points_get($con, $uid) {
    $uid = intval($uid);
    ps_points_init($con);
    $r = mysqli_query($con, "SELECT points FROM user_points WHERE userId=$uid");
    if ($r && ($row = mysqli_fetch_assoc($r))) return (int)$row['points'];
    return 0;
}

function ps_points_add($con, $uid, $delta, $reason) {
    $uid   = intval($uid);
    $delta = intval($delta);
    ps_points_init($con);
    mysqli_query($con, "INSERT INTO user_points (userId, points) VALUES ($uid, $delta)
        ON DUPLICATE KEY UPDATE points = points + $delta, updated_at=NOW()");
    $reason_esc = mysqli_real_escape_string($con, $reason);
    mysqli_query($con, "INSERT INTO user_points_log (userId, delta, reason) VALUES ($uid, $delta, '$reason_esc')");
}

function ps_points_log($con, $uid, $limit = 10) {
    $uid = intval($uid);
    ps_points_init($con);
    $res = mysqli_query($con, "SELECT delta, reason, created_at FROM user_points_log WHERE userId=$uid ORDER BY id DESC LIMIT $limit");
    $log = [];
    while ($res && $r = mysqli_fetch_assoc($res)) $log[] = $r;
    return $log;
}

// N1 — Niveles de cliente
// Bronce: 0-999 pts | Plata: 1000-4999 | Oro: 5000+ | Diamante: 15000+
function ps_level_info(int $points): array {
    if ($points >= 15000) return ['name'=>'Diamante','icon'=>'💎','color'=>'#00bcd4','discount'=>10,'next'=>null,'next_pts'=>0];
    if ($points >= 5000)  return ['name'=>'Oro',     'icon'=>'🥇','color'=>'#f39c12','discount'=>7,'next'=>'Diamante','next_pts'=>15000];
    if ($points >= 1000)  return ['name'=>'Plata',   'icon'=>'🥈','color'=>'#95a5a6','discount'=>4,'next'=>'Oro',     'next_pts'=>5000];
    return                       ['name'=>'Bronce',  'icon'=>'🥉','color'=>'#cd7f32','discount'=>0,'next'=>'Plata',   'next_pts'=>1000];
}

function ps_level_discount($con, $uid): float {
    $pts = ps_points_get($con, intval($uid));
    return (float) ps_level_info($pts)['discount'];
}
