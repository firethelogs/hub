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

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$searchParams = [];

if ($searchTerm) {
    // Check if search term is numeric for exact Telegram ID match
    if (is_numeric($searchTerm)) {
        $searchQuery = " WHERE (u.username LIKE ? OR u.telegram_username LIKE ? OR u.telegram_first_name LIKE ? OR u.telegram_id = ? OR u.email LIKE ?)";
        $searchParams = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", intval($searchTerm), "%$searchTerm%"];
    } else {
        $searchQuery = " WHERE (u.username LIKE ? OR u.telegram_username LIKE ? OR u.telegram_first_name LIKE ? OR u.email LIKE ?)";
        $searchParams = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
    }
}

$stmt = $db->prepare("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON u.id = w.user_id$searchQuery ORDER BY u.created_at DESC");
$stmt->execute($searchParams);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="users-admin">
    <div class="admin-header">
        <h1>👥 JaxxyCC Store Users</h1>
        <p>Manage registered users and their access</p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <!-- Search Users -->
    <div class="search-section">
        <h3>🔍 Search Users</h3>
        <form method="get" class="search-form">
            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                   placeholder="Search by username, Telegram ID, name, or email..." 
                   class="search-input">
            <button type="submit" class="search-btn">
                🔍 Search
            </button>
            <?php if ($searchTerm): ?>
                <a href="?search=" class="clear-btn">
                    ✕ Clear
                </a>
            <?php endif; ?>
        </form>
        
        <div class="search-results">
            <?php if (empty($users)): ?>
                <p class="no-results">
                    <?= $searchTerm ? 'No users found matching your search.' : 'No users found.' ?>
                </p>
            <?php else: ?>
                <p class="results-count">
                    Found <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?><?= $searchTerm ? ' matching "' . htmlspecialchars($searchTerm) . '"' : '' ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="users-grid">
        <?php foreach ($users as $user): ?>
        <div class="user-card">
            <div class="user-header">
                <div class="user-avatar">
                    <?php 
                    $displayName = get_display_name($user);
                    echo strtoupper(substr($displayName, 0, 2));
                    ?>
                </div>
                <div class="user-info">
                    <h3>
                        <?php 
                        $displayName = get_display_name($user);
                        echo htmlspecialchars($displayName);
                        ?>
                        <?php if ($displayName !== $user['username']): ?>
                            <small style="color: #888; font-weight: normal;">(<?= htmlspecialchars($user['username']) ?>)</small>
                        <?php endif; ?>
                    </h3>
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
                
                <?php if ($user['telegram_id']): ?>
                <div class="detail-item">
                    <span class="label">Telegram ID:</span>
                    <span class="value"><?= htmlspecialchars($user['telegram_id']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($user['telegram_username']): ?>
                <div class="detail-item">
                    <span class="label">Telegram Username:</span>
                    <span class="value">@<?= htmlspecialchars($user['telegram_username']) ?></span>
                </div>
                <?php endif; ?>
                
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

.search-section {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.search-section h3 {
    margin: 0 0 1rem 0;
    color: #60a5fa;
    font-size: 1.2rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
}

.search-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #e8e8e8;
    font-size: 1rem;
    transition: all 0.3s ease;
    min-width: 0; /* Prevent flex item from overflowing */
}

.search-input:focus {
    outline: none;
    border-color: #60a5fa;
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
}

.search-input::placeholder {
    color: #888;
}

.search-input::selection {
    background: rgba(96, 165, 250, 0.3);
    color: #fff;
}

.search-btn {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.search-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(96, 165, 250, 0.4);
}

.clear-btn {
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    color: #e8e8e8;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-weight: 600;
}

.clear-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.search-results {
    margin-top: 1rem;
}

.no-results {
    color: #888;
    text-align: center;
    font-style: italic;
    margin: 0;
}

.results-count {
    color: #888;
    font-size: 0.9rem;
    margin: 0;
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
    transition: all 0.3s ease;
}

.user-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
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
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0; /* Prevent overflow */
}

.user-info h3 {
    color: #fff;
    margin: 0;
    font-size: 1.1rem;
    word-break: break-word;
}

.user-info p {
    color: #888;
    margin: 0;
    font-size: 0.9rem;
    word-break: break-word;
}

.user-status {
    flex-shrink: 0;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
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
    flex-shrink: 0;
}

.value {
    color: #fff;
    font-weight: 500;
    text-align: right;
    word-break: break-word;
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

/* Responsive improvements */
@media (max-width: 768px) {
    .users-grid {
        grid-template-columns: 1fr;
    }
    
    .search-form {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .search-input {
        width: 100%;
    }
    
    .search-btn,
    .clear-btn {
        width: 100%;
        text-align: center;
    }
    
    .user-header {
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .user-status {
        width: 100%;
    }
}

/* Fix text selection colors globally */
::selection {
    background: rgba(96, 165, 250, 0.3);
    color: #fff;
}

::-moz-selection {
    background: rgba(96, 165, 250, 0.3);
    color: #fff;
}

/* Ensure form inputs have proper selection colors */
input[type="text"]::-moz-selection,
input[type="search"]::-moz-selection {
    background: rgba(96, 165, 250, 0.3);
    color: #fff;
}
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
