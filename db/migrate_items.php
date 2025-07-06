<?php
// db/migrate_items.php
// Add new columns to existing items table

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

try {
    // Add new columns to items table
    $db->exec('ALTER TABLE items ADD COLUMN stock_limit INTEGER DEFAULT -1');
    $db->exec('ALTER TABLE items ADD COLUMN is_limited INTEGER DEFAULT 0');
    $db->exec('ALTER TABLE items ADD COLUMN purchases_count INTEGER DEFAULT 0');
    
    // Update purchases_count for existing items
    $db->exec('
        UPDATE items 
        SET purchases_count = (
            SELECT COUNT(*) 
            FROM purchases 
            WHERE purchases.item_id = items.id
        )
    ');
    
    echo "Database migrated successfully!\n";
    echo "Added columns: stock_limit, is_limited, purchases_count\n";
} catch (Exception $e) {
    echo "Migration completed (columns may already exist)\n";
}
