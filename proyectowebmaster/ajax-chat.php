<?php
session_start();
error_reporting(0);
include('includes/config.php');
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

mysqli_query($con, "CREATE TABLE IF NOT EXISTS live_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(80) NOT NULL,
    user_id INT DEFAULT NULL,
    sender ENUM('user','admin') NOT NULL DEFAULT 'user',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(session_id),
    INDEX(user_id),
    INDEX(created_at)
)");

// ── Verificar admin via token HMAC ──
$_psid = $_POST['_sid'] ?? '';
$_ptok = $_POST['_tok'] ?? '';
$is_admin = ($_psid !== '' && $_ptok !== '' &&
    hash_equals(hash_hmac('sha256', $_psid, 'ps_chat_secret_2024'), $_ptok));

if ($is_admin) {
    $sid = $_psid;
    $uid = null;
    $sid_e = mysqli_real_escape_string($con, $sid);
    session_write_close();
} else {
    // ── SOLO usuarios logueados en el frontend pueden usar el chat ──
    // $_SESSION['alogin'] es exclusivo del admin → si existe, no es un usuario frontend
    if (!isset($_SESSION['id']) || isset($_SESSION['alogin'])) {
        session_write_close();
        echo json_encode(['error' => 'auth_required']); exit();
    }
    $uid = intval($_SESSION['id']);

    // chat_sid: identifica la conversación del usuario, separado de PHPSESSID
    $raw_sid = $_POST['chat_sid'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9_-]{20,80}$/', $raw_sid)) {
        session_write_close();
        echo json_encode(['error' => 'invalid_sid']); exit();
    }

    // Verificar que este chat_sid pertenece al usuario logueado o no tiene dueño aún
    $raw_sid_e = mysqli_real_escape_string($con, $raw_sid);
    $own_q = mysqli_query($con,
        "SELECT DISTINCT user_id FROM live_chat_messages
         WHERE session_id='$raw_sid_e' AND user_id IS NOT NULL LIMIT 1");
    if ($own_q && $own_r = mysqli_fetch_assoc($own_q)) {
        // El chat_sid ya tiene un dueño — verificar que sea este usuario
        if (intval($own_r['user_id']) !== $uid) {
            session_write_close();
            echo json_encode(['error' => 'forbidden']); exit();
        }
    }

    $sid = $raw_sid;
    $sid_e = $raw_sid_e;
    session_write_close();
}

/* ── SEND ── */
if ($action === 'send') {
    $msg = trim($_POST['msg'] ?? '');
    if ($msg === '' || strlen($msg) > 1000) { echo json_encode(['ok'=>false]); exit(); }
    $msg_e = mysqli_real_escape_string($con, $msg);
    $sender = $is_admin ? 'admin' : 'user';

    if ($sender === 'admin') {
        $target = mysqli_real_escape_string($con, $_POST['target_sid'] ?? '');
        if ($target === '') { echo json_encode(['ok'=>false]); exit(); }
        mysqli_query($con, "INSERT INTO live_chat_messages (session_id, sender, message) VALUES ('$target','admin','$msg_e')");
        mysqli_query($con, "UPDATE live_chat_messages SET is_read=1 WHERE session_id='$target' AND sender='user'");
    } else {
        mysqli_query($con, "INSERT INTO live_chat_messages (session_id, user_id, sender, message) VALUES ('$sid_e',$uid,'user','$msg_e')");
    }
    echo json_encode(['ok'=>true, 'id'=>mysqli_insert_id($con)]);
    exit();
}

/* ── POLL ── */
if ($action === 'poll') {
    $last_id = intval($_POST['last_id'] ?? $_GET['last_id'] ?? 0);

    if ($is_admin) {
        $target = mysqli_real_escape_string($con, $_POST['target_sid'] ?? '');
        if ($target === '') { echo json_encode(['msgs'=>[]]); exit(); }
        $q = mysqli_query($con,
            "SELECT id, sender, message, created_at
             FROM live_chat_messages
             WHERE session_id='$target' AND id>$last_id
             ORDER BY id ASC LIMIT 50");
    } else {
        // Usuario solo puede ver mensajes de su propio chat_sid y su user_id
        $q = mysqli_query($con,
            "SELECT id, sender, message, created_at
             FROM live_chat_messages
             WHERE session_id='$sid_e' AND id>$last_id
             ORDER BY id ASC LIMIT 50");
    }

    $msgs = [];
    if ($q) while ($r = mysqli_fetch_assoc($q)) $msgs[] = $r;
    echo json_encode(['msgs' => $msgs]);
    exit();
}

/* ── SESSIONS (admin) ── */
if ($action === 'sessions') {
    if (!$is_admin) { echo json_encode(['sessions'=>[]]); exit(); }
    $q = mysqli_query($con,
        "SELECT session_id,
                MAX(id) AS last_id,
                MAX(created_at) AS last_msg,
                MAX(user_id) AS user_id,
                SUM(CASE WHEN sender='user' AND is_read=0 THEN 1 ELSE 0 END) AS unread
         FROM live_chat_messages
         WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY session_id
         ORDER BY last_msg DESC
         LIMIT 50");
    $sessions = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $sid_tmp = mysqli_real_escape_string($con, $r['session_id']);
            $pq = mysqli_query($con, "SELECT message FROM live_chat_messages WHERE session_id='$sid_tmp' AND sender='user' ORDER BY id DESC LIMIT 1");
            $r['preview'] = ($pq && $pr = mysqli_fetch_assoc($pq)) ? $pr['message'] : null;
            if ($r['user_id']) {
                $uid_tmp = intval($r['user_id']);
                $uq = mysqli_query($con, "SELECT name, email FROM users WHERE id=$uid_tmp LIMIT 1");
                if ($uq && $ur = mysqli_fetch_assoc($uq)) {
                    $r['user_name']  = $ur['name'];
                    $r['user_email'] = $ur['email'];
                }
            }
            $sessions[] = $r;
        }
    }
    echo json_encode(['sessions' => $sessions]);
    exit();
}

echo json_encode(['error' => 'unknown action']);
