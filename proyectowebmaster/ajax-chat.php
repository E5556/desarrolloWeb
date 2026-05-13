<?php
session_start();
error_reporting(0);
include('includes/config.php');
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Auto-create table
mysqli_query($con, "CREATE TABLE IF NOT EXISTS live_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(80) NOT NULL,
    user_id INT DEFAULT NULL,
    sender ENUM('user','admin') NOT NULL DEFAULT 'user',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(session_id),
    INDEX(created_at)
)");

$sid = session_id();
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : null;

if ($action === 'send') {
    $msg = trim($_POST['msg'] ?? '');
    if ($msg === '' || strlen($msg) > 1000) { echo json_encode(['ok'=>false]); exit(); }
    $msg_e = mysqli_real_escape_string($con, $msg);
    $sid_e = mysqli_real_escape_string($con, $sid);
    $sender = isset($_SESSION['alogin']) ? 'admin' : 'user';
    // Admin sends to specific session
    if ($sender === 'admin') {
        $target_sid = mysqli_real_escape_string($con, $_POST['target_sid'] ?? $sid);
        mysqli_query($con,"INSERT INTO live_chat_messages (session_id,sender,message) VALUES ('$target_sid','admin','$msg_e')");
        // Mark user messages as read
        mysqli_query($con,"UPDATE live_chat_messages SET is_read=1 WHERE session_id='$target_sid' AND sender='user'");
    } else {
        mysqli_query($con,"INSERT INTO live_chat_messages (session_id,user_id,sender,message) VALUES ('$sid_e',".($uid?$uid:'NULL').",'user','$msg_e')");
    }
    echo json_encode(['ok'=>true]); exit();
}

if ($action === 'poll') {
    $last_id = intval($_POST['last_id'] ?? $_GET['last_id'] ?? 0);
    $target_sid = isset($_SESSION['alogin'])
        ? mysqli_real_escape_string($con, $_POST['target_sid'] ?? '')
        : mysqli_real_escape_string($con, $sid);

    if ($target_sid === '') { echo json_encode(['msgs'=>[]]); exit(); }

    $q = mysqli_query($con,
        "SELECT id,sender,message,created_at FROM live_chat_messages
         WHERE session_id='$target_sid' AND id>$last_id ORDER BY id ASC LIMIT 30");
    $msgs = [];
    while ($r = mysqli_fetch_assoc($q)) $msgs[] = $r;
    echo json_encode(['msgs'=>$msgs]); exit();
}

if ($action === 'sessions') {
    // Admin: get list of active chat sessions (last 24h)
    if (empty($_SESSION['alogin'])) { echo json_encode(['sessions'=>[]]); exit(); }
    $q = mysqli_query($con,
        "SELECT session_id, MAX(id) as last_id, MAX(created_at) as last_msg,
                SUM(CASE WHEN sender='user' AND is_read=0 THEN 1 ELSE 0 END) as unread,
                GROUP_CONCAT(CASE WHEN sender='user' THEN message END ORDER BY id DESC SEPARATOR ' | ') as preview
         FROM live_chat_messages
         WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY session_id ORDER BY last_msg DESC LIMIT 30");
    $sessions = [];
    while ($r = mysqli_fetch_assoc($q)) $sessions[] = $r;
    echo json_encode(['sessions'=>$sessions]); exit();
}

echo json_encode(['error'=>'unknown action']);
