<?php
// store.php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/credit_cards.php';
require_login();
include __DIR__ . '/includes/header.php';

$db = get_db();
$user_id = $_SESSION['user_id'];

// Get all items
$items = $db->query('SELECT * FROM items ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
// Get user's purchases
$purchased = $db->prepare('SELECT item_id FROM purchases WHERE user_id = ?');
$purchased->execute([$user_id]);
$purchased_ids = array_column($purchased->fetchAll(PDO::FETCH_ASSOC), 'item_id');

// Check for error/success messages
$error_msg = '';
$success_msg = '';
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<div class="card">
    <h2>🛍️ JaxxyCC Store</h2>
    <p>Purchase premium items to unlock exclusive content!</p>
    
    <?php if ($error_msg): ?>
        <div class="error"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>
    
    <?php if ($success_msg): ?>
        <div class="success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    
    <?php if (count($items) > 0): ?>
    <div class="store-items">
        <?php foreach ($items as $item): ?>
        <div class="store-item <?= $item['is_credit_card'] ? 'credit-card-item' : '' ?>">
            <div class="item-header">
                <div class="item-title-section">
                    <?php if ($item['is_credit_card']): ?>
                        <div class="credit-card-badge" style="background-color: <?= getCreditCardColor($item['credit_card_type']) ?>;">
                            <span class="card-logo"><?= getCreditCardLogo($item['credit_card_type']) ?></span>
                            <span class="card-name"><?= getCreditCardName($item['credit_card_type']) ?></span>
                        </div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <?php if ($item['is_credit_card']): ?>
                        <div class="card-details"><?= htmlspecialchars(getCardDetails($item)) ?></div>
                        
                        <div class="card-preview">
                            <div class="card-preview-header">
                                <div class="card-preview-number"><?= maskCardNumber($item['card_number']) ?></div>
                                <div class="card-logo"><?= getCreditCardLogo($item['credit_card_type']) ?></div>
                            </div>
                            <div class="card-preview-details">
                                <div class="card-preview-holder">
                                    <?= $item['card_holder_name'] ? htmlspecialchars($item['card_holder_name']) : 'CARDHOLDER NAME' ?>
                                </div>
                                <div>
                                    <div>EXP: <?= formatCardExpiry($item['card_expiry']) ?></div>
                                    <div>CVV: <?= maskCvv($item['card_cvv']) ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-hidden-info">
                            🔒 Full card details revealed after purchase
                        </div>
                    <?php elseif ($item['credit_card_details']): ?>
                        <p class="card-details"><?= htmlspecialchars($item['credit_card_details']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="item-price">$<?= number_format($item['price'], 2) ?></div>
            </div>
            
            <div class="item-meta">
                <span class="item-date">Added <?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                <?php if ($item['is_limited']): ?>
                    <span class="stock-info">
                        <?php 
                        $remaining = $item['stock_limit'] - $item['purchases_count'];
                        if ($remaining > 0): ?>
                            <span class="stock-available">📦 <?= $remaining ?> left</span>
                        <?php else: ?>
                            <span class="stock-out">❌ Sold Out</span>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <span class="stock-unlimited">♾️ Unlimited</span>
                <?php endif; ?>
            </div>
            
            <div class="item-actions">
                <?php if (in_array($item['id'], $purchased_ids)): ?>
                    <form method="post" action="/purchase.php" style="margin:0;">
                        <input type="hidden" name="reveal" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-owned">🔓 View Content</button>
                    </form>
                <?php else: ?>
                    <?php if ($item['is_limited'] && $item['purchases_count'] >= $item['stock_limit']): ?>
                        <button disabled class="btn-disabled">❌ Out of Stock</button>
                    <?php else: ?>
                        <form method="post" action="/purchase.php" style="margin:0;">
                            <input type="hidden" name="buy" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-buy">🛒 Buy Now</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-store">
        <div class="empty-icon">🛍️</div>
        <h3>Store is empty</h3>
        <p>No items available yet. Check back later!</p>
    </div>
    <?php endif; ?>
</div>

<style>
.store-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.store-item {
    background: rgba(40, 40, 40, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.store-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

.store-item.credit-card-item {
    border: 2px solid rgba(16, 185, 129, 0.3);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(40, 40, 40, 0.6));
}

.credit-card-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    color: white;
    font-size: 0.85rem;
    font-weight: 600;
}

.card-logo {
    font-size: 1.2rem;
}

.card-name {
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.item-title-section {
    flex: 1;
}

.card-details {
    color: #10b981;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    margin: 0.5rem 0;
    font-weight: 600;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.item-header h3 {
    color: #fff;
    margin: 0;
    font-size: 1.2rem;
}

.item-price {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
}

.item-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.item-date {
    color: #888;
}

.stock-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stock-available {
    color: #10b981;
    font-weight: 600;
}

.stock-out {
    color: #ef4444;
    font-weight: 600;
}

.stock-unlimited {
    color: #6366f1;
    font-weight: 600;
}

.item-actions {
    text-align: center;
}

.btn-buy {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 1rem;
}

.btn-buy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

.btn-owned {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 1rem;
}

.btn-owned:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

.btn-disabled {
    background: rgba(255, 255, 255, 0.1);
    color: #666;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: not-allowed;
    width: 100%;
    font-size: 1rem;
}

.empty-store {
    text-align: center;
    padding: 4rem 2rem;
    color: #888;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .store-items {
        grid-template-columns: 1fr;
    }
}
</style>
<?php include __DIR__ . '/includes/footer.php'; ?>
