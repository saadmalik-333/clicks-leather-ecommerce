<?php
/**
 * Clicks Leather — Admin Change Password Page
 */
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Admin auth check FIRST (before any POST handling)
require_admin();

$page_title = 'Change Password';

// POST handling (now safe - only admins can reach this)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation (reusing account.php rules)
    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    }
    
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match.';
    }
    
    // Verify current password
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT password_hashed FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password_hashed'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }
    
    // Update password if no errors
    if (empty($errors)) {
        try {
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hashed = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $user_id]);
            
            set_flash_message('success', 'Password changed successfully.');
        } catch (Exception $e) {
            set_flash_message('error', 'Error changing password. Please try again.');
        }
    } else {
        set_flash_message('error', implode(' ', $errors));
    }
    
    redirect(ADMIN_URL . '/change-password.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-section">
    <h3 class="section-title">Change Password</h3>
    
    <div class="form-card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="current_password" name="current_password" required>
                    <button type="button" class="password-toggle-icon" aria-label="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="new_password" name="new_password" required>
                    <button type="button" class="password-toggle-icon" aria-label="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <small class="form-hint">Must be at least 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="password-toggle-icon" aria-label="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<style>
    .form-card {
        background: var(--bg-card);
        border-radius: var(--radius-md);
        padding: 2rem;
        max-width: 500px;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-primary);
    }
    
    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-size: 0.9rem;
        font-family: var(--font-body);
    }
    
    .form-group input:focus {
        outline: none;
        border-color: var(--color-primary);
    }
    
    .form-hint {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
    }
    
    .password-input-wrapper {
        position: relative;
    }
    
    .password-input-wrapper input {
        padding-right: 2.5rem;
    }
    
    .password-toggle-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
    }
    
    .password-toggle-icon:hover {
        color: var(--text-primary);
    }
    
    .password-toggle-icon svg {
        width: 20px;
        height: 20px;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-primary {
        background: var(--color-primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--color-primary-dark);
    }
</style>

<script src="<?= PUBLIC_URL ?>/js/password-toggle.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
