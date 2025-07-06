<?php
// telegram_login.php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/telegram.php';

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'start';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegram = getTelegramBot();
    
    if (isset($_POST['request_otp'])) {
        $telegramId = trim($_POST['telegram_id']);
        
        if ($telegramId) {
            // Send OTP to Telegram
            $result = $telegram->sendOTP($telegramId);
            
            if ($result && $result['ok']) {
                $_SESSION['temp_telegram_id'] = $telegramId;
                header('Location: /telegram_login.php?step=verify');
                exit;
            } else {
                $error = 'Failed to send OTP. Please check your Telegram ID and try again.';
            }
        } else {
            $error = 'Please enter your Telegram ID.';
        }
    }
    
    if (isset($_POST['verify_otp'])) {
        $otpCode = trim($_POST['otp_code']);
        $telegramId = $_SESSION['temp_telegram_id'] ?? '';
        
        if ($otpCode && $telegramId) {
            // Debug OTP verification
            $debugInfo = $telegram->debugOTP($telegramId, $otpCode);
            
            if ($telegram->verifyOTP($telegramId, $otpCode)) {
                // Check if user exists with this Telegram ID
                $user = $telegram->getUserByTelegramId($telegramId);
                
                if ($user) {
                    // Update existing user's Telegram information
                    $telegram->updateUserTelegramInfo($user['id'], $telegramId);
                    
                    // Get updated user info for display name
                    $updatedUser = $telegram->getUserByTelegramId($telegramId);
                    $displayName = get_display_name($updatedUser);
                    
                    // Login existing user
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['display_name'] = $displayName;
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    // Update online status
                    $telegram->updateOnlineStatus($user['id'], true);
                    
                    unset($_SESSION['temp_telegram_id']);
                    header('Location: /dashboard.php');
                    exit;
                } else {
                    // Create new user automatically
                    try {
                        // Get user info from Telegram
                        $telegramUserInfo = $telegram->getUserInfo($telegramId);
                        
                        $db = get_db();
                        $db->beginTransaction();
                        
                        // Generate username from Telegram info
                        $username = $telegramUserInfo['username'] ?? 'user_' . $telegramId;
                        $firstName = $telegramUserInfo['first_name'] ?? 'User';
                        $lastName = $telegramUserInfo['last_name'] ?? '';
                        $photoUrl = $telegramUserInfo['photo_url'] ?? null;
                        
                        // Check if username already exists, if so, add number
                        $originalUsername = $username;
                        $counter = 1;
                        while (true) {
                            $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
                            $stmt->execute([$username]);
                            if (!$stmt->fetch()) {
                                break;
                            }
                            $username = $originalUsername . '_' . $counter;
                            $counter++;
                        }
                        
                        // Create user account
                        $stmt = $db->prepare('INSERT INTO users (username, email, password, telegram_id, telegram_username, telegram_first_name, telegram_last_name, telegram_photo_url, telegram_auth_date, is_online, last_seen, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime("now"), datetime("now"))');
                        
                        $result = $stmt->execute([
                            $username,
                            $telegramId . '@telegram.local', // Dummy email
                            password_hash(uniqid(), PASSWORD_DEFAULT), // Dummy password
                            $telegramId,
                            $telegramUserInfo['username'] ?? null,
                            $firstName,
                            $lastName,
                            $photoUrl,
                            time()
                        ]);
                        
                        if ($result) {
                            $userId = $db->lastInsertId();
                            
                            // Create wallet for the user
                            $db->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 0)')->execute([$userId]);
                            
                            $db->commit();
                            
                            // Login the user
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['username'] = $username;
                            $_SESSION['display_name'] = $telegramUserInfo['username'] ? '@' . $telegramUserInfo['username'] : $firstName;
                            $_SESSION['is_admin'] = 0;
                            
                            // Send welcome message
                            $displayName = $telegramUserInfo['username'] ? '@' . $telegramUserInfo['username'] : $firstName;
                            $telegram->sendWelcomeMessage($telegramId, $displayName);
                            
                            unset($_SESSION['temp_telegram_id']);
                            header('Location: /dashboard.php');
                            exit;
                        } else {
                            $db->rollback();
                            $error = 'Failed to create account. Please try again.';
                        }
                    } catch (Exception $e) {
                        if ($db->inTransaction()) {
                            $db->rollback();
                        }
                        $error = 'Error creating account: ' . $e->getMessage();
                    }
                }
            } else {
                $error = 'Invalid or expired OTP. Please try again.';
                // Add debug info in development
                if ($debugInfo) {
                    $error .= ' (Debug: Found OTP, Used: ' . ($debugInfo['used'] ? 'Yes' : 'No') . ', Expires: ' . $debugInfo['expires_at'] . ', Current: ' . $debugInfo['current_time'] . ')';
                } else {
                    $error .= ' (Debug: No OTP found for this code)';
                }
            }
        } else {
            $error = 'Please enter the OTP code.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <?php if ($step === 'start'): ?>
        <h2>🔐 Telegram Login</h2>
        <p>Login securely using your Telegram account with OTP verification.</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Telegram ID or Username</label>
                <input type="text" name="telegram_id" placeholder="Enter your Telegram ID (e.g., @username or 123456789)" required>
                <small>💡 To find your Telegram ID, message @userinfobot on Telegram</small>
            </div>
            
            <button type="submit" name="request_otp" class="btn-primary">
                📱 Send OTP to Telegram
            </button>
        </form>
    
    <?php elseif ($step === 'verify'): ?>
        <h2>🔑 Enter OTP</h2>
        <p>We've sent a 6-digit code to your Telegram. Please enter it below:</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>OTP Code</label>
                <input type="text" name="otp_code" placeholder="000000" maxlength="6" required 
                       style="text-align: center; font-size: 1.8rem; letter-spacing: 0.3rem; font-family: 'Courier New', monospace; padding: 1rem;">
                <div class="otp-counter" id="otp-counter">0 / 6 digits</div>
            </div>
            
            <button type="submit" name="verify_otp" class="btn-primary" id="verify-btn" disabled>
                ✅ Verify & Login
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 1rem;">
            <a href="/telegram_login.php">← Back to enter Telegram ID</a>
        </div>
        
        <div class="info" style="margin-top: 2rem;">
            <p><strong>💡 Tips:</strong></p>
            <ul style="text-align: left;">
                <li>The OTP is valid for 5 minutes</li>
                <li>Check your Telegram messages</li>
                <li>Make sure you can receive messages from bots</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
.info {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 1rem;
    color: #60a5fa;
}

.info ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.info li {
    margin: 0.25rem 0;
}

input[name="otp_code"] {
    text-align: center;
    font-size: 1.8rem;
    letter-spacing: 0.3rem;
    font-family: 'Courier New', monospace;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #e8e8e8;
    transition: all 0.3s ease;
}

input[name="otp_code"]:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    outline: none;
}

