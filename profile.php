<?php
// profile.php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
include __DIR__ . '/includes/header.php';

$user = get_user($_SESSION['user_id']);
?>
<div class="card">
    <h2>👤 Your Profile</h2>
    <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0;">
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
        <?php if ($user['is_admin']): ?>
        <p><strong>Role:</strong> <span style="color: #8b5cf6;">Administrator</span></p>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="/dashboard.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; margin: 0 0.5rem;">💰 View Dashboard</button>
        </a>
        <a href="/store.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; margin: 0 0.5rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">🛍️ Browse Store</button>
        </a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
