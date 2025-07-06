<?php
// dashboard.php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
include __DIR__ . '/includes/header.php';

$db = get_db();
$user_id = $_SESSION['user_id'];
$user = get_user($user_id);

// Wallet balance
$stmt = $db->prepare('SELECT balance FROM wallets WHERE user_id = ?');
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();

// Transaction history
$transactions = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$transactions->execute([$user_id]);
$transactions = $transactions->fetchAll(PDO::FETCH_ASSOC);

// Purchases
$purchases = $db->prepare('SELECT p.*, i.title, i.price FROM purchases p JOIN items i ON p.item_id = i.id WHERE p.user_id = ? ORDER BY p.purchased_at DESC');
$purchases->execute([$user_id]);
$purchases = $purchases->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <h2>💰 Your Wallet</h2>
    <p><strong>Balance:</strong> <span class="balance">$<?= number_format($balance, 2) ?></span></p>
    <h3>📊 Recent Transactions</h3>
    <?php if (count($transactions) > 0): ?>
    <table>
        <tr><th>Date</th><th>Amount</th><th>Type</th></tr>
        <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
            <td style="color: <?= $t['amount'] > 0 ? '#10b981' : '#ef4444' ?>;">
                <?= ($t['amount'] > 0 ? '+' : '') . '$' . number_format($t['amount'], 2) ?>
            </td>
            <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($t['type']))) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="color: #888; text-align: center; padding: 2rem;">No transactions yet</p>
    <?php endif; ?>
</div>
<div class="card">
    <h2>🛍️ My Purchases</h2>
    <?php if (count($purchases) > 0): ?>
    <table>
        <tr><th>Title</th><th>Price</th><th>Action</th></tr>
        <?php foreach ($purchases as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td>$<?= number_format($p['price'], 2) ?></td>
            <td><form method="post" action="/purchase.php" style="margin:0;"><input type="hidden" name="reveal" value="<?= $p['item_id'] ?>"><button type="submit">🔓 View Content</button></form></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="color: #888; text-align: center; padding: 2rem;">No purchases yet. <a href="/store.php" style="color: #6366f1;">Visit the store</a> to buy some items!</p>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
