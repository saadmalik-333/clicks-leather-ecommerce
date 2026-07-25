<?php
/**
 * Clicks Leather — Admin Login Page
 * Separate from customer login, with role verification.
 */
require_once __DIR__ . '/../includes/auth.php';

// If already logged in as admin, redirect to dashboard
if (is_admin()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

// If logged in but not admin, show access denied
if (is_logged_in() && !is_admin()) {
    set_flash_message('error', 'Access denied. Admin privileges required.');
    redirect(PUBLIC_URL . '/index.php');
}

$errors = [];
$old_email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $old_email = htmlspecialchars($email);

        $result = login_user($pdo, $email, $password);

        if ($result['success']) {
            // Double-check this user is actually an admin
            if ($_SESSION['user_role'] !== 'admin') {
                logout_user();
                $errors[] = 'Access denied. Admin privileges required.';
            } else {
                set_flash_message('success', 'Welcome back, Admin!');
                redirect(ADMIN_URL . '/dashboard.php');
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
</head>
<body class="auth-page admin-auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">Clicks Leather</div>
                <div class="admin-badge">Admin Panel</div>
                <h1>Admin Login</h1>
                <p>Enter your admin credentials</p>
            </div>

            <?= display_flash_message() ?>

            <?php if (!empty($errors)): ?>
                <div class="flash-message flash-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" id="admin-login-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" value="<?= $old_email ?>" 
                           placeholder="admin@clicksleather.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter admin password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="admin-login-btn">
                    Login as Admin
                </button>
            </form>

            <div class="auth-footer">
                <p><a href="<?= PUBLIC_URL ?>/login.php">← Back to customer login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
