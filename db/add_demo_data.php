<?php
// db/add_demo_data.php
// Add some demo items to test the store

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

// Demo items
$items = [
    [
        'title' => 'Premium Strategy Guide',
        'price' => 9.99,
        'content' => 'This is the complete premium strategy guide that reveals all the secrets to mastering our platform. Learn advanced techniques, insider tips, and proven strategies that successful users employ to maximize their results. This guide contains step-by-step instructions, real-world examples, and expert insights that you won\'t find anywhere else.'
    ],
    [
        'title' => 'Exclusive Interview Collection',
        'price' => 14.99,
        'content' => 'Get access to our exclusive collection of interviews with industry leaders and successful entrepreneurs. These candid conversations reveal the mindset, strategies, and daily habits that led to their success. Learn from their failures, understand their decision-making process, and discover the key moments that changed their trajectory.'
    ],
    [
        'title' => 'Advanced Training Module',
        'price' => 24.99,
        'content' => 'This comprehensive training module covers advanced concepts and techniques that go beyond the basics. Includes video walkthroughs, detailed explanations, practical exercises, and real-world case studies. Perfect for users who want to take their skills to the next level and achieve professional-grade results.'
    ],
    [
        'title' => 'Secret Recipe Collection',
        'price' => 7.99,
        'content' => 'Discover our collection of secret recipes that have been passed down through generations. Each recipe includes detailed instructions, ingredient lists, cooking tips, and the fascinating stories behind these culinary treasures. Transform your cooking with these hidden gems from around the world.'
    ]
];

foreach ($items as $item) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM items WHERE title = ?');
    $stmt->execute([$item['title']]);
    
    if ($stmt->fetchColumn() == 0) {
        $stmt = $db->prepare('INSERT INTO items (title, price, content) VALUES (?, ?, ?)');
        $stmt->execute([$item['title'], $item['price'], $item['content']]);
        echo "Added: " . $item['title'] . "\n";
    } else {
        echo "Already exists: " . $item['title'] . "\n";
    }
}

echo "Demo data added successfully!\n";