input[name="otp_code"]:valid {
    border-color: #10b981;
}

.otp-counter {
    text-align: center;
    margin-top: 0.5rem;
    color: #888;
    font-size: 0.9rem;
}
</style>

<script>
// Auto-format OTP input
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('input[name="otp_code"]');
    const otpCounter = document.getElementById('otp-counter');
    const verifyBtn = document.getElementById('verify-btn');
    
    if (otpInput) {
        // Update counter and button state
        function updateOTPState() {
            const length = otpInput.value.length;
            if (otpCounter) {
                otpCounter.textContent = `${length} / 6 digits`;
                otpCounter.style.color = length === 6 ? '#10b981' : '#888';
            }
            
            if (verifyBtn) {
                verifyBtn.disabled = length !== 6;
                verifyBtn.style.opacity = length === 6 ? '1' : '0.5';
            }
        }
        
        otpInput.addEventListener('input', function(e) {
            // Only allow numbers
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            
            // Limit to 6 digits
            if (e.target.value.length > 6) {
                e.target.value = e.target.value.substring(0, 6);
            }
            
            updateOTPState();
        });
        
        // Focus the input
        otpInput.focus();
        
        // Initial state update
        updateOTPState();
        
        // Prevent form submission on Enter key if not 6 digits
        otpInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.value.length !== 6) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
