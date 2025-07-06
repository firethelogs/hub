<?php
// update_existing_users.php - Update existing users' Telegram information
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/telegram.php';

echo "<h1>Update Existing Users' Telegram Information</h1>";

$telegram = getTelegramBot();
$db = get_db();

// Get all users with Telegram IDs but missing profile information
$stmt = $db->prepare('SELECT id, username, telegram_id, telegram_username, telegram_first_name, telegram_photo_url FROM users WHERE telegram_id IS NOT NULL');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($users) . " users with Telegram IDs</p>";

foreach ($users as $user) {
    echo "<h3>User: {$user['username']} (ID: {$user['id']})</h3>";
    echo "<p>Telegram ID: {$user['telegram_id']}</p>";
    echo "<p>Current Telegram Username: " . ($user['telegram_username'] ?? 'N/A') . "</p>";
    echo "<p>Current First Name: " . ($user['telegram_first_name'] ?? 'N/A') . "</p>";
    echo "<p>Current Photo URL: " . ($user['telegram_photo_url'] ?? 'N/A') . "</p>";
    
    // Fetch fresh information from Telegram
    $telegramInfo = $telegram->getUserInfo($user['telegram_id']);
    
    if ($telegramInfo) {
        echo "<p>✅ Fresh Telegram info found:</p>";
        echo "<ul>";
        echo "<li>Username: " . ($telegramInfo['username'] ?? 'N/A') . "</li>";
        echo "<li>First Name: " . ($telegramInfo['first_name'] ?? 'N/A') . "</li>";
        echo "<li>Last Name: " . ($telegramInfo['last_name'] ?? 'N/A') . "</li>";
        echo "<li>Photo URL: " . (isset($telegramInfo['photo_url']) && $telegramInfo['photo_url'] ? 'Available' : 'N/A') . "</li>";
        echo "</ul>";
        
        // Update the user's information
        $success = $telegram->updateUserTelegramInfo($user['id'], $user['telegram_id']);
        if ($success) {
            echo "<p>✅ User information updated successfully</p>";
        } else {
            echo "<p>❌ Failed to update user information</p>";
        }
    } else {
        echo "<p>❌ Could not fetch fresh Telegram info</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Summary</h2>";
echo "<p>Update process completed. Users should now have proper Telegram usernames and profile pictures.</p>";
?>
