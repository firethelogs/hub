<?php
// admin/store.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
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
    
    if ($title && $price > 0 && $content) {
        $stmt = $db->prepare('INSERT INTO items (title, price, content, is_limited, stock_limit, purchases_count) VALUES (?, ?, ?, ?, ?, 0)');
        $stmt->execute([$title, $price, $content, $is_limited, $stock_limit]);
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
    
    if ($title && $price > 0 && $content) {
        $stmt = $db->prepare('UPDATE items SET title = ?, price = ?, content = ?, is_limited = ?, stock_limit = ? WHERE id = ?');
        $stmt->execute([$title, $price, $content, $is_limited, $stock_limit, $id]);
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
        <h1>🛍️ Store Management</h1>
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
            
            <button type="submit" class="btn-primary">🚀 Add Item</button>
        </form>
    </div>

    <!-- Existing Items -->
    <?php if (count($items) > 0): ?>
    <div class="items-section">
        <h2>📦 Manage Existing Items</h2>
        <div class="items-grid">
            <?php foreach ($items as $item): ?>
            <div class="item-card">
                <div class="item-header">
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
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

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

function toggleEditStockLimit(itemId) {
    const checkbox = document.getElementById('edit_is_limited_' + itemId);
    const stockInput = document.getElementById('edit_stock_input_' + itemId);
    stockInput.style.display = checkbox.checked ? 'block' : 'none';
}

function editItem(itemId) {
    const editForm = document.getElementById('edit-form-' + itemId);
    editForm.style.display = editForm.style.display === 'none' ? 'block' : 'none';
}

function cancelEdit(itemId) {
    const editForm = document.getElementById('edit-form-' + itemId);
    editForm.style.display = 'none';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
