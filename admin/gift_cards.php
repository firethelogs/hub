<?php
// admin/gift_cards.php - Admin interface for managing gift cards
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();
$message = '';
$messageType = '';

// Handle gift card creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_gift_card'])) {
    $amount = floatval($_POST['amount']);
    $quantity = intval($_POST['quantity']) ?: 1;
    
    if ($amount > 0 && $quantity > 0 && $quantity <= 100) {
        $created_cards = [];
        $failed_cards = 0;
        
        for ($i = 0; $i < $quantity; $i++) {
            $result = create_gift_card($amount, $_SESSION['user_id']);
            if ($result['success']) {
                $created_cards[] = $result['code'];
            } else {
                $failed_cards++;
            }
        }
        
        if (count($created_cards) > 0) {
            $message = 'Successfully created ' . count($created_cards) . ' gift card(s)';
            if ($failed_cards > 0) {
                $message .= ' (' . $failed_cards . ' failed)';
            }
            $messageType = 'success';
        } else {
            $message = 'Failed to create gift cards';
            $messageType = 'error';
        }
    } else {
        $message = 'Invalid amount or quantity (max 100 cards at once)';
        $messageType = 'error';
    }
}

// Handle gift card deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_card'])) {
    $card_id = intval($_POST['card_id']);
    $stmt = $db->prepare('UPDATE gift_cards SET is_active = 0 WHERE id = ?');
    if ($stmt->execute([$card_id])) {
        $message = 'Gift card deactivated successfully';
        $messageType = 'success';
    } else {
        $message = 'Failed to deactivate gift card';
        $messageType = 'error';
    }
}

// Handle gift card reactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivate_card'])) {
    $card_id = intval($_POST['card_id']);
    $stmt = $db->prepare('UPDATE gift_cards SET is_active = 1 WHERE id = ? AND redeemed_by IS NULL');
    if ($stmt->execute([$card_id])) {
        $message = 'Gift card reactivated successfully';
        $messageType = 'success';
    } else {
        $message = 'Failed to reactivate gift card';
        $messageType = 'error';
    }
}

// Get gift cards with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

$whereClause = '';
$params = [];

if ($search) {
    $whereClause = " WHERE (gc.code LIKE ? OR u1.username LIKE ? OR u2.username LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

if ($status !== 'all') {
    $statusClause = '';
    if ($status === 'active') {
        $statusClause = " gc.is_active = 1 AND gc.redeemed_by IS NULL";
    } elseif ($status === 'redeemed') {
        $statusClause = " gc.redeemed_by IS NOT NULL";
    } elseif ($status === 'inactive') {
        $statusClause = " gc.is_active = 0";
    }
    
    if ($statusClause) {
        $whereClause = $whereClause ? $whereClause . " AND " . $statusClause : " WHERE " . $statusClause;
    }
}

$stmt = $db->prepare("SELECT gc.*, u1.username as created_by_username, u2.username as redeemed_by_username 
                      FROM gift_cards gc 
                      LEFT JOIN users u1 ON gc.created_by = u1.id 
                      LEFT JOIN users u2 ON gc.redeemed_by = u2.id 
                      $whereClause 
                      ORDER BY gc.created_at DESC 
                      LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$gift_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM gift_cards gc 
                           LEFT JOIN users u1 ON gc.created_by = u1.id 
                           LEFT JOIN users u2 ON gc.redeemed_by = u2.id 
                           $whereClause");
$countStmt->execute($params);
$total_cards = $countStmt->fetchColumn();
$total_pages = ceil($total_cards / $limit);

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM gift_cards")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM gift_cards WHERE is_active = 1 AND redeemed_by IS NULL")->fetchColumn(),
    'redeemed' => $db->query("SELECT COUNT(*) FROM gift_cards WHERE redeemed_by IS NOT NULL")->fetchColumn(),
    'inactive' => $db->query("SELECT COUNT(*) FROM gift_cards WHERE is_active = 0")->fetchColumn(),
    'total_value' => $db->query("SELECT SUM(amount) FROM gift_cards WHERE is_active = 1 AND redeemed_by IS NULL")->fetchColumn() ?? 0,
    'redeemed_value' => $db->query("SELECT SUM(amount) FROM gift_cards WHERE redeemed_by IS NOT NULL")->fetchColumn() ?? 0
];
?>

