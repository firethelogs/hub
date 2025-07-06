<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JaxxyCC Store</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <nav>
        <a href="/index.php">🎯 JaxxyCC Store</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            // Get user's Telegram info and online status
            try {
                if (!function_exists('get_db')) {
                    require_once __DIR__ . '/functions.php';
                }
                $db = get_db();
                $stmt = $db->prepare('SELECT telegram_username, telegram_first_name, telegram_photo_url, is_online, last_seen FROM users WHERE id = ?');
                $stmt->execute([$_SESSION['user_id']]);
                $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $userInfo = null;
            }
            ?>
            <a href="/dashboard.php">Dashboard</a>
            <a href="/store.php">Store</a>
            <a href="/redeem_gift_card.php">🎁 Gift Cards</a>
            <a href="/profile.php">Profile</a>
            
            <!-- User Info with Telegram -->
            <div class="user-info">
                <?php if ($userInfo && $userInfo['telegram_photo_url']): ?>
                    <img src="<?= htmlspecialchars($userInfo['telegram_photo_url']) ?>" alt="Profile" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar-placeholder">👤</div>
                <?php endif; ?>
                <div class="user-details">
                    <?php if (isset($_SESSION['display_name'])): ?>
                        <span class="username"><?= htmlspecialchars($_SESSION['display_name']) ?></span>
                    <?php elseif ($userInfo && $userInfo['telegram_username']): ?>
                        <span class="username">@<?= htmlspecialchars($userInfo['telegram_username']) ?></span>
                    <?php elseif ($userInfo && $userInfo['telegram_first_name']): ?>
                        <span class="username"><?= htmlspecialchars($userInfo['telegram_first_name']) ?></span>
                    <?php else: ?>
                        <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php endif; ?>
                    <span class="online-status <?= ($userInfo && $userInfo['is_online']) ? 'online' : 'offline' ?>">
                        <?= ($userInfo && $userInfo['is_online']) ? '🟢 Online' : '🔴 Offline' ?>
                    </span>
                </div>
            </div>
            
            <a href="/logout.php">Logout</a>
        <?php else: ?>
            <a href="/telegram_login.php">📱 Login with Telegram</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <a href="/admin/panel.php">⚡ Admin Panel</a>
        <?php endif; ?>
    </nav>
</header>
<main>
