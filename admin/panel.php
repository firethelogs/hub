<?php
// admin/panel.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';
?>
<?php
// admin/panel.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();

// Get some stats
$total_users = $db->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
$total_items = $db->query('SELECT COUNT(*) FROM items')->fetchColumn();
$total_purchases = $db->query('SELECT COUNT(*) FROM purchases')->fetchColumn();
$total_revenue = $db->query('SELECT SUM(i.price) FROM purchases p JOIN items i ON p.item_id = i.id')->fetchColumn() ?? 0;
?>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1>⚡ Admin Control Panel</h1>
        <p>Manage your platform efficiently</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?= $total_users ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <h3><?= $total_items ?></h3>
                <p>Store Items</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
                <h3><?= $total_purchases ?></h3>
                <p>Total Purchases</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <h3>$<?= number_format($total_revenue, 2) ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="/admin/users.php" class="action-card">
                <div class="action-icon">👥</div>
                <h3>User Management</h3>
                <p>View, manage, and ban users</p>
            </a>
            <a href="/admin/credits.php" class="action-card">
                <div class="action-icon">�</div>
                <h3>Credit Management</h3>
                <p>Send credits to user wallets</p>
            </a>
            <a href="/admin/store.php" class="action-card">
                <div class="action-icon">🛍️</div>
                <h3>Store Management</h3>
                <p>Add, edit, and manage store items</p>
            </a>
            <a href="/admin/analytics.php" class="action-card">
                <div class="action-icon">📊</div>
                <h3>Analytics</h3>
                <p>View sales and user statistics</p>
            </a>
        </div>
    </div>
</div>

<style>
.admin-dashboard {
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
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.admin-header p {
    color: #888;
    font-size: 1.1rem;
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
    background: rgba(99, 102, 241, 0.2);
    border-radius: 12px;
    padding: 0.8rem;
    min-width: 60px;
    text-align: center;
}

.stat-info h3 {
    font-size: 1.8rem;
    color: #fff;
    margin: 0;
    font-weight: 700;
}

.stat-info p {
    color: #888;
    margin: 0;
    font-size: 0.9rem;
}

.admin-actions h2 {
    color: #fff;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.action-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    text-align: center;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
    border-color: rgba(99, 102, 241, 0.5);
}

.action-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.action-card h3 {
    color: #fff;
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
}

.action-card p {
    color: #888;
    margin: 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
