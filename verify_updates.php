<?php
// verify_updates.php - Verify that users were updated correctly
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Verify User Updates</h1>";

$db = get_db();

// Get all users with Telegram IDs
$stmt = $db->prepare('SELECT id, username, telegram_id, telegram_username, telegram_first_name, telegram_photo_url FROM users WHERE telegram_id IS NOT NULL');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($users) . " users with Telegram IDs</p>";

foreach ($users as $user) {
    echo "<h3>User ID: {$user['id']}</h3>";
    echo "<ul>";
    echo "<li>Username: {$user['username']}</li>";
    echo "<li>Telegram ID: {$user['telegram_id']}</li>";
    echo "<li>Telegram Username: " . ($user['telegram_username'] ?? 'N/A') . "</li>";
    echo "<li>Telegram First Name: " . ($user['telegram_first_name'] ?? 'N/A') . "</li>";
    echo "<li>Telegram Photo URL: " . ($user['telegram_photo_url'] ?? 'N/A') . "</li>";
    echo "</ul>";
    
    // Check if this user has proper info now
    if ($user['telegram_username'] || $user['telegram_first_name'] !== 'User') {
        echo "<p>✅ User has updated Telegram information</p>";
    } else {
        echo "<p>❌ User still has default/missing information</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Summary</h2>";
echo "<p>Verification completed. Users should now show proper Telegram information in their profiles.</p>";
?>
