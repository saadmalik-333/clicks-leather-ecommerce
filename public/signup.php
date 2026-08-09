<?php
/**
 * Clicks Leather — Signup Page
 */
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect(is_admin() ? ADMIN_URL . '/dashboard.php' : PUBLIC_URL . '/index.php');
}

$errors = [];
$old_naam = '';
$old_email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $naam = $_POST['naam'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $old_naam = htmlspecialchars($naam);
        $old_email = htmlspecialchars($email);

        $result = register_user($pdo, $naam, $email, $password, $confirm_password);

        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect(PUBLIC_URL . '/login.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}

$google_login_url = get_google_login_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your Clicks Leather account — premium leather goods handcrafted for you.">
    <title>Sign Up — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?= PUBLIC_URL ?>/index.php" class="auth-logo">Clicks Leather</a>
                <h1>Create Account</h1>
                <p>Join us for premium leather goods</p>
            </div>

            <?= display_flash_message() ?>

            <script src="<?= PUBLIC_URL ?>/js/flash-message.js"></script>

            <?php if (!empty($errors)): ?>
                <div class="flash-message flash-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" id="signup-form">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="naam">Full Name</label>
                    <input type="text" id="naam" name="naam" value="<?= $old_naam ?>" 
                           placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= $old_email ?>" 
                           placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" 
                               placeholder="Min 8 characters" required minlength="8">
                        <button type="button" class="password-toggle-icon" aria-label="Show password">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm your password" required minlength="8">
                        <button type="button" class="password-toggle-icon" aria-label="Show password">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="signup-btn">
                    Create Account
                </button>
            </form>

            <div class="auth-divider">
                <span>or continue with</span>
            </div>

            <a href="<?= htmlspecialchars($google_login_url) ?>" class="btn btn-google btn-full" id="google-signin-btn">
                <svg width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Sign up with Google
            </a>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?= PUBLIC_URL ?>/login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="<?= PUBLIC_URL ?>/js/password-toggle.js"></script>
</body>
</html>
