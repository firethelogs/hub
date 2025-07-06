<?php
// admin/telegram.php - Telegram Bot Admin Panel
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/telegram.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();
$telegram = getTelegramBot();

// Get statistics
$totalUsers = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$telegramUsers = $db->query('SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL')->fetchColumn();
$onlineUsers = $db->query('SELECT COUNT(*) FROM users WHERE is_online = 1')->fetchColumn();
$totalOtps = $db->query('SELECT COUNT(*) FROM telegram_otps')->fetchColumn();
$usedOtps = $db->query('SELECT COUNT(*) FROM telegram_otps WHERE used = 1')->fetchColumn();

// Get bot info
$botInfo = $telegram->getBotInfo();
$botUsername = $botInfo['result']['username'] ?? 'N/A';
$botName = $botInfo['result']['first_name'] ?? 'N/A';

// Get recent Telegram users
$recentUsers = $db->query('
    SELECT u.*, 
           (SELECT COUNT(*) FROM telegram_otps WHERE telegram_id = u.telegram_id) as otp_count
    FROM users u 
    WHERE u.telegram_id IS NOT NULL 
    ORDER BY u.telegram_auth_date DESC 
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

// Handle send test message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $testChatId = trim($_POST['test_chat_id']);
    if ($testChatId) {
        $testMessage = "🧪 <b>Test Message from JaxxyCC Store</b>\n\n";
        $testMessage .= "This is a test message from the admin panel.\n";
        $testMessage .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $testMessage .= "✅ Bot is working correctly!";
        
        $result = $telegram->sendMessage($testChatId, $testMessage);
        $testResult = $result ? 'Success' : 'Failed';
    }
}
?>

<div class="telegram-admin">
    <div class="admin-header">
        <h1>📱 Telegram Bot Management</h1>
        <p>Monitor and manage Telegram bot integration</p>
    </div>

    <!-- Bot Status -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🤖</div>
            <div class="stat-details">
                <h3>Bot Status</h3>
                <p class="stat-value"><?= $botInfo ? '✅ Online' : '❌ Offline' ?></p>
                <p class="stat-label">@<?= $botUsername ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-details">
                <h3>Total Users</h3>
                <p class="stat-value"><?= $totalUsers ?></p>
                <p class="stat-label">Registered users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📱</div>
            <div class="stat-details">
                <h3>Telegram Users</h3>
                <p class="stat-value"><?= $telegramUsers ?></p>
                <p class="stat-label"><?= $totalUsers > 0 ? round(($telegramUsers / $totalUsers) * 100, 1) : 0 ?>% of total</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🟢</div>
            <div class="stat-details">
                <h3>Online Users</h3>
                <p class="stat-value"><?= $onlineUsers ?></p>
                <p class="stat-label">Currently online</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🔐</div>
            <div class="stat-details">
                <h3>OTP Codes</h3>
                <p class="stat-value"><?= $totalOtps ?></p>
                <p class="stat-label"><?= $usedOtps ?> used</p>
            </div>
        </div>
    </div>

    <!-- Test Message -->
    <div class="admin-section">
        <h2>🧪 Test Bot</h2>
        <p>Send a test message to verify bot functionality</p>
        
        <?php if (isset($testResult)): ?>
            <div class="<?= $testResult === 'Success' ? 'success' : 'error' ?>">
                Test message: <?= $testResult ?>
            </div>
        <?php endif; ?>
        
        <form method="post" style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
            <input type="text" name="test_chat_id" placeholder="Enter Telegram Chat ID" required style="flex: 1;">
            <button type="submit" name="send_test" style="width: auto; padding: 0.8rem 1.5rem;">Send Test</button>
        </form>
    </div>

    <!-- Recent Telegram Users -->
    <div class="admin-section">
        <h2>📊 Recent Telegram Users</h2>
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Telegram Info</th>
                        <th>Status</th>
                        <th>OTP Count</th>
                        <th>Connected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <?php if ($user['telegram_photo_url']): ?>
                                    <img src="<?= htmlspecialchars($user['telegram_photo_url']) ?>" alt="Photo" class="user-photo">
                                <?php else: ?>
                                    <div class="user-photo-placeholder"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                                <?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <small><?= htmlspecialchars($user['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($user['telegram_first_name'] . ' ' . $user['telegram_last_name']) ?></strong>
                                <?php if ($user['telegram_username']): ?>
                                    <small>@<?= htmlspecialchars($user['telegram_username']) ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?= $user['is_online'] ? 'online' : 'offline' ?>">
                                <?= $user['is_online'] ? '🟢 Online' : '🔴 Offline' ?>
                            </span>
                        </td>
                        <td><?= $user['otp_count'] ?></td>
                        <td><?= date('M j, Y', $user['telegram_auth_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bot Configuration -->
    <div class="admin-section">
        <h2>⚙️ Bot Configuration</h2>
        <div class="config-info">
            <div class="config-item">
                <strong>Bot Name:</strong> <?= htmlspecialchars($botName) ?>
            </div>
            <div class="config-item">
                <strong>Bot Username:</strong> @<?= htmlspecialchars($botUsername) ?>
            </div>
            <div class="config-item">
                <strong>Bot URL:</strong> <a href="https://t.me/<?= $botUsername ?>" target="_blank">https://t.me/<?= $botUsername ?></a>
            </div>
            <div class="config-item">
                <strong>Webhook URL:</strong> <code><?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/webhook.php</code>
            </div>
        </div>
    </div>
</div>

<style>
.telegram-admin {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.admin-header {
    text-align: center;
    margin-bottom: 3rem;
}

.admin-header h1 {
    font-size: 2.5rem;
    background: linear-gradient(135deg, #10b981, #06b6d4);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #10b981, #06b6d4);
    border-radius: 12px;
}

.stat-details h3 {
    margin: 0 0 0.5rem 0;
    color: #e8e8e8;
    font-size: 1rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #10b981;
    margin: 0;
}

.stat-label {
    font-size: 0.8rem;
    color: #888;
    margin: 0;
}

.admin-section {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.admin-section h2 {
    margin: 0 0 1rem 0;
    color: #e8e8e8;
}

.users-table {
    overflow-x: auto;
}

.users-table table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th,
.users-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.users-table th {
    background: rgba(99, 102, 241, 0.1);
    color: #e8e8e8;
    font-weight: 600;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.user-photo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-photo-placeholder {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.online {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-badge.offline {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.config-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.config-item {
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.config-item strong {
    color: #10b981;
}

.config-item a {
    color: #06b6d4;
    text-decoration: none;
}

.config-item a:hover {
    text-decoration: underline;
}

.config-item code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .config-info {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
