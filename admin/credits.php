<?php
// admin/credits.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$searchParams = [];

if ($searchTerm) {
    // Check if search term is numeric for exact Telegram ID match
    if (is_numeric($searchTerm)) {
        $searchQuery = " AND (u.username LIKE ? OR u.telegram_username LIKE ? OR u.telegram_first_name LIKE ? OR u.telegram_id = ?)";
        $searchParams = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", intval($searchTerm)];
    } else {
        $searchQuery = " AND (u.username LIKE ? OR u.telegram_username LIKE ? OR u.telegram_first_name LIKE ?)";
        $searchParams = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
    }
}

$users = $db->prepare("SELECT u.id, u.username, u.telegram_username, u.telegram_first_name, u.telegram_id FROM users u WHERE u.is_banned = 0 AND u.is_admin = 0$searchQuery ORDER BY u.telegram_username ASC, u.telegram_first_name ASC, u.username ASC");
$users->execute($searchParams);
$users = $users->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['amount'])) {
    $uid = (int)$_POST['user_id'];
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?')->execute([$amount, $uid]);
            $db->prepare('INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, ?)')->execute([$uid, $amount, 'admin_credit']);
            
            // Get new balance and user's telegram info
            $stmt = $db->prepare('SELECT w.balance, u.telegram_id FROM wallets w JOIN users u ON w.user_id = u.id WHERE u.id = ?');
            $stmt->execute([$uid]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $db->commit();
            
            // Send Telegram notification
            if ($userInfo && $userInfo['telegram_id']) {
                require_once __DIR__ . '/../includes/telegram.php';
                $telegram = getTelegramBot();
                $telegram->sendBalanceNotification($userInfo['telegram_id'], $amount, $userInfo['balance']);
            }
            
            $msg = 'Credited!';
        } catch (Exception $e) {
            $db->rollback();
            $msg = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<div class="card">
    <h2>💰 Credit Management</h2>
    <p style="color: #888; margin-bottom: 2rem;">Send credits to user wallets</p>
    
    <?php if (!empty($msg)): ?>
        <div class="success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Search Users -->
    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin: 0 0 1rem 0; color: #60a5fa;">🔍 Search Users</h3>
        <form method="get" style="display: flex; gap: 1rem; align-items: center;">
            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                   placeholder="Search by username, Telegram ID, or name..." 
                   style="flex: 1; padding: 0.75rem; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; background: rgba(255,255,255,0.05); color: #e8e8e8;">
            <button type="submit" style="padding: 0.75rem 1.5rem; background: #60a5fa; color: white; border: none; border-radius: 8px; cursor: pointer;">
                🔍 Search
            </button>
            <?php if ($searchTerm): ?>
                <a href="?search=" style="padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); color: #e8e8e8; text-decoration: none; border-radius: 8px;">
                    ✕ Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Credit Form -->
    <form method="post">
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Select User:</label>
            <select name="user_id" required style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; background: rgba(255,255,255,0.05); color: #e8e8e8;">
                <option value="">Select User</option>
                <?php foreach ($users as $u): ?>
                    <?php 
                    $displayName = get_display_name($u);
                    $userInfo = '';
                    if ($u['telegram_id']) {
                        $userInfo = " (ID: {$u['telegram_id']})";
                    }
                    if ($displayName !== $u['username']) {
                        $userInfo .= " [{$u['username']}]";
                    }
                    ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($displayName . $userInfo) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($users)): ?>
                <p style="color: #888; margin-top: 0.5rem;">
                    <?= $searchTerm ? 'No users found matching your search.' : 'No users found.' ?>
                </p>
            <?php else: ?>
                <p style="color: #888; margin-top: 0.5rem; font-size: 0.9rem;">
                    Found <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?><?= $searchTerm ? ' matching "' . htmlspecialchars($searchTerm) . '"' : '' ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Amount:</label>
            <input type="number" name="amount" min="0.01" step="0.01" placeholder="Amount to send (e.g., 10.50)" required 
                   style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; background: rgba(255,255,255,0.05); color: #e8e8e8;">
        </div>
        
        <button type="submit" style="width: 100%; padding: 1rem; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer;">
            💸 Send Credits
        </button>
    </form>

    <!-- User Statistics -->
    <?php if (!$searchTerm): ?>
    <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
        <h3 style="margin: 0 0 1rem 0; color: #a8a8f0;">📊 Statistics</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <div style="text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #10b981;"><?= count($users) ?></div>
                <div style="color: #888; font-size: 0.9rem;">Total Users</div>
            </div>
            <?php
            $telegramUsers = array_filter($users, function($u) { return !empty($u['telegram_id']); });
            ?>
            <div style="text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #60a5fa;"><?= count($telegramUsers) ?></div>
                <div style="color: #888; font-size: 0.9rem;">With Telegram</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
