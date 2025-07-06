<?php
// admin/credits.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
include __DIR__ . '/../includes/header.php';

$db = get_db();
$users = $db->query('SELECT id, username FROM users WHERE is_banned = 0 AND is_admin = 0')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['amount'])) {
    $uid = (int)$_POST['user_id'];
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $db->beginTransaction();
        $db->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?')->execute([$amount, $uid]);
        $db->prepare('INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, ?)')->execute([$uid, $amount, 'admin_credit']);
        $db->commit();
        $msg = 'Credited!';
    }
}
?>
<div class="card">
    <h2>💰 Credit Management</h2>
    <p style="color: #888; margin-bottom: 2rem;">Send credits to user wallets</p>
    
    <?php if (!empty($msg)): ?>
        <div class="success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <select name="user_id" required>
            <option value="">Select User</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="amount" min="0.01" step="0.01" placeholder="Amount to send" required>
        <button type="submit">💸 Send Credits</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
