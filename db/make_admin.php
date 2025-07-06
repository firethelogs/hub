<?php
// db/make_admin.php
// Run this script to make an existing user an admin

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

$username = 'your_username_here'; // Change this to the username you want to make admin

$stmt = $db->prepare('UPDATE users SET is_admin = 1 WHERE username = ?');
$stmt->execute([$username]);

if ($stmt->rowCount() > 0) {
    echo "User '$username' is now an admin!\n";
} else {
    echo "User '$username' not found.\n";
}
