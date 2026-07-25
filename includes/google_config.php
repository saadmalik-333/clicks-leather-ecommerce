<?php
/**
 * Clicks Leather — Google OAuth Configuration
 * 
 * Replace these values with your Google Cloud Console credentials.
 * 
 * Setup steps:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select existing
 * 3. Enable "Google+ API" or "Google People API"
 * 4. Go to Credentials → Create OAuth 2.0 Client ID
 * 5. Set Authorized redirect URI to: http://localhost/Leather website/public/google_callback.php
 * 6. Copy Client ID and Client Secret below
 */

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/public/google_callback.php');

// Google OAuth URLs
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

/**
 * Generate Google OAuth login URL
 */
function get_google_login_url(): string {
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account'
    ];
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}
