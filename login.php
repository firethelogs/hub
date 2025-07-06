<?php
// login.php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = 'All fields are required.';
    } else {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned']) {
                $error = 'Your account is banned.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header('Location: /dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<div class="card">
    <h2>🔐 Sign In</h2>
    <p style="color: #888; margin-bottom: 2rem;">Access your wallet and continue shopping</p>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Sign In</button>
    </form>
    
    <p style="text-align: center; margin-top: 2rem; color: #888;">
        Don't have an account? <a href="/register.php" style="color: #6366f1;">Create one here</a>
    </p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
