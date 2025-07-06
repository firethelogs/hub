<?php
// db/migrate_telegram.php
// Add Telegram fields to users table

$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

$db->exec('
ALTER TABLE users ADD COLUMN telegram_id TEXT;
ALTER TABLE users ADD COLUMN telegram_username TEXT;
ALTER TABLE users ADD COLUMN telegram_first_name TEXT;
ALTER TABLE users ADD COLUMN telegram_last_name TEXT;
ALTER TABLE users ADD COLUMN telegram_photo_url TEXT;
ALTER TABLE users ADD COLUMN telegram_auth_date INTEGER;
ALTER TABLE users ADD COLUMN is_online INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN last_seen DATETIME;

CREATE TABLE IF NOT EXISTS telegram_otps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id TEXT NOT NULL,
    otp_code TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    used INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS telegram_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    telegram_id TEXT NOT NULL,
    session_token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
');

echo "Telegram tables and fields added successfully!\n";
