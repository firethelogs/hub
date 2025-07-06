<?php
// redeem_gift_card.php - User interface for redeeming gift cards
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$message = '';
$messageType = '';
$redeemed_amount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_code'])) {
    $code = trim(strtoupper($_POST['gift_card_code']));
    
    if ($code) {
        $result = redeem_gift_card($code, $_SESSION['user_id']);
        
        if ($result['success']) {
            $message = 'Gift card redeemed successfully! $' . number_format($result['amount'], 2) . ' has been added to your wallet.';
            $messageType = 'success';
            $redeemed_amount = $result['amount'];
            
            // Send Telegram notification if available
            if (!empty($_SESSION['telegram_id'])) {
                require_once __DIR__ . '/includes/telegram.php';
                try {
                    $telegram = getTelegramBot();
                    $telegram->sendMessage($_SESSION['telegram_id'], 
                        "🎁 Gift Card Redeemed!\n\n" .
                        "Amount: $" . number_format($result['amount'], 2) . "\n" .
                        "Code: " . $result['code'] . "\n\n" .
                        "Your new balance will be updated shortly."
                    );
                } catch (Exception $e) {
                    // Silent fail for telegram notifications
                }
            }
        } else {
            $message = $result['error'];
            $messageType = 'error';
        }
    } else {
        $message = 'Please enter a gift card code.';
        $messageType = 'error';
    }
}

