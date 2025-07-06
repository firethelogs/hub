<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark PHP Wallet Store</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <nav>
        <a href="/index.php">🚀 WalletStore</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/dashboard.php">Dashboard</a>
            <a href="/store.php">Store</a>
            <a href="/profile.php">Profile</a>
            <a href="/logout.php">Logout</a>
        <?php else: ?>
            <a href="/login.php">Login</a>
            <a href="/register.php">Register</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <a href="/admin/panel.php">⚡ Admin Panel</a>
        <?php endif; ?>
    </nav>
</header>
<main>
