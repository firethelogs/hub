<?php
// includes/telegram.php
// Telegram Bot Integration

class TelegramBot {
    private $token;
    private $apiUrl;
    private $db;
    
    public function __construct($token, $db) {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
        $this->db = $db;
    }
    
    // Send message to user
    public function sendMessage($chatId, $message, $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->makeRequest('sendMessage', $data);
    }
    
    // Generate and send OTP
    public function sendOTP($telegramId, $username = null) {
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        // Store OTP in database
        $stmt = $this->db->prepare('INSERT INTO telegram_otps (telegram_id, otp_code, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$telegramId, $otp, $expiresAt]);
        
        $message = "🔐 <b>JaxxyCC Store - Login OTP</b>\n\n";
        $message .= "Your one-time password: <code>{$otp}</code>\n\n";
        $message .= "⏰ Valid for 5 minutes\n";
        $message .= "🔒 Use this code to login to JaxxyCC Store\n\n";
        $message .= "<i>If you didn't request this, please ignore this message.</i>";
        
        return $this->sendMessage($telegramId, $message);
    }
    
    // Verify OTP
    public function verifyOTP($telegramId, $otpCode) {
        $stmt = $this->db->prepare('
            SELECT * FROM telegram_otps 
            WHERE telegram_id = ? AND otp_code = ? AND used = 0 AND expires_at > ?
            ORDER BY created_at DESC LIMIT 1
        ');
        $stmt->execute([$telegramId, $otpCode, date('Y-m-d H:i:s')]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otp) {
            // Mark OTP as used
            $stmt = $this->db->prepare('UPDATE telegram_otps SET used = 1 WHERE id = ?');
            $stmt->execute([$otp['id']]);
            return true;
        }
        
        return false;
    }
    
    // Debug OTP verification
    public function debugOTP($telegramId, $otpCode) {
        $stmt = $this->db->prepare('
            SELECT *, expires_at, datetime("now") as current_time 
            FROM telegram_otps 
            WHERE telegram_id = ? AND otp_code = ?
            ORDER BY created_at DESC LIMIT 1
        ');
        $stmt->execute([$telegramId, $otpCode]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $otp;
    }

    // Get user info from Telegram
    public function getUserInfo($telegramId) {
        $data = ['chat_id' => $telegramId];
        $response = $this->makeRequest('getChat', $data);
        
        if ($response && $response['ok']) {
            $userInfo = $response['result'];
            
            // Get profile photo if available
            if (!empty($userInfo['photo']) && !empty($userInfo['photo']['big_file_id'])) {
                $photoUrl = $this->getPhotoUrl($userInfo['photo']['big_file_id']);
                if ($photoUrl) {
                    $userInfo['photo_url'] = $photoUrl;
                }
            }
            
            return $userInfo;
        }
        
        return null;
    }
    
    // Get profile photo URL
    public function getPhotoUrl($fileId) {
        $data = ['file_id' => $fileId];
        $response = $this->makeRequest('getFile', $data);
        
        if ($response && $response['ok'] && !empty($response['result']['file_path'])) {
            return "https://api.telegram.org/file/bot{$this->token}/" . $response['result']['file_path'];
        }
        
        return null;
    }
    
    // Send purchase notification
    public function sendPurchaseNotification($telegramId, $itemTitle, $price) {
        $message = "🛒 <b>Purchase Successful!</b>\n\n";
        $message .= "📦 Item: <b>{$itemTitle}</b>\n";
        $message .= "💰 Price: <b>\${$price}</b>\n\n";
        $message .= "✅ Your purchase has been processed successfully!\n";
        $message .= "🔓 You can now access your content in the store.\n\n";
        $message .= "Thank you for shopping with JaxxyCC Store! 🎯";
        
        return $this->sendMessage($telegramId, $message);
    }
    
    // Send balance notification
    public function sendBalanceNotification($telegramId, $amount, $newBalance) {
        $message = "💳 <b>Account Recharged!</b>\n\n";
        $message .= "💰 Amount Added: <b>\${$amount}</b>\n";
        $message .= "💎 New Balance: <b>\${$newBalance}</b>\n\n";
        $message .= "✅ Your account has been recharged successfully!\n";
        $message .= "🛍️ Ready to shop at JaxxyCC Store! 🎯";
        
        return $this->sendMessage($telegramId, $message);
    }
    
    // Send welcome message
    public function sendWelcomeMessage($telegramId, $username) {
        $message = "🎯 <b>Welcome to JaxxyCC Store!</b>\n\n";
        $message .= "Hello <b>{$username}</b>! 👋\n\n";
        $message .= "🔐 Your Telegram account has been successfully linked!\n";
        $message .= "🚀 You can now use Telegram OTP to login quickly and securely.\n\n";
        $message .= "📱 <b>Features available:</b>\n";
        $message .= "• 🔑 Quick OTP login\n";
        $message .= "• 🛒 Purchase notifications\n";
        $message .= "• 💰 Balance updates\n";
        $message .= "• 📊 Account status\n\n";
        $message .= "Start shopping now! 🛍️";
        
        return $this->sendMessage($telegramId, $message);
    }
    
    // Update user online status
    public function updateOnlineStatus($userId, $isOnline = true) {
        $status = $isOnline ? 1 : 0;
        $lastSeen = date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare('UPDATE users SET is_online = ?, last_seen = ? WHERE id = ?');
        return $stmt->execute([$status, $lastSeen, $userId]);
    }
    
    // Get user by Telegram ID
    public function getUserByTelegramId($telegramId) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE telegram_id = ?');
        $stmt->execute([$telegramId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Link Telegram account to user
    public function linkTelegramAccount($userId, $telegramData) {
        $stmt = $this->db->prepare('
            UPDATE users SET 
                telegram_id = ?, 
                telegram_username = ?, 
                telegram_first_name = ?, 
                telegram_last_name = ?, 
                telegram_photo_url = ?,
                telegram_auth_date = ?
            WHERE id = ?
        ');
        
        return $stmt->execute([
            $telegramData['id'],
            $telegramData['username'] ?? null,
            $telegramData['first_name'] ?? null,
            $telegramData['last_name'] ?? null,
            $telegramData['photo_url'] ?? null,
            time(),
            $userId
        ]);
    }
    
    // Update existing user's Telegram information
    public function updateUserTelegramInfo($userId, $telegramId) {
        $userInfo = $this->getUserInfo($telegramId);
        
        if ($userInfo) {
            $stmt = $this->db->prepare('UPDATE users SET 
                telegram_username = ?, 
                telegram_first_name = ?, 
                telegram_last_name = ?, 
                telegram_photo_url = ?, 
                telegram_auth_date = ? 
                WHERE id = ?');
            
            return $stmt->execute([
                $userInfo['username'] ?? null,
                $userInfo['first_name'] ?? null,
                $userInfo['last_name'] ?? null,
                $userInfo['photo_url'] ?? null,
                time(),
                $userId
            ]);
        }
        
        return false;
    }

    // Get bot information
    public function getBotInfo() {
        return $this->makeRequest('getMe', []);
    }

    // Make API request to Telegram
    private function makeRequest($method, $data) {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }
}

// Initialize Telegram Bot
function getTelegramBot() {
    $token = '8158833495:AAHzeYw3BEHXhZLDmYLrGYbh51s-LAoF7QA';
    $db = get_db();
    return new TelegramBot($token, $db);
}
?>
