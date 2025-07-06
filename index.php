<?php
// index.php
session_start();
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:700px;text-align:center;">
    <h1>🎯 Welcome to JaxxyCC Store</h1>
    <p style="font-size: 1.1rem; color: #a8a8a8; margin-bottom: 2rem;">
        Your premium destination for exclusive digital content and secure wallet management. 
        Join JaxxyCC Store to purchase premium items, manage your wallet, and unlock exclusive features.
    </p>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
        <a href="/telegram_login.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; background: #10b981; color: white; border: none; font-size: 1.1rem;">📱 Login with Telegram</button>
        </a>
        <a href="/telegram_demo.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">ℹ️ How it Works</button>
        </a>
    </div>
    <div style="text-align: center; margin-top: 2rem;">
        <p style="color: #888; font-size: 0.9rem;">
            🔒 Secure login with Telegram bot • No passwords required • Instant notifications
        </p>
    </div>
    <?php else: ?>
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
        <a href="/dashboard.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem;">Go to Dashboard</button>
        </a>
        <a href="/store.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">Browse Store</button>
        </a>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
