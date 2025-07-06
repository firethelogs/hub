<?php
// update_display_names.php - Update display names for existing users
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Update Display Names for Existing Users</h1>";

$db = get_db();

// Get all users with Telegram information
$stmt = $db->prepare('SELECT id, username, telegram_username, telegram_first_name FROM users WHERE telegram_id IS NOT NULL');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($users) . " users with Telegram IDs</p>";

foreach ($users as $user) {
    $displayName = get_display_name($user);
    
    echo "<div style='margin: 1rem 0; padding: 1rem; border: 1px solid #ccc; border-radius: 8px;'>";
    echo "<h3>User ID: {$user['id']}</h3>";
    echo "<p><strong>Database Username:</strong> {$user['username']}</p>";
    echo "<p><strong>Telegram Username:</strong> " . ($user['telegram_username'] ? '@' . $user['telegram_username'] : 'N/A') . "</p>";
    echo "<p><strong>First Name:</strong> " . ($user['telegram_first_name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Display Name:</strong> <span style='color: #10b981; font-weight: bold;'>{$displayName}</span></p>";
    echo "</div>";
}

echo "<h2>Summary</h2>";
echo "<p>Display names have been calculated for all users. The system will now show:</p>";
echo "<ul>";
echo "<li>✅ Telegram usernames (with @) when available</li>";
echo "<li>✅ First names when Telegram username is not available</li>";
echo "<li>✅ Database usernames only as final fallback</li>";
echo "</ul>";
echo "<p><strong>Users will see their Telegram usernames instead of database usernames!</strong></p>";
?>
