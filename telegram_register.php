<?php
// telegram_register.php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/telegram.php';

if (!isset($_SESSION['temp_telegram_id'])) {
    header('Location: /telegram_login.php');
    exit;
}

$error = '';
$telegram = getTelegramBot();
$telegramId = $_SESSION['temp_telegram_id'];

// Try to get Telegram user info
$telegramUserInfo = $telegram->getUserInfo($telegramId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($username && $email && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $db = get_db();
            
            // Check if username or email already exists
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Create new user account
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Get profile photo URL if available
                $photoUrl = null;
                if ($telegramUserInfo && isset($telegramUserInfo['photo_url'])) {
                    $photoUrl = $telegramUserInfo['photo_url'];
                }
                
                $stmt = $db->prepare('INSERT INTO users (username, email, password, telegram_id, telegram_username, telegram_first_name, telegram_last_name, telegram_photo_url, telegram_auth_date, created_at, last_seen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
                
                $telegramUsername = $telegramUserInfo['username'] ?? null;
                $telegramFirstName = $telegramUserInfo['first_name'] ?? null;
                $telegramLastName = $telegramUserInfo['last_name'] ?? null;
                
                if ($stmt->execute([$username, $email, $hashedPassword, $telegramId, $telegramUsername, $telegramFirstName, $telegramLastName, $photoUrl, time()])) {
                    $userId = $db->lastInsertId();
                    
                    // Create wallet for the user
                    $db->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 0)')->execute([$userId]);
                    
                    // Login the user
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['is_admin'] = 0;
                    
                    // Send welcome message
                    $telegram->sendWelcomeMessage($telegramId, $username);
                    
                    // Update online status
                    $telegram->updateOnlineStatus($userId, true);
                    
                    unset($_SESSION['temp_telegram_id']);
                    header('Location: /dashboard.php');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h2>🚀 Complete Registration</h2>
    <p>Complete your account setup to start using JaxxyCC Store with Telegram.</p>
    
    <?php if ($telegramUserInfo): ?>
        <div class="telegram-info">
            <h3>📱 Telegram Account</h3>
            <div class="telegram-profile">
                <?php if (isset($telegramUserInfo['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($telegramUserInfo['photo_url']) ?>" alt="Profile" class="telegram-avatar">
                <?php else: ?>
                    <div class="telegram-avatar-placeholder">👤</div>
                <?php endif; ?>
                <div class="telegram-details">
                    <p><strong>Name:</strong> <?= htmlspecialchars(($telegramUserInfo['first_name'] ?? '') . ' ' . ($telegramUserInfo['last_name'] ?? '')) ?></p>
                    <?php if (isset($telegramUserInfo['username'])): ?>
                        <p><strong>Username:</strong> @<?= htmlspecialchars($telegramUserInfo['username']) ?></p>
                    <?php endif; ?>
                    <p><strong>ID:</strong> <?= htmlspecialchars($telegramId) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" 
                   value="<?= htmlspecialchars($_POST['username'] ?? ($telegramUserInfo['username'] ?? '')) ?>" 
                   placeholder="Choose a unique username" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                   placeholder="Enter your email address" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a secure password" required>
        </div>
        
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm your password" required>
        </div>
        
        <button type="submit" class="btn-primary">
            🎯 Complete Registration
        </button>
    </form>
    
    <div style="text-align: center; margin-top: 2rem;">
        <p>Already have an account? <a href="/login.php">Login here</a></p>
    </div>
</div>

<style>
.telegram-info {
    background: rgba(34, 139, 34, 0.1);
    border: 1px solid rgba(34, 139, 34, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.telegram-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.telegram-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.telegram-avatar-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.telegram-details {
    flex: 1;
}

.telegram-details p {
    margin: 0.25rem 0;
    color: #a0a0a0;
}

.telegram-info h3 {
    margin-top: 0;
    color: #22c55e;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
