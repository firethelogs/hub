<?php
// purchase.php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/credit_cards.php';
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
        
        // Get updated balance
        $stmt = $db->prepare('SELECT balance FROM wallets WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $newBalance = $stmt->fetchColumn();
        
        // Send Telegram notification
        require_once __DIR__ . '/includes/telegram.php';
        $telegram = getTelegramBot();
        $stmt = $db->prepare('SELECT telegram_id FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $telegramId = $stmt->fetchColumn();
        
        if ($telegramId) {
            $telegram->sendPurchaseNotification($telegramId, $item['title'], $item['price']);
        }
        
        // Store purchase success data for thank you page
        $_SESSION['purchase_success'] = [
            'item' => $item,
            'balance' => $newBalance
        ];
        
        header('Location: /thank_you.php');
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
    // Get content and item details
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    include __DIR__ . '/includes/header.php';
    echo '<div class="card" style="max-width: 800px;"><h2>🔓 Unlocked Content</h2>';
    
    // If it's a credit card, show the card details
    if ($item['is_credit_card']) {
        echo '<div class="card-reveal-info">';
        echo '<h4>💳 ' . getCreditCardName($item['credit_card_type']) . ' Card Details</h4>';
        echo '<div class="card-data-row"><span class="card-data-label">Card Number:</span><span class="card-data-value">' . htmlspecialchars($item['card_number']) . '</span></div>';
        echo '<div class="card-data-row"><span class="card-data-label">Expiry Date:</span><span class="card-data-value">' . htmlspecialchars($item['card_expiry']) . '</span></div>';
        echo '<div class="card-data-row"><span class="card-data-label">CVV:</span><span class="card-data-value">' . htmlspecialchars($item['card_cvv']) . '</span></div>';
        echo '<div class="card-data-row"><span class="card-data-label">Cardholder Name:</span><span class="card-data-value">' . htmlspecialchars($item['card_holder_name']) . '</span></div>';
        if ($item['card_bank']) {
            echo '<div class="card-data-row"><span class="card-data-label">Bank:</span><span class="card-data-value">' . htmlspecialchars($item['card_bank']) . '</span></div>';
        }
        if ($item['card_country']) {
            echo '<div class="card-data-row"><span class="card-data-label">Country:</span><span class="card-data-value">' . htmlspecialchars($item['card_country']) . '</span></div>';
        }
        if ($item['card_level']) {
            echo '<div class="card-data-row"><span class="card-data-label">Level:</span><span class="card-data-value">' . strtoupper(htmlspecialchars($item['card_level'])) . '</span></div>';
        }
        echo '</div>';
    }
    
    // Show additional content if any
    if ($item['content']) {
        echo '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 2rem; margin: 1.5rem 0;">';
        echo '<pre style="white-space: pre-wrap; color: #e8e8e8; font-family: Georgia, serif; line-height: 1.6;">'.htmlspecialchars($item['content']).'</pre>';
        echo '</div>';
    }
    
    echo '<div style="text-align: center; margin-top: 2rem;">';
    echo '<a href="/dashboard.php" style="text-decoration: none; margin: 0 0.5rem;"><button style="width: auto; padding: 1rem 2rem;">💰 Back to Dashboard</button></a>';
    echo '<a href="/store.php" style="text-decoration: none; margin: 0 0.5rem;"><button style="width: auto; padding: 1rem 2rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">🛍️ Browse More Items</button></a>';
    echo '</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

header('Location: /store.php');
exit;
