<?php
// register.php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } else {
        $db = get_db();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)')->execute([$username, $email, $hash]);
            $user_id = $db->lastInsertId();
            $db->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 0)')->execute([$user_id]);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = 0;
            header('Location: /dashboard.php');
            exit;
        }
    }
}
?>
<div class="card">
    <h2>🚀 Create Account</h2>
    <p style="color: #888; margin-bottom: 2rem;">Join WalletStore and start your journey</p>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="text" name="username" placeholder="Choose a username" required>
        <input type="email" name="email" placeholder="Your email address" required>
        <input type="password" name="password" placeholder="Create a password" required>
        <button type="submit">Create Account</button>
    </form>
    
    <p style="text-align: center; margin-top: 2rem; color: #888;">
        Already have an account? <a href="/login.php" style="color: #6366f1;">Sign in here</a>
    </p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
