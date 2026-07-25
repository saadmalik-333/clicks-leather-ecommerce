<?php
/**
 * Clicks Leather — Google OAuth Callback
 * 
 * Handles the redirect from Google after user authorizes the app.
 */
require_once __DIR__ . '/../includes/auth.php';

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
    redirect($result['redirect']);
} else {
    set_flash_message('error', $result['message']);
    redirect(PUBLIC_URL . '/login.php');
}
