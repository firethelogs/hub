<?php
// db/clear_store.php
// Delete all items from the store

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

// First, delete all purchases (to maintain referential integrity)
$db->exec('DELETE FROM purchases');
echo "Deleted all purchases.\n";

// Then delete all items
$db->exec('DELETE FROM items');
echo "Deleted all store items.\n";

// Reset the auto-increment counter for items
$db->exec('DELETE FROM sqlite_sequence WHERE name="items"');
$db->exec('DELETE FROM sqlite_sequence WHERE name="purchases"');
echo "Reset item and purchase counters.\n";

echo "Store is now empty!\n";
