<?php
// db/ensure_telegram_tables.php
// Ensure all Telegram tables and fields exist

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

try {
    // Check if telegram_otps table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='telegram_otps'");
    if (!$result->fetch()) {
        $db->exec('
        CREATE TABLE telegram_otps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            telegram_id TEXT NOT NULL,
            otp_code TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            used INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ');
        echo "Created telegram_otps table\n";
    } else {
        echo "telegram_otps table already exists\n";
    }

    // Check if telegram_sessions table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='telegram_sessions'");
    if (!$result->fetch()) {
        $db->exec('
        CREATE TABLE telegram_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            telegram_id TEXT NOT NULL,
            session_token TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        ');
        echo "Created telegram_sessions table\n";
    } else {
        echo "telegram_sessions table already exists\n";
    }

    // Add missing columns to users table if they don't exist
    $columns = [
        'last_seen' => 'DATETIME'
    ];

    foreach ($columns as $column => $type) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN $column $type");
            echo "Added column $column to users table\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') === false) {
                echo "Error adding column $column: " . $e->getMessage() . "\n";
            } else {
                echo "Column $column already exists\n";
            }
        }
    }

    echo "Telegram database setup complete!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
