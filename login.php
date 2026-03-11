<?php
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, full_name FROM admins WHERE username = ? AND password = ?");
        $stmt->bind_param('ss', $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['full_name'];
            $_SESSION['admin_user'] = $row['username'];
            setFlash('success', 'Welcome back, ' . $row['full_name'] . '!');
            redirect(SITE_URL . '/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1>Admin Login</h1>
        <p class="subtitle">Sign in to manage the NFC Tourist ID system</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
