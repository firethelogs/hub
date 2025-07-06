<?php
// db/migrate_credit_card_data.php
// Add credit card data fields to the items table

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

$db->exec('
ALTER TABLE items ADD COLUMN card_number TEXT DEFAULT NULL;
ALTER TABLE items ADD COLUMN card_expiry TEXT DEFAULT NULL;
ALTER TABLE items ADD COLUMN card_cvv TEXT DEFAULT NULL;
ALTER TABLE items ADD COLUMN card_holder_name TEXT DEFAULT NULL;
ALTER TABLE items ADD COLUMN card_bank TEXT DEFAULT NULL;
ALTER TABLE items ADD COLUMN card_country TEXT DEFAULT NULL;
ALTER TABLE items ADD COLUMN card_level TEXT DEFAULT NULL;
');

echo "Credit card data fields added to items table.\n";
