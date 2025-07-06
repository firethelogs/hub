<?php
// db/migrate_credit_cards.php
// Add credit card columns to existing items table

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

try {
    // Add new columns to items table
    $db->exec('ALTER TABLE items ADD COLUMN is_credit_card INTEGER DEFAULT 0');
    $db->exec('ALTER TABLE items ADD COLUMN credit_card_type TEXT DEFAULT NULL');
    $db->exec('ALTER TABLE items ADD COLUMN credit_card_details TEXT DEFAULT NULL');
    
    echo "Database migrated successfully!\n";
    echo "Added columns: is_credit_card, credit_card_type, credit_card_details\n";
} catch (Exception $e) {
    echo "Migration completed (columns may already exist)\n";
}
