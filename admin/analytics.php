<?php
// admin/analytics.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();

// Get analytics data
$total_users = $db->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
$total_items = $db->query('SELECT COUNT(*) FROM items')->fetchColumn();
$total_purchases = $db->query('SELECT COUNT(*) FROM purchases')->fetchColumn();
$total_revenue = $db->query('SELECT SUM(i.price) FROM purchases p JOIN items i ON p.item_id = i.id')->fetchColumn() ?? 0;

// Top selling items
$top_items = $db->query('
    SELECT i.title, i.price, i.purchases_count, (i.price * i.purchases_count) as revenue
    FROM items i 
    WHERE i.purchases_count > 0 
    ORDER BY i.purchases_count DESC 
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

// Recent purchases
$recent_purchases = $db->query('
    SELECT u.username, i.title, i.price, p.purchased_at
    FROM purchases p 
    JOIN users u ON p.user_id = u.id 
    JOIN items i ON p.item_id = i.id 
    ORDER BY p.purchased_at DESC 
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

// User activity
$active_users = $db->query('
    SELECT u.username, COUNT(p.id) as purchases, SUM(i.price) as total_spent
    FROM users u 
    LEFT JOIN purchases p ON u.id = p.user_id 
    LEFT JOIN items i ON p.item_id = i.id 
    WHERE u.is_admin = 0 
    GROUP BY u.id 
    ORDER BY purchases DESC 
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="analytics-dashboard">
    <div class="admin-header">
        <h1>📊 Analytics Dashboard</h1>
        <p>Track your platform's performance</p>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-info">
                <h3><?= $total_users ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">📦</div>
            <div class="metric-info">
                <h3><?= $total_items ?></h3>
                <p>Store Items</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">🛒</div>
            <div class="metric-info">
                <h3><?= $total_purchases ?></h3>
                <p>Total Sales</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-info">
                <h3>$<?= number_format($total_revenue, 2) ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- Analytics Sections -->
    <div class="analytics-grid">
        <!-- Top Selling Items -->
        <div class="analytics-section">
            <h2>🔥 Top Selling Items</h2>
            <?php if (count($top_items) > 0): ?>
            <div class="top-items">
                <?php foreach ($top_items as $item): ?>
                <div class="top-item">
                    <div class="item-info">
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <p>$<?= number_format($item['price'], 2) ?> each</p>
                    </div>
                    <div class="item-stats">
                        <span class="sales-count"><?= $item['purchases_count'] ?> sold</span>
                        <span class="revenue">$<?= number_format($item['revenue'], 2) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="no-data">No sales data available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Purchases -->
        <div class="analytics-section">
            <h2>🛒 Recent Purchases</h2>
            <?php if (count($recent_purchases) > 0): ?>
            <div class="recent-purchases">
                <?php foreach ($recent_purchases as $purchase): ?>
                <div class="purchase-item">
                    <div class="purchase-info">
                        <h4><?= htmlspecialchars($purchase['username']) ?></h4>
                        <p><?= htmlspecialchars($purchase['title']) ?></p>
                    </div>
                    <div class="purchase-details">
                        <span class="price">$<?= number_format($purchase['price'], 2) ?></span>
                        <span class="date"><?= date('M j, H:i', strtotime($purchase['purchased_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="no-data">No recent purchases.</p>
            <?php endif; ?>
        </div>

        <!-- Top Users -->
        <div class="analytics-section">
            <h2>⭐ Top Users</h2>
            <?php if (count($active_users) > 0): ?>
            <div class="top-users">
                <?php foreach ($active_users as $user): ?>
                <div class="user-item">
                    <div class="user-info">
                        <h4><?= htmlspecialchars($user['username']) ?></h4>
                        <p><?= $user['purchases'] ?> purchases</p>
                    </div>
                    <div class="user-stats">
                        <span class="total-spent">$<?= number_format($user['total_spent'], 2) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="no-data">No user activity data available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.analytics-dashboard {
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
    background: linear-gradient(135deg, #f59e0b, #d97706);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.metric-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
}

.metric-icon {
    font-size: 2rem;
    background: rgba(249, 158, 11, 0.2);
    border-radius: 12px;
    padding: 0.8rem;
    min-width: 60px;
    text-align: center;
}

.metric-info h3 {
    font-size: 1.8rem;
    color: #fff;
    margin: 0;
    font-weight: 700;
}

.metric-info p {
    color: #888;
    margin: 0;
    font-size: 0.9rem;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.analytics-section {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
}

.analytics-section h2 {
    color: #fff;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
}

.top-item, .purchase-item, .user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.top-item h4, .purchase-item h4, .user-item h4 {
    color: #fff;
    margin: 0;
    font-size: 1rem;
}

.top-item p, .purchase-item p, .user-item p {
    color: #888;
    margin: 0;
    font-size: 0.85rem;
}

.sales-count {
    background: rgba(99, 102, 241, 0.2);
    color: #6366f1;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    margin-right: 0.5rem;
}

.revenue, .price, .total-spent {
    color: #10b981;
    font-weight: 600;
}

.date {
    color: #888;
    font-size: 0.8rem;
}

.no-data {
    color: #888;
    text-align: center;
    padding: 2rem;
    font-style: italic;
}

@media (max-width: 768px) {
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}
</style>
