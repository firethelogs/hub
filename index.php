<?php
// index.php
session_start();
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:700px;text-align:center;">
    <h1>🚀 Welcome to WalletStore</h1>
    <p style="font-size: 1.1rem; color: #a8a8a8; margin-bottom: 2rem;">
        The ultimate platform for digital content with secure wallet integration. 
        Register to manage your wallet, purchase exclusive content, and unlock premium features.
    </p>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
        <a href="/register.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem;">Get Started</button>
        </a>
        <a href="/login.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">Sign In</button>
        </a>
    </div>
    <?php else: ?>
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
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
