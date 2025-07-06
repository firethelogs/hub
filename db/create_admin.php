<?php
// db/create_admin.php
// Run this script to create an admin user

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

// Admin credentials
$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123'; // Change this to a secure password

// Check if admin already exists
$stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Admin user already exists!\n";
    exit;
}

// Create admin user
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)');
$stmt->execute([$username, $email, $hash]);

$user_id = $db->lastInsertId();

// Create wallet for admin
$stmt = $db->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 0)');
$stmt->execute([$user_id]);

echo "Admin user created successfully!\n";
echo "Username: $username\n";
echo "Password: $password\n";
echo "You can now login at /admin/login.php\n";
