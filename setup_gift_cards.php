<?php
// create_gift_cards_table.php - Database setup for gift cards
require_once 'includes/functions.php';

try {
    $db = get_db();
    
    // Create gift_cards table
    $sql = "CREATE TABLE IF NOT EXISTS gift_cards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_by INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        redeemed_by INTEGER DEFAULT NULL,
        redeemed_at DATETIME DEFAULT NULL,
        is_active BOOLEAN DEFAULT 1,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (redeemed_by) REFERENCES users(id)
    )";
    
    $db->exec($sql);
    
    // Create gift_card_redemptions table for tracking
    $sql = "CREATE TABLE IF NOT EXISTS gift_card_redemptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        gift_card_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        redeemed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address TEXT,
        user_agent TEXT,
        FOREIGN KEY (gift_card_id) REFERENCES gift_cards(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $db->exec($sql);
    
    echo "✅ Gift cards database tables created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating gift cards tables: " . $e->getMessage() . "\n";
}
?>
