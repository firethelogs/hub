<?php
// purchase.php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$user_id = $_SESSION['user_id'];

if (isset($_POST['buy'])) {
    $item_id = (int)$_POST['buy'];
    
    // Check if already purchased
    $stmt = $db->prepare('SELECT 1 FROM purchases WHERE user_id = ? AND item_id = ?');
    $stmt->execute([$user_id, $item_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'You have already purchased this item.';
        header('Location: /store.php');
        exit;
    }
    
    // Get item details
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        $_SESSION['error'] = 'Item not found.';
        header('Location: /store.php');
        exit;
    }
    
    // Check if item is still available (stock limit)
    if ($item['is_limited'] && $item['purchases_count'] >= $item['stock_limit']) {
        $_SESSION['error'] = 'This item is out of stock.';
        header('Location: /store.php');
        exit;
    }
    
    // Check balance
    $stmt = $db->prepare('SELECT balance FROM wallets WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $balance = $stmt->fetchColumn();
    if ($balance < $item['price']) {
        $_SESSION['error'] = 'Insufficient balance. Your balance: $' . number_format($balance, 2) . ', Required: $' . number_format($item['price'], 2);
        header('Location: /store.php');
        exit;
    }
    
    // Process purchase
    $db->beginTransaction();
    try {
        // Deduct from wallet
        $db->prepare('UPDATE wallets SET balance = balance - ? WHERE user_id = ?')->execute([$item['price'], $user_id]);
        
        // Add transaction
        $db->prepare('INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, ?)')->execute([$user_id, -$item['price'], 'purchase']);
        
        // Add purchase
        $db->prepare('INSERT INTO purchases (user_id, item_id) VALUES (?, ?)')->execute([$user_id, $item_id]);
        
        // Update item purchase count
        $db->prepare('UPDATE items SET purchases_count = purchases_count + 1 WHERE id = ?')->execute([$item_id]);
        
        $db->commit();
        $_SESSION['success'] = 'Purchase successful! You can now view the content.';
        header('Location: /store.php');
        exit;
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = 'Purchase failed. Please try again.';
        header('Location: /store.php');
        exit;
    }
}

if (isset($_POST['reveal'])) {
    $item_id = (int)$_POST['reveal'];
    // Check if purchased
    $stmt = $db->prepare('SELECT 1 FROM purchases WHERE user_id = ? AND item_id = ?');
    $stmt->execute([$user_id, $item_id]);
    if (!$stmt->fetch()) {
        header('Location: /store.php');
        exit;
    }
    // Get content
    $stmt = $db->prepare('SELECT content FROM items WHERE id = ?');
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    include __DIR__ . '/includes/header.php';
    echo '<div class="card" style="max-width: 800px;"><h2>🔓 Unlocked Content</h2>';
    echo '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 2rem; margin: 1.5rem 0;">';
    echo '<pre style="white-space: pre-wrap; color: #e8e8e8; font-family: Georgia, serif; line-height: 1.6;">'.htmlspecialchars($item['content']).'</pre>';
    echo '</div>';
    echo '<div style="text-align: center; margin-top: 2rem;">';
    echo '<a href="/dashboard.php" style="text-decoration: none; margin: 0 0.5rem;"><button style="width: auto; padding: 1rem 2rem;">💰 Back to Dashboard</button></a>';
    echo '<a href="/store.php" style="text-decoration: none; margin: 0 0.5rem;"><button style="width: auto; padding: 1rem 2rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">🛍️ Browse More Items</button></a>';
    echo '</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

header('Location: /store.php');
exit;
