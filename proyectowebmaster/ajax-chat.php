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

// Verificar admin via token HMAC
$_psid = $_POST['_sid'] ?? '';
$_ptok = $_POST['_tok'] ?? '';
$is_admin = ($_psid !== '' && $_ptok !== '' &&
    hash_equals(hash_hmac('sha256', $_psid, 'ps_chat_secret_2024'), $_ptok));

if ($is_admin) {
    // Admin: usa su propio sid del token HMAC como identificador
    $sid = $_psid;
    $uid = null;
} else {
    // Usuario del frontend: usa chat_sid propio guardado en localStorage,
    // completamente separado de la sesión PHP de autenticación.
    $raw_sid = $_POST['chat_sid'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9_-]{20,80}$/', $raw_sid)) {
        echo json_encode(['error' => 'invalid_sid']); exit();
    }
    $sid = $raw_sid;

    // user_id: solo si la sesión activa es de un usuario frontend (tiene $_SESSION['id']
    // pero NO tiene $_SESSION['alogin'] que es exclusivo del admin)
    $uid = null;
    if (isset($_SESSION['id']) && !isset($_SESSION['alogin'])) {
        $uid = intval($_SESSION['id']);
    }
}
$sid_e = mysqli_real_escape_string($con, $sid);

session_write_close();

/* ── SEND ── */
if ($action === 'send') {
    $msg = trim($_POST['msg'] ?? '');
    if ($msg === '' || strlen($msg) > 1000) { echo json_encode(['ok'=>false]); exit(); }
    $msg_e  = mysqli_real_escape_string($con, $msg);
    $sender = $is_admin ? 'admin' : 'user';

    if ($sender === 'admin') {
        $target = mysqli_real_escape_string($con, $_POST['target_sid'] ?? '');
        if ($target === '') { echo json_encode(['ok'=>false]); exit(); }
        mysqli_query($con, "INSERT INTO live_chat_messages (session_id, sender, message) VALUES ('$target','admin','$msg_e')");
        mysqli_query($con, "UPDATE live_chat_messages SET is_read=1 WHERE session_id='$target' AND sender='user'");
    } else {
        $uid_sql = $uid ? $uid : 'NULL';
        mysqli_query($con, "INSERT INTO live_chat_messages (session_id, user_id, sender, message) VALUES ('$sid_e',$uid_sql,'user','$msg_e')");
    }
    echo json_encode(['ok'=>true, 'id'=>mysqli_insert_id($con)]);
    exit();
}

/* ── POLL ── */
if ($action === 'poll') {
    $last_id = intval($_POST['last_id'] ?? $_GET['last_id'] ?? 0);

    if ($is_admin) {
        // Admin: poll a specific session
        $target = mysqli_real_escape_string($con, $_POST['target_sid'] ?? '');
        if ($target === '') { echo json_encode(['msgs'=>[]]); exit(); }
        $q = mysqli_query($con,
            "SELECT id, sender, message, created_at
             FROM live_chat_messages
             WHERE session_id='$target' AND id>$last_id
             ORDER BY id ASC LIMIT 50");
    } else {
        // User: always poll by their own session_id only
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
    // Step 1: get sessions
    $q = mysqli_query($con,
        "SELECT session_id,
                MAX(id) AS last_id,
                MAX(created_at) AS last_msg,
                MAX(user_id) AS user_id,
                SUM(CASE WHEN sender='user' AND is_read=0 THEN 1 ELSE 0 END) AS unread
         FROM live_chat_messages
         WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY session_id
         ORDER BY last_msg DESC
         LIMIT 50");
    $sessions = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            // Step 2: get preview (last user message)
            $sid_tmp = mysqli_real_escape_string($con, $r['session_id']);
            $pq = mysqli_query($con, "SELECT message FROM live_chat_messages WHERE session_id='$sid_tmp' AND sender='user' ORDER BY id DESC LIMIT 1");
            $r['preview'] = ($pq && $pr = mysqli_fetch_assoc($pq)) ? $pr['message'] : null;
            // Step 3: get user name/email if logged in
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

/* ── LINK: vincular chat_sid al user_id cuando el usuario se loguea ── */
if ($action === 'link') {
    if ($is_admin) { echo json_encode(['ok'=>false]); exit(); }
    // Requiere que haya sesión de usuario frontend activa
    if (!$uid) { echo json_encode(['ok'=>false]); exit(); }
    // Actualizar todos los mensajes de ese chat_sid con el user_id real
    mysqli_query($con, "UPDATE live_chat_messages SET user_id=$uid WHERE session_id='$sid_e' AND user_id IS NULL");
    echo json_encode(['ok'=>true]);
    exit();
}

echo json_encode(['error' => 'unknown action']);
