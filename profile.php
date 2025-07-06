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
    
    <!-- Profile Photo and Basic Info -->
    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem;">
        <?php if ($user['telegram_photo_url']): ?>
            <img src="<?= htmlspecialchars($user['telegram_photo_url']) ?>" alt="Profile Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(99, 102, 241, 0.3);">
        <?php else: ?>
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                <?php 
                $displayName = $user['telegram_username'] ?? $user['telegram_first_name'] ?? $user['username'];
                echo strtoupper(substr($displayName, 0, 2));
                ?>
            </div>
        <?php endif; ?>
        
        <div>
            <h3 style="margin: 0; color: #e8e8e8;">
                <?php if ($user['telegram_username']): ?>
                    @<?= htmlspecialchars($user['telegram_username']) ?>
                <?php elseif ($user['telegram_first_name']): ?>
                    <?= htmlspecialchars($user['telegram_first_name']) ?>
                <?php else: ?>
                    <?= htmlspecialchars($user['username']) ?>
                <?php endif; ?>
            </h3>
            <p style="margin: 0.5rem 0 0 0; color: #888;">
                <span class="online-status <?= $user['is_online'] ? 'online' : 'offline' ?>">
                    <?= $user['is_online'] ? '🟢 Online' : '🔴 Offline' ?>
                </span>
                <?php if (!$user['is_online'] && $user['last_seen']): ?>
                    <span style="color: #666; font-size: 0.9rem;">
                        • Last seen <?= date('M j, Y \a\t g:i A', strtotime($user['last_seen'])) ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <!-- Account Information -->
    <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0;">
        <h4 style="margin: 0 0 1rem 0; color: #e8e8e8;">📋 Account Information</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <strong>Username:</strong> 
                <?php if ($user['telegram_username']): ?>
                    @<?= htmlspecialchars($user['telegram_username']) ?>
                <?php elseif ($user['telegram_first_name']): ?>
                    <?= htmlspecialchars($user['telegram_first_name']) ?>
                <?php else: ?>
                    <?= htmlspecialchars($user['username']) ?>
                <?php endif; ?>
            </div>
            <div>
                <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
            </div>
            <div>
                <strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?>
            </div>
            <?php if ($user['is_admin']): ?>
            <div>
                <strong>Role:</strong> <span style="color: #8b5cf6;">Administrator</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Telegram Information -->
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0;">
        <h4 style="margin: 0 0 1rem 0; color: #e8e8e8;">📱 Telegram Integration</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <strong>Telegram ID:</strong> <?= htmlspecialchars($user['telegram_id']) ?>
            </div>
            <?php if ($user['telegram_username']): ?>
            <div>
                <strong>Telegram Username:</strong> @<?= htmlspecialchars($user['telegram_username']) ?>
            </div>
            <?php endif; ?>
            <?php if ($user['telegram_first_name']): ?>
            <div>
                <strong>First Name:</strong> <?= htmlspecialchars($user['telegram_first_name']) ?>
            </div>
            <?php endif; ?>
            <?php if ($user['telegram_last_name']): ?>
            <div>
                <strong>Last Name:</strong> <?= htmlspecialchars($user['telegram_last_name']) ?>
            </div>
            <?php endif; ?>
            <div>
                <strong>Connected:</strong> <?= date('F j, Y', $user['telegram_auth_date']) ?>
            </div>
        </div>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(16, 185, 129, 0.2);">
            <p style="margin: 0; color: #10b981; font-size: 0.9rem;">
                ✅ Your Telegram account is connected and you will receive notifications for purchases, balance updates, and more!
            </p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="/dashboard.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; margin: 0 0.5rem;">💰 View Dashboard</button>
        </a>
        <a href="/store.php" style="text-decoration: none;">
            <button style="width: auto; padding: 1rem 2rem; margin: 0 0.5rem; background: rgba(255,255,255,0.1); color: #e8e8e8;">🛍️ Browse JaxxyCC Store</button>
        </a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
