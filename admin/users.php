<?php
// admin/users.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();

if (isset($_POST['ban'])) {
    $uid = (int)$_POST['ban'];
    $db->prepare('UPDATE users SET is_banned = 1 WHERE id = ?')->execute([$uid]);
    $success_msg = 'User banned successfully!';
}

if (isset($_POST['unban'])) {
    $uid = (int)$_POST['unban'];
    $db->prepare('UPDATE users SET is_banned = 0 WHERE id = ?')->execute([$uid]);
    $success_msg = 'User unbanned successfully!';
}

$users = $db->query('SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON u.id = w.user_id ORDER BY u.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="users-admin">
    <div class="admin-header">
        <h1>👥 User Management</h1>
        <p>Manage registered users and their access</p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <div class="users-grid">
        <?php foreach ($users as $user): ?>
        <div class="user-card">
            <div class="user-header">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                </div>
                <div class="user-info">
                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="user-status">
                    <?php if ($user['is_admin']): ?>
                        <span class="status-badge admin">Admin</span>
                    <?php elseif ($user['is_banned']): ?>
                        <span class="status-badge banned">Banned</span>
                    <?php else: ?>
                        <span class="status-badge active">Active</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="user-details">
                <div class="detail-item">
                    <span class="label">User ID:</span>
                    <span class="value">#<?= $user['id'] ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Wallet Balance:</span>
                    <span class="value balance">$<?= number_format($user['balance'] ?? 0, 2) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Joined:</span>
                    <span class="value"><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
            
            <?php if (!$user['is_admin']): ?>
            <div class="user-actions">
                <?php if ($user['is_banned']): ?>
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="unban" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn-unban">✅ Unban User</button>
                    </form>
                <?php else: ?>
                    <form method="post" style="margin: 0;" onsubmit="return confirm('Are you sure you want to ban this user?')">
                        <input type="hidden" name="ban" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn-ban">🚫 Ban User</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.users-admin {
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
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.user-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    transition: transform 0.3s ease;
}

.user-card:hover {
    transform: translateY(-2px);
}

.user-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.user-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.user-info {
    flex: 1;
}

.user-info h3 {
    color: #fff;
    margin: 0;
    font-size: 1.1rem;
}

.user-info p {
    color: #888;
    margin: 0;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.admin {
    background: rgba(139, 92, 246, 0.2);
    color: #8b5cf6;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-badge.banned {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.user-details {
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
}

.label {
    color: #888;
    font-size: 0.9rem;
}

.value {
    color: #fff;
    font-weight: 500;
}

.value.balance {
    color: #10b981;
    font-weight: 600;
}

.user-actions {
    text-align: center;
}

.btn-ban {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-ban:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
}

.btn-unban {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-unban:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

@media (max-width: 768px) {
    .users-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
