<?php
// admin/store.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/credit_cards.php';
require_once __DIR__ . '/../includes/bin_lookup.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();

// Add item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['price'], $_POST['content'])) {
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    $content = trim($_POST['content']);
    $is_limited = isset($_POST['is_limited']) ? 1 : 0;
    $stock_limit = $is_limited ? (int)$_POST['stock_limit'] : -1;
    $is_credit_card = isset($_POST['is_credit_card']) ? 1 : 0;
    $credit_card_type = $is_credit_card ? trim($_POST['credit_card_type']) : null;
    $credit_card_details = $is_credit_card ? trim($_POST['credit_card_details']) : null;
    
    // Credit card data fields
    $card_number = $is_credit_card ? trim($_POST['card_number']) : null;
    $card_expiry = $is_credit_card ? trim($_POST['card_expiry']) : null;
    $card_cvv = $is_credit_card ? trim($_POST['card_cvv']) : null;
    $card_holder_name = $is_credit_card ? trim($_POST['card_holder_name']) : null;
    $card_bank = $is_credit_card ? trim($_POST['card_bank']) : null;
    $card_country = $is_credit_card ? trim($_POST['card_country']) : null;
    $card_level = $is_credit_card ? trim($_POST['card_level']) : null;
    
    if ($title && $price > 0 && $content) {
        $stmt = $db->prepare('INSERT INTO items (title, price, content, is_limited, stock_limit, purchases_count, is_credit_card, credit_card_type, credit_card_details, card_number, card_expiry, card_cvv, card_holder_name, card_bank, card_country, card_level) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $price, $content, $is_limited, $stock_limit, $is_credit_card, $credit_card_type, $credit_card_details, $card_number, $card_expiry, $card_cvv, $card_holder_name, $card_bank, $card_country, $card_level]);
        $msg = 'Item added successfully!';
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Edit item
if (isset($_POST['edit_id'], $_POST['edit_title'], $_POST['edit_price'], $_POST['edit_content'])) {
    $id = (int)$_POST['edit_id'];
    $title = trim($_POST['edit_title']);
    $price = floatval($_POST['edit_price']);
    $content = trim($_POST['edit_content']);
    $is_limited = isset($_POST['edit_is_limited']) ? 1 : 0;
    $stock_limit = $is_limited ? (int)$_POST['edit_stock_limit'] : -1;
    $is_credit_card = isset($_POST['edit_is_credit_card']) ? 1 : 0;
    $credit_card_type = $is_credit_card ? trim($_POST['edit_credit_card_type']) : null;
    $credit_card_details = $is_credit_card ? trim($_POST['edit_credit_card_details']) : null;
    
    // Credit card data fields
    $card_number = $is_credit_card ? trim($_POST['edit_card_number']) : null;
    $card_expiry = $is_credit_card ? trim($_POST['edit_card_expiry']) : null;
    $card_cvv = $is_credit_card ? trim($_POST['edit_card_cvv']) : null;
    $card_holder_name = $is_credit_card ? trim($_POST['edit_card_holder_name']) : null;
    $card_bank = $is_credit_card ? trim($_POST['edit_card_bank']) : null;
    $card_country = $is_credit_card ? trim($_POST['edit_card_country']) : null;
    $card_level = $is_credit_card ? trim($_POST['edit_card_level']) : null;
    
    if ($title && $price > 0 && $content) {
        $stmt = $db->prepare('UPDATE items SET title = ?, price = ?, content = ?, is_limited = ?, stock_limit = ?, is_credit_card = ?, credit_card_type = ?, credit_card_details = ?, card_number = ?, card_expiry = ?, card_cvv = ?, card_holder_name = ?, card_bank = ?, card_country = ?, card_level = ? WHERE id = ?');
        $stmt->execute([$title, $price, $content, $is_limited, $stock_limit, $is_credit_card, $credit_card_type, $credit_card_details, $card_number, $card_expiry, $card_cvv, $card_holder_name, $card_bank, $card_country, $card_level, $id]);
        $msg = 'Item updated successfully!';
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Delete item
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $db->prepare('DELETE FROM purchases WHERE item_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
    $msg = 'Item deleted successfully!';
}

$items = $db->query('SELECT * FROM items ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="store-admin">
    <div class="admin-header">
        <h1>🛍️ JaxxyCC Store Management</h1>
        <p>Add, edit, and manage your store items</p>
    </div>

    <!-- Add New Item Form -->
    <div class="add-item-section">
        <h2>➕ Add New Item</h2>
        
        <?php if (!empty($msg)): ?>
            <div class="success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" class="add-item-form">
            <div class="form-grid">
                <div class="form-group">
                    <label>Item Title</label>
                    <input type="text" name="title" placeholder="Enter item title" required>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" min="0.01" step="0.01" placeholder="0.00" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Content (Locked)</label>
                <textarea name="content" placeholder="Enter the content that will be unlocked after purchase" required rows="4"></textarea>
            </div>
            
            <div class="stock-controls">
                <div class="checkbox-group">
                    <input type="checkbox" id="is_limited" name="is_limited" onchange="toggleStockLimit()">
                    <label for="is_limited">Limited Stock Item</label>
                </div>
                <div class="stock-input" id="stock_input" style="display: none;">
                    <label>Stock Limit</label>
                    <input type="number" name="stock_limit" min="1" placeholder="How many can be purchased?" value="1">
                    <small>Leave unchecked for unlimited purchases</small>
                </div>
            </div>
            
            <div class="credit-card-controls">
                <div class="checkbox-group">
                    <input type="checkbox" id="is_credit_card" name="is_credit_card" onchange="toggleCreditCard()">
                    <label for="is_credit_card">💳 This is a Credit Card</label>
                </div>
                <div class="credit-card-input" id="credit_card_input" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Card Type</label>
                            <select name="credit_card_type">
                                <option value="">Select Card Type</option>
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="amex">American Express</option>
                                <option value="discover">Discover</option>
                                <option value="jcb">JCB</option>
                                <option value="diners">Diners Club</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Card Details (Public info)</label>
                            <input type="text" name="credit_card_details" placeholder="e.g., Premium, USA, Gold Level">
                        </div>
                    </div>
                    
                    <h4>📋 Credit Card Data (Hidden from customers until purchase)</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Card Number</label>
                            <div class="input-group">
                                <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                <button type="button" class="btn-lookup" onclick="lookupBIN('card_number')">🔍 Auto-Fill</button>
                            </div>
                            <small class="lookup-status" id="lookup_status"></small>
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" name="card_cvv" placeholder="123" maxlength="4">
                        </div>
                        <div class="form-group">
                            <label>Cardholder Name</label>
                            <input type="text" name="card_holder_name" placeholder="John Doe">
                        </div>
                        <div class="form-group">
                            <label>Bank</label>
                            <input type="text" name="card_bank" placeholder="Chase Bank">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="card_country" placeholder="USA">
                        </div>
                        <div class="form-group">
                            <label>Card Level</label>
                            <select name="card_level">
                                <option value="">Select Level</option>
                                <option value="standard">Standard</option>
                                <option value="gold">Gold</option>
                                <option value="platinum">Platinum</option>
                                <option value="black">Black</option>
                                <option value="business">Business</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">🚀 Add Item</button>
        </form>
    </div>

    <!-- Existing Items -->
    <?php if (count($items) > 0): ?>
    <div class="items-section">
        <h2>📦 Manage Existing Items</h2>
        <div class="items-grid">
            <?php foreach ($items as $item): ?>
            <div class="item-card <?= $item['is_credit_card'] ? 'credit-card-item' : '' ?>">
                <div class="item-header">
                    <div class="item-title-section">
                        <?php if ($item['is_credit_card']): ?>
                            <div class="credit-card-badge" style="background-color: <?= getCreditCardColor($item['credit_card_type']) ?>;">
                                <span class="card-logo"><?= getCreditCardLogo($item['credit_card_type']) ?></span>
                                <span class="card-name"><?= getCreditCardName($item['credit_card_type']) ?></span>
                            </div>
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <?php if ($item['is_credit_card'] && $item['credit_card_details']): ?>
                            <p class="card-details"><?= htmlspecialchars($item['credit_card_details']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="item-price">$<?= number_format($item['price'], 2) ?></div>
                </div>
                
                <div class="item-stats">
                    <div class="stat">
                        <span class="stat-label">Purchases:</span>
                        <span class="stat-value"><?= $item['purchases_count'] ?></span>
                    </div>
                    <?php if ($item['is_limited']): ?>
                    <div class="stat">
                        <span class="stat-label">Stock:</span>
                        <span class="stat-value <?= ($item['stock_limit'] - $item['purchases_count']) <= 0 ? 'sold-out' : '' ?>">
                            <?= max(0, $item['stock_limit'] - $item['purchases_count']) ?> / <?= $item['stock_limit'] ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="stat">
                        <span class="stat-label">Stock:</span>
                        <span class="stat-value unlimited">Unlimited</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="item-content">
                    <p><?= substr(htmlspecialchars($item['content']), 0, 100) ?>...</p>
                </div>
                
                <div class="item-actions">
                    <button onclick="editItem(<?= $item['id'] ?>)" class="btn-edit">✏️ Edit</button>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                        <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-delete">🗑️ Delete</button>
                    </form>
                </div>
                
                <!-- Edit Form (Hidden) -->
                <div id="edit-form-<?= $item['id'] ?>" class="edit-form" style="display: none;">
                    <form method="post">
                        <input type="hidden" name="edit_id" value="<?= $item['id'] ?>">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="edit_title" value="<?= htmlspecialchars($item['title']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Price ($)</label>
                            <input type="number" name="edit_price" value="<?= $item['price'] ?>" min="0.01" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="edit_content" required rows="3"><?= htmlspecialchars($item['content']) ?></textarea>
                        </div>
                        <div class="stock-controls">
                            <div class="checkbox-group">
                                <input type="checkbox" id="edit_is_limited_<?= $item['id'] ?>" name="edit_is_limited" <?= $item['is_limited'] ? 'checked' : '' ?> onchange="toggleEditStockLimit(<?= $item['id'] ?>)">
                                <label for="edit_is_limited_<?= $item['id'] ?>">Limited Stock</label>
                            </div>
                            <div class="stock-input" id="edit_stock_input_<?= $item['id'] ?>" style="display: <?= $item['is_limited'] ? 'block' : 'none' ?>;">
                                <label>Stock Limit</label>
                                <input type="number" name="edit_stock_limit" min="1" value="<?= $item['stock_limit'] ?>">
                            </div>
                        </div>
                        
                        <div class="credit-card-controls">
                            <div class="checkbox-group">
                                <input type="checkbox" id="edit_is_credit_card_<?= $item['id'] ?>" name="edit_is_credit_card" <?= $item['is_credit_card'] ? 'checked' : '' ?> onchange="toggleEditCreditCard(<?= $item['id'] ?>)">
                                <label for="edit_is_credit_card_<?= $item['id'] ?>">💳 Credit Card</label>
                            </div>
                            <div class="credit-card-input" id="edit_credit_card_input_<?= $item['id'] ?>" style="display: <?= $item['is_credit_card'] ? 'block' : 'none' ?>;">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Card Type</label>
                                        <select name="edit_credit_card_type">
                                            <option value="">Select Card Type</option>
                                            <option value="visa" <?= $item['credit_card_type'] == 'visa' ? 'selected' : '' ?>>Visa</option>
                                            <option value="mastercard" <?= $item['credit_card_type'] == 'mastercard' ? 'selected' : '' ?>>Mastercard</option>
                                            <option value="amex" <?= $item['credit_card_type'] == 'amex' ? 'selected' : '' ?>>American Express</option>
                                            <option value="discover" <?= $item['credit_card_type'] == 'discover' ? 'selected' : '' ?>>Discover</option>
                                            <option value="jcb" <?= $item['credit_card_type'] == 'jcb' ? 'selected' : '' ?>>JCB</option>
                                            <option value="diners" <?= $item['credit_card_type'] == 'diners' ? 'selected' : '' ?>>Diners Club</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Card Details (Public)</label>
                                        <input type="text" name="edit_credit_card_details" value="<?= htmlspecialchars($item['credit_card_details'] ?? '') ?>" placeholder="e.g., Premium, USA, Gold Level">
                                    </div>
                                </div>
                                
                                <h4>📋 Credit Card Data</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Card Number</label>
                                        <div class="input-group">
                                            <input type="text" name="edit_card_number" id="edit_card_number_<?= $item['id'] ?>" value="<?= htmlspecialchars($item['card_number'] ?? '') ?>" placeholder="1234 5678 9012 3456">
                                            <button type="button" class="btn-lookup" onclick="lookupBINEdit(<?= $item['id'] ?>)">🔍 Auto-Fill</button>
                                        </div>
                                        <small class="lookup-status" id="edit_lookup_status_<?= $item['id'] ?>"></small>
                                    </div>
                                    <div class="form-group">
                                        <label>Expiry Date</label>
                                        <input type="text" name="edit_card_expiry" value="<?= htmlspecialchars($item['card_expiry'] ?? '') ?>" placeholder="MM/YY">
                                    </div>
                                    <div class="form-group">
                                        <label>CVV</label>
                                        <input type="text" name="edit_card_cvv" value="<?= htmlspecialchars($item['card_cvv'] ?? '') ?>" placeholder="123">
                                    </div>
                                    <div class="form-group">
                                        <label>Cardholder Name</label>
                                        <input type="text" name="edit_card_holder_name" value="<?= htmlspecialchars($item['card_holder_name'] ?? '') ?>" placeholder="John Doe">
                                    </div>
                                    <div class="form-group">
                                        <label>Bank</label>
                                        <input type="text" name="edit_card_bank" value="<?= htmlspecialchars($item['card_bank'] ?? '') ?>" placeholder="Chase Bank">
                                    </div>
                                    <div class="form-group">
                                        <label>Country</label>
                                        <input type="text" name="edit_card_country" value="<?= htmlspecialchars($item['card_country'] ?? '') ?>" placeholder="USA">
                                    </div>
                                    <div class="form-group">
                                        <label>Card Level</label>
                                        <select name="edit_card_level">
                                            <option value="">Select Level</option>
                                            <option value="standard" <?= $item['card_level'] == 'standard' ? 'selected' : '' ?>>Standard</option>
                                            <option value="gold" <?= $item['card_level'] == 'gold' ? 'selected' : '' ?>>Gold</option>
                                            <option value="platinum" <?= $item['card_level'] == 'platinum' ? 'selected' : '' ?>>Platinum</option>
                                            <option value="black" <?= $item['card_level'] == 'black' ? 'selected' : '' ?>>Black</option>
                                            <option value="business" <?= $item['card_level'] == 'business' ? 'selected' : '' ?>>Business</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">💾 Update</button>
                            <button type="button" onclick="cancelEdit(<?= $item['id'] ?>)" class="btn-cancel">❌ Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>No items yet</h3>
        <p>Add your first item to get started!</p>
    </div>
    <?php endif; ?>
</div>

<style>
.store-admin {
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
    background: linear-gradient(135deg, #10b981, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.add-item-section {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 3rem;
}

.add-item-section h2 {
    color: #fff;
    margin-bottom: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 200px;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    color: #ccc;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.stock-controls {
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 12px;
    padding: 1rem;
    margin: 1rem 0;
}

.credit-card-controls {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 12px;
    padding: 1rem;
    margin: 1rem 0;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.stock-input {
    margin-top: 1rem;
}

.stock-input small {
    color: #888;
    font-size: 0.8rem;
}

.btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

.items-section h2 {
    color: #fff;
    margin-bottom: 1.5rem;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.item-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    transition: transform 0.3s ease;
}

.item-card:hover {
    transform: translateY(-2px);
}

.item-card.credit-card-item {
    border: 2px solid rgba(16, 185, 129, 0.3);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(30, 30, 30, 0.8));
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
    padding: 0.3rem 0.8rem;
    border-radius: 8px;
    font-weight: 600;
}

.item-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.stat-label {
    font-size: 0.8rem;
    color: #888;
}

.stat-value {
    font-weight: 600;
    color: #10b981;
}

.stat-value.sold-out {
    color: #ef4444;
}

.stat-value.unlimited {
    color: #6366f1;
}

.item-content {
    margin-bottom: 1rem;
}

.item-content p {
    color: #ccc;
    margin: 0;
    font-size: 0.9rem;
}

.item-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-edit {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.8rem;
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.8rem;
}

.edit-form {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.form-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-cancel {
    background: rgba(255, 255, 255, 0.1);
    color: #ccc;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #888;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* Input Group Styles */
.input-group {
    display: flex;
    gap: 8px;
    align-items: center;
    width: 100%;
}

.input-group input {
    flex: 1;
    min-width: 200px;
    width: auto;
}

.btn-lookup {
    padding: 8px 12px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    white-space: nowrap;
    transition: all 0.2s;
    min-width: 90px;
}

.btn-lookup:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
}

.btn-lookup:disabled {
    background: #6b7280;
    cursor: not-allowed;
    transform: none;
}

/* Lookup Status Styles */
.lookup-status {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
}

.lookup-status.loading {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

.lookup-status.success {
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.lookup-status.error {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

/* Make sure form inputs have adequate width */
.form-group input[type="text"] {
    min-width: 200px;
    width: 100%;
}

.form-grid .form-group {
    min-width: 0;
}

/* Auto-filled visual feedback */
.auto-filled {
    background-color: rgba(16, 185, 129, 0.1) !important;
    border-color: #10b981 !important;
    transition: all 0.3s ease;
}

.auto-filled:focus {
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .items-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function toggleStockLimit() {
    const checkbox = document.getElementById('is_limited');
    const stockInput = document.getElementById('stock_input');
    stockInput.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleCreditCard() {
    const checkbox = document.getElementById('is_credit_card');
    const creditCardInput = document.getElementById('credit_card_input');
    creditCardInput.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleEditStockLimit(itemId) {
    const checkbox = document.getElementById('edit_is_limited_' + itemId);
    const stockInput = document.getElementById('edit_stock_input_' + itemId);
    stockInput.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleEditCreditCard(itemId) {
    const checkbox = document.getElementById('edit_is_credit_card_' + itemId);
    const creditCardInput = document.getElementById('edit_credit_card_input_' + itemId);
    creditCardInput.style.display = checkbox.checked ? 'block' : 'none';
}

function editItem(itemId) {
    const editForm = document.getElementById('edit-form-' + itemId);
    editForm.style.display = editForm.style.display === 'none' ? 'block' : 'none';
}

function cancelEdit(itemId) {
    const editForm = document.getElementById('edit-form-' + itemId);
    editForm.style.display = 'none';
}

// Format credit card numbers
function formatCardNumber(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/\D/g, '');
    
    // Add spaces every 4 digits
    value = value.replace(/(.{4})/g, '$1 ');
    
    // Remove trailing space
    value = value.trim();
    
    // Limit to 19 characters (16 digits + 3 spaces)
    if (value.length > 19) {
        value = value.substring(0, 19);
    }
    
    input.value = value;
}

// Format expiry date
function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    
    input.value = value;
}

// BIN Lookup functionality
async function lookupBIN(inputId) {
    const cardNumberInput = document.getElementById(inputId);
    const cardNumber = cardNumberInput.value.replace(/\s/g, '');
    const statusElement = document.getElementById('lookup_status');
    const lookupButton = document.querySelector('.btn-lookup');
    
    // Validate card number length
    if (cardNumber.length < 8) {
        statusElement.textContent = 'Please enter at least 8 digits';
        statusElement.className = 'lookup-status error';
        return;
    }
    
    // Show loading state
    statusElement.textContent = '🔍 Looking up card details...';
    statusElement.className = 'lookup-status loading';
    lookupButton.disabled = true;
    lookupButton.textContent = '🔄 Loading...';
    
    try {
        const formData = new FormData();
        formData.append('card_number', cardNumber);
        
        const response = await fetch('/ajax/bin_lookup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Auto-fill the form with BIN data
            autoFillCardData(data);
            statusElement.textContent = '✅ Card details auto-filled successfully!';
            statusElement.className = 'lookup-status success';
        } else {
            statusElement.textContent = '❌ ' + (data.error || 'BIN lookup failed');
            statusElement.className = 'lookup-status error';
        }
    } catch (error) {
        console.error('BIN lookup error:', error);
        statusElement.textContent = '❌ Network error during lookup';
        statusElement.className = 'lookup-status error';
    } finally {
        // Reset button state
        lookupButton.disabled = false;
        lookupButton.textContent = '🔍 Auto-Fill';
    }
}

function autoFillCardData(binData) {
    // Auto-fill card type
    const cardTypeSelect = document.querySelector('select[name="credit_card_type"]');
    if (cardTypeSelect && binData.card_type) {
        cardTypeSelect.value = binData.card_type;
        
        // Add visual feedback
        cardTypeSelect.classList.add('auto-filled');
        setTimeout(() => cardTypeSelect.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill card details
    const cardDetailsInput = document.querySelector('input[name="credit_card_details"]');
    if (cardDetailsInput && binData.details) {
        cardDetailsInput.value = binData.details;
        cardDetailsInput.classList.add('auto-filled');
        setTimeout(() => cardDetailsInput.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill bank name
    const bankInput = document.querySelector('input[name="card_bank"]');
    if (bankInput && binData.bank && binData.bank !== 'Unknown Bank') {
        bankInput.value = binData.bank;
        bankInput.classList.add('auto-filled');
        setTimeout(() => bankInput.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill country
    const countryInput = document.querySelector('input[name="card_country"]');
    if (countryInput && binData.country && binData.country !== 'Unknown') {
        countryInput.value = binData.country;
        countryInput.classList.add('auto-filled');
        setTimeout(() => countryInput.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill card level based on type
    const levelSelect = document.querySelector('select[name="card_level"]');
    if (levelSelect && binData.type) {
        const typeMapping = {
            'credit': 'standard',
            'debit': 'standard',
            'prepaid': 'standard'
        };
        const level = typeMapping[binData.type.toLowerCase()] || 'standard';
        levelSelect.value = level;
        levelSelect.classList.add('auto-filled');
        setTimeout(() => levelSelect.classList.remove('auto-filled'), 3000);
    }
}

// BIN Lookup for edit forms
async function lookupBINEdit(itemId) {
    const cardNumberInput = document.getElementById(`edit_card_number_${itemId}`);
    const cardNumber = cardNumberInput.value.replace(/\s/g, '');
    const statusElement = document.getElementById(`edit_lookup_status_${itemId}`);
    const lookupButton = event.target;
    
    // Validate card number length
    if (cardNumber.length < 8) {
        statusElement.textContent = 'Please enter at least 8 digits';
        statusElement.className = 'lookup-status error';
        return;
    }
    
    // Show loading state
    statusElement.textContent = '🔍 Looking up card details...';
    statusElement.className = 'lookup-status loading';
    lookupButton.disabled = true;
    lookupButton.textContent = '🔄 Loading...';
    
    try {
        const formData = new FormData();
        formData.append('card_number', cardNumber);
        
        const response = await fetch('/ajax/bin_lookup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Auto-fill the edit form with BIN data
            autoFillEditCardData(itemId, data);
            statusElement.textContent = '✅ Card details auto-filled successfully!';
            statusElement.className = 'lookup-status success';
        } else {
            statusElement.textContent = '❌ ' + (data.error || 'BIN lookup failed');
            statusElement.className = 'lookup-status error';
        }
    } catch (error) {
        console.error('BIN lookup error:', error);
        statusElement.textContent = '❌ Network error during lookup';
        statusElement.className = 'lookup-status error';
    } finally {
        // Reset button state
        lookupButton.disabled = false;
        lookupButton.textContent = '🔍 Auto-Fill';
    }
}

function autoFillEditCardData(itemId, binData) {
    // Auto-fill card type
    const cardTypeSelect = document.querySelector(`select[name="edit_credit_card_type"]`);
    if (cardTypeSelect && binData.card_type) {
        cardTypeSelect.value = binData.card_type;
        cardTypeSelect.classList.add('auto-filled');
        setTimeout(() => cardTypeSelect.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill card details
    const cardDetailsInput = document.querySelector(`input[name="edit_credit_card_details"]`);
    if (cardDetailsInput && binData.details) {
        cardDetailsInput.value = binData.details;
        cardDetailsInput.classList.add('auto-filled');
        setTimeout(() => cardDetailsInput.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill bank name
    const bankInput = document.querySelector(`input[name="edit_card_bank"]`);
    if (bankInput && binData.bank && binData.bank !== 'Unknown Bank') {
        bankInput.value = binData.bank;
        bankInput.classList.add('auto-filled');
        setTimeout(() => bankInput.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill country
    const countryInput = document.querySelector(`input[name="edit_card_country"]`);
    if (countryInput && binData.country && binData.country !== 'Unknown') {
        countryInput.value = binData.country;
        countryInput.classList.add('auto-filled');
        setTimeout(() => countryInput.classList.remove('auto-filled'), 3000);
    }
    
    // Auto-fill card level based on type
    const levelSelect = document.querySelector(`select[name="edit_card_level"]`);
    if (levelSelect && binData.type) {
        const typeMapping = {
            'credit': 'standard',
            'debit': 'standard',
            'prepaid': 'standard'
        };
        const level = typeMapping[binData.type.toLowerCase()] || 'standard';
        levelSelect.value = level;
        levelSelect.classList.add('auto-filled');
        setTimeout(() => levelSelect.classList.remove('auto-filled'), 3000);
    }
}

// Add Enter key support for BIN lookup
document.addEventListener('DOMContentLoaded', function() {
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupBIN('card_number');
            }
        });
    }
    
    // Format card number inputs
    document.querySelectorAll('input[name="card_number"], input[name="edit_card_number"]').forEach(function(input) {
        input.addEventListener('input', function() {
            formatCardNumber(this);
        });
    });
    
    // Format expiry inputs
    document.querySelectorAll('input[name="card_expiry"], input[name="edit_card_expiry"]').forEach(function(input) {
        input.addEventListener('input', function() {
            formatExpiry(this);
        });
    });
    
    // Limit CVV to 4 digits
    document.querySelectorAll('input[name="card_cvv"], input[name="edit_card_cvv"]').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