<div class="gift-cards-admin">
    <div class="admin-header">
        <h1>🎁 Gift Cards Management</h1>
        <p>Create and manage gift cards for users</p>
    </div>

    <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Cards</div>
        </div>
        <div class="stat-card active">
            <div class="stat-value"><?= $stats['active'] ?></div>
            <div class="stat-label">Active Cards</div>
        </div>
        <div class="stat-card redeemed">
            <div class="stat-value"><?= $stats['redeemed'] ?></div>
            <div class="stat-label">Redeemed Cards</div>
        </div>
        <div class="stat-card inactive">
            <div class="stat-value"><?= $stats['inactive'] ?></div>
            <div class="stat-label">Inactive Cards</div>
        </div>
        <div class="stat-card value">
            <div class="stat-value">$<?= number_format($stats['total_value'], 2) ?></div>
            <div class="stat-label">Active Value</div>
        </div>
        <div class="stat-card redeemed-value">
            <div class="stat-value">$<?= number_format($stats['redeemed_value'], 2) ?></div>
            <div class="stat-label">Redeemed Value</div>
        </div>
    </div>

    <!-- Create Gift Card Form -->
    <div class="create-card-section">
        <h2>🎯 Create Gift Cards</h2>
        <form method="post" class="create-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Amount ($)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="1000" required placeholder="25.00">
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" max="100" value="1" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="create_gift_card" class="btn-create">
                        🎁 Create Gift Card(s)
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Search and Filter -->
    <div class="search-section">
        <h3>🔍 Search & Filter</h3>
        <form method="get" class="search-form">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search by code or username..." class="search-input">
            <select name="status" class="status-filter">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="redeemed" <?= $status === 'redeemed' ? 'selected' : '' ?>>Redeemed</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit" class="search-btn">🔍 Search</button>
            <?php if ($search || $status !== 'all'): ?>
                <a href="?" class="clear-btn">✕ Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Gift Cards List -->
    <div class="cards-list">
        <h3>📋 Gift Cards (<?= $total_cards ?> total)</h3>
        
        <?php if (empty($gift_cards)): ?>
            <div class="no-cards">
                <p>No gift cards found.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($gift_cards as $card): ?>
                    <div class="gift-card-item <?= $card['redeemed_by'] ? 'redeemed' : ($card['is_active'] ? 'active' : 'inactive') ?>">
                        <div class="card-header">
                            <div class="card-code"><?= htmlspecialchars($card['code']) ?></div>
                            <div class="card-amount">$<?= number_format($card['amount'], 2) ?></div>
                        </div>
                        
                        <div class="card-details">
                            <div class="detail-row">
                                <span class="label">Created:</span>
                                <span class="value"><?= date('M j, Y g:i A', strtotime($card['created_at'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Created by:</span>
                                <span class="value"><?= htmlspecialchars($card['created_by_username']) ?></span>
                            </div>
                            
                            <?php if ($card['redeemed_by']): ?>
                                <div class="detail-row">
                                    <span class="label">Redeemed by:</span>
                                    <span class="value"><?= htmlspecialchars($card['redeemed_by_username']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Redeemed:</span>
                                    <span class="value"><?= date('M j, Y g:i A', strtotime($card['redeemed_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-status">
                            <?php if ($card['redeemed_by']): ?>
                                <span class="status-badge redeemed">✅ Redeemed</span>
                            <?php elseif ($card['is_active']): ?>
                                <span class="status-badge active">🟢 Active</span>
                            <?php else: ?>
                                <span class="status-badge inactive">🔴 Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$card['redeemed_by']): ?>
                            <div class="card-actions">
                                <?php if ($card['is_active']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                        <button type="submit" name="deactivate_card" class="btn-deactivate"
                                                onclick="return confirm('Deactivate this gift card?')">
                                            🚫 Deactivate
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                        <button type="submit" name="reactivate_card" class="btn-reactivate">
                                            ✅ Reactivate
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" 
                   class="page-link <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.gift-cards-admin {
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.2);
}

.stat-card.active { border-color: rgba(16, 185, 129, 0.5); }
.stat-card.redeemed { border-color: rgba(59, 130, 246, 0.5); }
.stat-card.inactive { border-color: rgba(239, 68, 68, 0.5); }
.stat-card.value { border-color: rgba(245, 158, 11, 0.5); }
.stat-card.redeemed-value { border-color: rgba(139, 92, 246, 0.5); }

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #fff;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #888;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.create-card-section {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.create-card-section h2 {
    color: #f59e0b;
    margin-top: 0;
    margin-bottom: 1.5rem;
}

.create-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    color: #e8e8e8;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.form-group input {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #e8e8e8;
    font-size: 1rem;
}

.form-group input:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.btn-create {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
}

.search-section {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.search-section h3 {
    color: #60a5fa;
    margin-top: 0;
    margin-bottom: 1rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #e8e8e8;
    min-width: 200px;
}

.status-filter {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #e8e8e8;
}

.search-btn, .clear-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.search-btn {
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    color: white;
}

.clear-btn {
    background: rgba(255, 255, 255, 0.1);
    color: #e8e8e8;
}

.cards-list h3 {
    color: #e8e8e8;
    margin-bottom: 1.5rem;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.gift-card-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.gift-card-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.gift-card-item.active {
    border-color: rgba(16, 185, 129, 0.3);
}

.gift-card-item.redeemed {
    border-color: rgba(59, 130, 246, 0.3);
    opacity: 0.8;
}

.gift-card-item.inactive {
    border-color: rgba(239, 68, 68, 0.3);
    opacity: 0.6;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card-code {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    font-weight: bold;
    color: #60a5fa;
    background: rgba(96, 165, 250, 0.1);
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    word-break: break-all;
}

.card-amount {
    font-size: 1.2rem;
    font-weight: bold;
    color: #10b981;
}

.card-details {
    margin-bottom: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.3rem 0;
}

.detail-row .label {
    color: #888;
    font-size: 0.9rem;
}

.detail-row .value {
    color: #e8e8e8;
    font-size: 0.9rem;
}

.card-status {
    margin-bottom: 1rem;
    text-align: center;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-badge.redeemed {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.card-actions {
    text-align: center;
}

.btn-deactivate, .btn-reactivate {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-deactivate {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-reactivate {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-deactivate:hover, .btn-reactivate:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    color: #e8e8e8;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.page-link:hover, .page-link.active {
    background: #60a5fa;
    color: white;
}

.no-cards {
    text-align: center;
    padding: 3rem;
    color: #888;
}

/* Responsive design */
@media (max-width: 768px) {
    .create-form .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input {
        min-width: auto;
    }
    
    .cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
