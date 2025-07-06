<?php
// logout.php
session_start();

// Update online status to offline
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/telegram.php';
    
    $telegram = getTelegramBot();
    $telegram->updateOnlineStatus($_SESSION['user_id'], false);
}

session_destroy();
header('Location: /index.php');
exit;
