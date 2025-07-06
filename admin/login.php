<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = 'All fields are required.';
    } else {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND is_admin = 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = 1;
            header('Location: /admin/panel.php');
            exit;
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<div class="card">
    <h2>⚡ JaxxyCC Store Admin</h2>
    <p style="color: #888; margin-bottom: 2rem;">Administrative access required</p>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="text" name="username" placeholder="Admin Username" required>
        <input type="password" name="password" placeholder="Admin Password" required>
        <button type="submit">Access Admin Panel</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
