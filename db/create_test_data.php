<?php
// db/create_test_data.php
// Create test user and add some credits

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

// Create test user
$username = 'testuser';
$email = 'test@example.com';
$password = 'password123';

// Check if user already exists
$stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Test user already exists!\n";
} else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);
    
    $user_id = $db->lastInsertId();
    
    // Create wallet
    $stmt = $db->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 100.00)');
    $stmt->execute([$user_id]);
    
    // Add credit transaction
    $stmt = $db->prepare('INSERT INTO transactions (user_id, amount, type) VALUES (?, 100.00, "initial_credit")');
    $stmt->execute([$user_id]);
    
    echo "Test user created successfully!\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Starting balance: $100.00\n";
}

// Add some test items with different stock settings
$items = [
    [
        'title' => 'Limited Edition Guide',
        'price' => 19.99,
        'content' => 'This is a limited edition guide that only 3 people can purchase. It contains exclusive insider information and strategies that are not available anywhere else.',
        'is_limited' => 1,
        'stock_limit' => 3
    ],
    [
        'title' => 'Unlimited Resource Pack',
        'price' => 9.99,
        'content' => 'This resource pack can be purchased by unlimited users. It includes templates, guides, and tools that will help you succeed.',
        'is_limited' => 0,
        'stock_limit' => -1
    ],
    [
        'title' => 'Exclusive One-Time Offer',
        'price' => 49.99,
        'content' => 'This is a one-time exclusive offer that only one person can purchase. Once sold, it will never be available again.',
        'is_limited' => 1,
        'stock_limit' => 1
    ]
];

foreach ($items as $item) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM items WHERE title = ?');
    $stmt->execute([$item['title']]);
    
    if ($stmt->fetchColumn() == 0) {
        $stmt = $db->prepare('INSERT INTO items (title, price, content, is_limited, stock_limit, purchases_count) VALUES (?, ?, ?, ?, ?, 0)');
        $stmt->execute([$item['title'], $item['price'], $item['content'], $item['is_limited'], $item['stock_limit']]);
        echo "Added item: " . $item['title'] . "\n";
    }
}

echo "Test data created successfully!\n";
