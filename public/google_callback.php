<?php
/**
 * Clicks Leather — Google OAuth Callback
 * 
 * Handles the redirect from Google after user authorizes the app.
 */
require_once __DIR__ . '/../includes/auth.php';

// Check for Google error response (user cancelled)
if (isset($_GET['error'])) {
    set_flash_message('error', 'Google sign-in was cancelled or failed. Please try again.');
    redirect(PUBLIC_URL . '/login.php');
}

// Verify OAuth state parameter (CSRF protection)
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || 
    !is_string($_GET['state']) || !is_string($_SESSION['google_oauth_state']) ||
    !hash_equals($_SESSION['google_oauth_state'], $_GET['state'])) {
    set_flash_message('error', 'Google sign-in could not be verified. Please try again.');
    redirect(PUBLIC_URL . '/login.php');
}

// Unset state after verification (single use)
unset($_SESSION['google_oauth_state']);

// Check for authorization code
if (!isset($_GET['code'])) {
    set_flash_message('error', 'Google authentication failed. No authorization code received.');
    redirect(PUBLIC_URL . '/login.php');
}

$auth_code = $_GET['code'];

// Handle the OAuth callback
$result = handle_google_callback($pdo, $auth_code);

if ($result['success']) {
    set_flash_message('success', $result['message']);
    
    // Check for checkout redirect intent (non-admin only)
    $post_login_redirect = $_SESSION['post_login_redirect'] ?? null;
    unset($_SESSION['post_login_redirect']);
    
    if ($post_login_redirect === 'checkout' && ($_SESSION['user_role'] ?? '') !== 'admin') {
        redirect(PUBLIC_URL . '/checkout.php');
    }
    
    redirect($result['redirect']);
} else {
    set_flash_message('error', $result['message']);
    redirect(PUBLIC_URL . '/login.php');
}
