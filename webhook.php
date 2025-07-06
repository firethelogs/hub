<?php
// webhook.php - Telegram Bot Webhook Handler
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/telegram.php';

// Get webhook data
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Log webhook for debugging (optional)
file_put_contents(__DIR__ . '/logs/webhook.log', date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);

if (!$update) {
    http_response_code(400);
    exit('Invalid JSON');
}

$telegram = getTelegramBot();
$chatId = $update['message']['chat']['id'] ?? null;
$messageText = $update['message']['text'] ?? '';
$userId = $update['message']['from']['id'] ?? null;

if (!$chatId || !$userId) {
    http_response_code(200);
    exit('OK');
}

// Handle different commands
switch ($messageText) {
    case '/start':
        $welcomeMessage = "🎯 <b>Welcome to JaxxyCC Store Bot!</b>\n\n";
        $welcomeMessage .= "I'm here to help you with secure login and notifications.\n\n";
        $welcomeMessage .= "📱 <b>Your Telegram ID:</b> <code>{$userId}</code>\n";
        $welcomeMessage .= "<i>Use this ID to login on our website</i>\n\n";
        $welcomeMessage .= "🔗 <b>Website:</b> https://jaxxycc.store\n\n";
        $welcomeMessage .= "🆘 <b>Commands:</b>\n";
        $welcomeMessage .= "/start - Show this message\n";
        $welcomeMessage .= "/id - Get your Telegram ID\n";
        $welcomeMessage .= "/help - Get help\n";
        
        $telegram->sendMessage($chatId, $welcomeMessage);
        break;
        
    case '/id':
        $idMessage = "📱 <b>Your Telegram ID:</b> <code>{$userId}</code>\n\n";
        $idMessage .= "Use this ID to login on JaxxyCC Store website.";
        
        $telegram->sendMessage($chatId, $idMessage);
        break;
        
    case '/help':
        $helpMessage = "🆘 <b>JaxxyCC Store Bot Help</b>\n\n";
        $helpMessage .= "This bot provides secure login and notifications for JaxxyCC Store.\n\n";
        $helpMessage .= "📱 <b>How to use:</b>\n";
        $helpMessage .= "1. Get your Telegram ID with /id\n";
        $helpMessage .= "2. Visit the website and use Telegram Login\n";
        $helpMessage .= "3. Enter your Telegram ID\n";
        $helpMessage .= "4. I'll send you an OTP code\n";
        $helpMessage .= "5. Enter the OTP to login\n\n";
        $helpMessage .= "🔔 <b>Notifications:</b>\n";
        $helpMessage .= "• Purchase confirmations\n";
        $helpMessage .= "• Balance updates\n";
        $helpMessage .= "• Account notifications\n\n";
        $helpMessage .= "🔗 <b>Website:</b> https://jaxxycc.store";
        
        $telegram->sendMessage($chatId, $helpMessage);
        break;
        
    default:
        // Handle unknown messages
        $unknownMessage = "❓ I don't understand that command.\n\n";
        $unknownMessage .= "Try these commands:\n";
        $unknownMessage .= "/start - Welcome message\n";
        $unknownMessage .= "/id - Get your Telegram ID\n";
        $unknownMessage .= "/help - Get help\n";
        
        $telegram->sendMessage($chatId, $unknownMessage);
        break;
}

// Return success
http_response_code(200);
echo 'OK';
?>
