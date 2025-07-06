<?php
// debug.php - Simple debug page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "🐛 Debug Information\n";
echo "==================\n\n";

echo "1. PHP Version: " . phpversion() . "\n";
echo "2. Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "3. Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "4. Current Directory: " . __DIR__ . "\n";
echo "5. Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";

echo "\n6. File Checks:\n";
$files = [
    '/includes/header.php',
    '/includes/footer.php', 
    '/includes/functions.php',
    '/includes/auth.php',
    '/assets/style.css',
    '/index.php'
];

foreach ($files as $file) {
    $path = __DIR__ . $file;
    echo "   " . $file . ": " . (file_exists($path) ? "✅ Exists" : "❌ Missing") . "\n";
}

echo "\n7. Database Check:\n";
try {
    require_once __DIR__ . '/includes/functions.php';
    $db = get_db();
    $stmt = $db->prepare('SELECT COUNT(*) FROM users');
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "   Database: ✅ Connected ($count users)\n";
} catch (Exception $e) {
    echo "   Database: ❌ Error - " . $e->getMessage() . "\n";
}

echo "\n8. Session Check:\n";
session_start();
echo "   Session ID: " . session_id() . "\n";
echo "   User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "\n";
echo "   Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set') . "\n";

echo "\n9. Header Test:\n";
ob_start();
try {
    include __DIR__ . '/includes/header.php';
    $header_output = ob_get_contents();
    ob_end_clean();
    echo "   Header: ✅ Loaded successfully\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "   Header: ❌ Error - " . $e->getMessage() . "\n";
}

echo "\n10. CSS Check:\n";
$css_path = __DIR__ . '/assets/style.css';
if (file_exists($css_path)) {
    $css_size = filesize($css_path);
    echo "   CSS: ✅ Exists ($css_size bytes)\n";
} else {
    echo "   CSS: ❌ Missing\n";
}

echo "\n🎯 Debug complete! Check the output above for issues.\n";
?>
