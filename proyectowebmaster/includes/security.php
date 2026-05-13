<?php
// =============================================
// Security Helper – MotoDventures
// =============================================

// CSRF token generation and validation
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify() {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Solicitud no válida. Por favor recarga la página e intenta de nuevo.');
    }
}

// Safe integer from GET/POST
function safe_int($value, $default = 0) {
    return intval($value ?? $default);
}

// Sanitize string input using mysqli_real_escape_string
function safe_str($con, $value) {
    return mysqli_real_escape_string($con, trim($value ?? ''));
}

// Whitelist check: value must be in allowed list
function safe_whitelist($value, array $allowed, $default = '') {
    return in_array($value, $allowed, true) ? $value : $default;
}

// ── Rate limiting (brute-force protection) ─────────────────────────────────
// Uses session-based counter per IP+action. Max $max attempts per $window seconds.
function rate_limit_check(string $action, int $max = 5, int $window = 300): bool {
    $key   = 'rl_' . $action . '_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $now   = time();
    $entry = $_SESSION[$key] ?? ['count' => 0, 'start' => $now];

    if ($now - $entry['start'] > $window) {
        // Reset window
        $entry = ['count' => 0, 'start' => $now];
    }
    $entry['count']++;
    $_SESSION[$key] = $entry;

    return $entry['count'] > $max;
}

function rate_limit_remaining(string $action, int $max = 5, int $window = 300): int {
    $key   = 'rl_' . $action . '_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $entry = $_SESSION[$key] ?? ['count' => 0, 'start' => time()];
    return max(0, $max - $entry['count']);
}

function rate_limit_reset(string $action): void {
    $key = 'rl_' . $action . '_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    unset($_SESSION[$key]);
}

// ── Safe file upload ────────────────────────────────────────────────────────
// Returns sanitized filename on success, false on failure.
// Validates extension AND MIME type.
function safe_image_upload(array $file, string $dest_dir, string $prefix = 'img'): string|false {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return false;

    $allowed_ext  = ['jpg','jpeg','png','gif','webp'];
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
    $max_size     = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $max_size) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) return false;

    if (!function_exists('finfo_open')) return false; // finfo required
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_mime, true)) return false;

    // Sanitized, unique filename — no original name in path
    $fname = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest  = rtrim($dest_dir, '/') . '/' . $fname;

    if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    return $fname;
}
?>