// Get user's current balance
$db = get_db();
$stmt = $db->prepare('SELECT balance FROM wallets WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$current_balance = $stmt->fetchColumn() ?? 0;

// Get user's redemption history
$stmt = $db->prepare('SELECT gc.code, gc.amount, gcr.redeemed_at FROM gift_card_redemptions gcr JOIN gift_cards gc ON gcr.gift_card_id = gc.id WHERE gcr.user_id = ? ORDER BY gcr.redeemed_at DESC LIMIT 10');
$stmt->execute([$_SESSION['user_id']]);
$redemption_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<div class="redeem-gift-card">
    <div class="redeem-header">
        <h1>🎁 Redeem Gift Card</h1>
        <p>Enter your gift card code to add credits to your wallet</p>
    </div>

    <!-- Current Balance -->
    <div class="balance-display">
        <h2>💰 Current Balance</h2>
        <div class="balance-amount">$<?= number_format($current_balance, 2) ?></div>
    </div>

    <!-- Redemption Form -->
    <div class="redemption-form">
        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="redeem-form">
            <div class="form-group">
                <label for="gift_card_code">Gift Card Code</label>
                <input type="text" 
                       id="gift_card_code" 
                       name="gift_card_code" 
                       placeholder="JAXXYCC-XXXX-XXXX-XXXX"
                       required
                       maxlength="25"
                       style="text-transform: uppercase;"
                       autocomplete="off">
                <small>Enter the complete gift card code (e.g., JAXXYCC-AB12-CD34-EF56)</small>
            </div>
            
            <button type="submit" name="redeem_code" class="btn-redeem">
                🎯 Redeem Gift Card
            </button>
        </form>
    </div>

    <!-- Gift Card Format Info -->
    <div class="info-section">
        <h3>ℹ️ Gift Card Information</h3>
        <div class="info-content">
            <div class="info-item">
                <strong>Format:</strong> All gift cards start with "JAXXYCC-" followed by 12 characters in groups of 4
            </div>
            <div class="info-item">
                <strong>Example:</strong> JAXXYCC-AB12-CD34-EF56
            </div>
            <div class="info-item">
                <strong>Case:</strong> Codes are not case-sensitive
            </div>
            <div class="info-item">
                <strong>Usage:</strong> Each gift card can only be redeemed once
            </div>
        </div>
    </div>

    <!-- Redemption History -->
    <?php if (!empty($redemption_history)): ?>
        <div class="history-section">
            <h3>📋 Recent Redemptions</h3>
            <div class="history-list">
                <?php foreach ($redemption_history as $redemption): ?>
                    <div class="history-item">
                        <div class="history-code"><?= htmlspecialchars($redemption['code']) ?></div>
                        <div class="history-amount">+$<?= number_format($redemption['amount'], 2) ?></div>
                        <div class="history-date"><?= date('M j, Y g:i A', strtotime($redemption['redeemed_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Success Animation (shown when gift card is redeemed) -->
    <?php if ($messageType === 'success' && $redeemed_amount > 0): ?>
        <div class="success-animation">
            <div class="checkmark-circle">
                <div class="checkmark">
                    <div class="checkmark-stem"></div>
                    <div class="checkmark-kick"></div>
                </div>
            </div>
            <div class="success-text">
                <h3>🎉 Gift Card Redeemed!</h3>
                <p>+$<?= number_format($redeemed_amount, 2) ?> added to your wallet</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="/store.php" class="action-btn">
            🛍️ Visit Store
        </a>
        <a href="/dashboard.php" class="action-btn">
            📊 View Dashboard
        </a>
    </div>
</div>

<style>
.redeem-gift-card {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
}

.redeem-header {
    text-align: center;
    margin-bottom: 3rem;
}

.redeem-header h1 {
    font-size: 2.5rem;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.balance-display {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.balance-display h2 {
    color: #10b981;
    margin: 0 0 1rem 0;
    font-size: 1.2rem;
}

.balance-amount {
    font-size: 2.5rem;
    font-weight: bold;
    color: #10b981;
}

.redemption-form {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    color: #e8e8e8;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-group input {
    width: 100%;
    padding: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #e8e8e8;
    font-size: 1.1rem;
    font-family: 'Courier New', monospace;
    letter-spacing: 0.1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #f59e0b;
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.form-group small {
    color: #888;
    font-size: 0.9rem;
    margin-top: 0.5rem;
    display: block;
}

.btn-redeem {
    width: 100%;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-redeem:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
}

.info-section {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-section h3 {
    color: #60a5fa;
    margin: 0 0 1rem 0;
}

.info-content {
    display: grid;
    gap: 0.8rem;
}

.info-item {
    color: #e8e8e8;
    font-size: 0.9rem;
}

.info-item strong {
    color: #60a5fa;
}

.history-section {
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.history-section h3 {
    color: #a8a8f0;
    margin: 0 0 1rem 0;
}

.history-list {
    display: grid;
    gap: 0.8rem;
}

.history-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 1rem;
    align-items: center;
    padding: 0.8rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.history-code {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    color: #60a5fa;
    font-weight: bold;
}

.history-amount {
    color: #10b981;
    font-weight: bold;
}

.history-date {
    color: #888;
    font-size: 0.8rem;
}

.success-animation {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000;
    background: rgba(0, 0, 0, 0.9);
    padding: 3rem;
    border-radius: 16px;
    text-align: center;
    border: 1px solid rgba(16, 185, 129, 0.3);
    animation: fadeIn 0.5s ease-out;
}

.checkmark-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #10b981;
    position: relative;
    animation: scaleIn 0.8s ease-out;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 1rem auto;
}

.checkmark {
    position: relative;
    width: 32px;
    height: 32px;
}

.checkmark-stem {
    position: absolute;
    width: 3px;
    height: 16px;
    background: white;
    left: 14px;
    top: 6px;
    transform: rotate(45deg);
    transform-origin: bottom;
    animation: checkmarkStem 0.6s ease-out 0.8s both;
    border-radius: 2px;
}

.checkmark-kick {
    position: absolute;
    width: 10px;
    height: 3px;
    background: white;
    left: 8px;
    top: 18px;
    transform: rotate(-45deg);
    transform-origin: left;
    animation: checkmarkKick 0.6s ease-out 1.2s both;
    border-radius: 2px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}

@keyframes scaleIn {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes checkmarkStem {
    0% { height: 0; opacity: 0; }
    50% { opacity: 1; }
    100% { height: 16px; opacity: 1; }
}

@keyframes checkmarkKick {
    0% { width: 0; opacity: 0; }
    50% { opacity: 1; }
    100% { width: 10px; opacity: 1; }
}

.success-text h3 {
    color: #10b981;
    margin: 0 0 0.5rem 0;
}

.success-text p {
    color: #e8e8e8;
    margin: 0;
}

.quick-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.action-btn {
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    color: #e8e8e8;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

/* Auto-hide success animation */
.success-animation {
    animation: fadeIn 0.5s ease-out, fadeOut 0.5s ease-out 4s forwards;
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; visibility: hidden; }
}

/* Responsive design */
@media (max-width: 768px) {
    .redeem-gift-card {
        padding: 1rem;
    }
    
    .history-item {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        text-align: center;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .action-btn {
        text-align: center;
    }
}
</style>

<script>
// Auto-format gift card code input
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('gift_card_code');
    
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            // Add dashes automatically
            if (value.length > 7) {
                value = value.substring(0, 7) + '-' + value.substring(7);
            }
            if (value.length > 12) {
                value = value.substring(0, 12) + '-' + value.substring(12);
            }
            if (value.length > 17) {
                value = value.substring(0, 17) + '-' + value.substring(17);
            }
            
            // Limit to expected length
            if (value.length > 22) {
                value = value.substring(0, 22);
            }
            
            e.target.value = value;
        });
        
        // Focus the input
        codeInput.focus();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
