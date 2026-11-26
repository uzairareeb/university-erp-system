<?php
// DB connection — env vars first (Vercel / TiDB), local XAMPP fallback.
// Vercel env vars: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSL=1
// TiDB Cloud needs SSL + port 4000. DATABASE_URL (mysql://...) is also accepted.
if (session_status() === PHP_SESSION_NONE) {
    // Do NOT override session.save_path here: many pages call
    // session_start() before including db.php, so a different path here
    // would make the login session invisible to the dashboard
    // (local test caught this: login redirected, dashboard said Access Denied).
    // PHP's default path is used consistently everywhere; on Vercel that is /tmp.
    session_start();
}

function erp_env($k, $def = '') {
    $v = getenv($k);
    if ($v === false || $v === '') $v = isset($_SERVER[$k]) ? $_SERVER[$k] : $def;
    return $v;
}

$host = erp_env('DB_HOST', 'localhost');
$port = (int) erp_env('DB_PORT', '3306');
$user = erp_env('DB_USER', 'root');
$pass = erp_env('DB_PASS', '');
$dbname = erp_env('DB_NAME', 'university_erp');
$use_ssl = erp_env('DB_SSL', '0') === '1';

// DATABASE_URL support: mysql://user:pass@host:port/db?sslaccept=strict
$dburl = erp_env('DATABASE_URL', '');
if ($dburl !== '') {
    $u = parse_url($dburl);
    if ($u !== false && !empty($u['host'])) {
        $host = $u['host'];
        $port = isset($u['port']) ? (int) $u['port'] : 4000;
        $user = isset($u['user']) ? urldecode($u['user']) : $user;
        $pass = isset($u['pass']) ? urldecode($u['pass']) : $pass;
        $dbname = isset($u['path']) ? ltrim($u['path'], '/') : $dbname;
        if (strpos($dburl, 'ssl') !== false) $use_ssl = true;
    }
}

$conn = mysqli_init();
if ($conn === false) {
    die("Database init failed.");
}
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
if ($use_ssl) {
    // TiDB Cloud requires TLS; no client cert needed, server CA is verified by default chain.
    $conn->ssl_set(null, null, null, null, null);
    $flags = MYSQLI_CLIENT_SSL;
    if (!$conn->real_connect($host, $user, $pass, $dbname, $port, null, $flags)) {
        die("Connection failed: " . $conn->connect_error);
    }
} else {
    if (!$conn->real_connect($host, $user, $pass, $dbname, $port)) {
        die("Connection failed: " . $conn->connect_error);
    }
}
$conn->set_charset('utf8mb4');

// Resolve a student record id from a logged-in user id.
// Students table has user_id; older data may have id == user id, so fall back to that.
function erp_student_id($conn, $user_id) {
    $user_id = (int) $user_id;
    $stmt = $conn->prepare("SELECT id FROM students WHERE user_id=? OR id=? ORDER BY user_id DESC LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $row = $r->fetch_assoc()) return (int) $row['id'];
    return null;
}
?>
