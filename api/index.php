<?php
// Vercel entry point — routes every request to the matching PHP page
// in the project root (same layout as the original XAMPP app).
// Based on the Apkora Vercel attempt (vercel-php@0.9.0, api/index.php router).
$root = dirname(__DIR__);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uri, '/');
if ($path === '' || $path === 'index.php') {
    $path = 'login.php';
}

// Only allow real .php pages inside the project (no traversal, no api/, no includes/).
if (strpos($path, '..') !== false || strpos($path, 'api/') === 0 || strpos($path, 'includes/') === 0) {
    http_response_code(404);
    echo "Not found.";
    exit;
}
$allowed_prefixes = ['admin/', 'faculty/', 'student/', 'accountant/'];
$allowed_roots = ['login.php', 'logout.php', 'register.php', 'setup.php'];
$ok = in_array($path, $allowed_roots, true);
if (!$ok) {
    foreach ($allowed_prefixes as $p) {
        if (strpos($path, $p) === 0) { $ok = true; break; }
    }
}
$file = $root . '/' . $path;
if (!$ok || substr($path, -4) !== '.php' || !is_file($file)) {
    http_response_code(404);
    echo "Page not found: " . htmlspecialchars($path);
    exit;
}

// Run the page from its own directory so relative includes
// (e.g. include '../includes/db.php') resolve like on XAMPP.
chdir(dirname($file));
$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['SCRIPT_NAME'] = '/' . $path;
$_SERVER['PHP_SELF'] = '/' . $path;
require $file;
?>
