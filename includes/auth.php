<?php
/**
 * Clicks Leather — Authentication Functions
 * 
 * Handles user registration, login, Google OAuth, and logout.
 * All passwords hashed with bcrypt via password_hash().
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/google_config.php';

/**
 * Register a new user
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function register_user(PDO $pdo, string $naam, string $email, string $password, string $confirm_password): array {
    // Validate inputs
    $naam = sanitize_input($naam);
    $email = sanitize_input($email);

    if (empty($naam) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    if ($password !== $confirm_password) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    // Hash password with bcrypt
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user
    $stmt = $pdo->prepare(
        "INSERT INTO users (naam, email, password_hashed, role) VALUES (:naam, :email, :password_hashed, 'customer')"
    );
    $stmt->execute([
        ':naam'            => $naam,
        ':email'           => $email,
        ':password_hashed' => $password_hashed
    ]);

    return ['success' => true, 'message' => 'Registration successful! Please login.'];
}

/**
 * Login user with email and password
 * 
 * @return array ['success' => bool, 'message' => string, 'redirect' => string|null]
 */
function login_user(PDO $pdo, string $email, string $password, bool $force_customer_role = false): array {
    $email = sanitize_input($email);

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required.', 'redirect' => null];
    }

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT id, naam, email, password_hashed, role FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password.', 'redirect' => null];
    }

    // Google-only accounts won't have a password
    if (empty($user['password_hashed'])) {
        return ['success' => false, 'message' => 'This account uses Google Sign-In. Please use the Google button.', 'redirect' => null];
    }

    // Verify password
    if (!password_verify($password, $user['password_hashed'])) {
        return ['success' => false, 'message' => 'Invalid email or password.', 'redirect' => null];
    }

    // Set session
    if ($force_customer_role) {
        $customer_user = $user;
        $customer_user['role'] = 'customer';
        set_user_session($pdo, $customer_user);
    } else {
        set_user_session($pdo, $user);
    }

    // Use safe redirect validation
    $redirect = get_safe_redirect();

    // Override with admin dashboard if admin (unless explicitly redirecting to checkout)
    if (!$force_customer_role && $user['role'] === 'admin' && ($_GET['redirect'] ?? '') !== 'checkout') {
        $redirect = ADMIN_URL . '/dashboard.php';
    }

    return ['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect];
}

/**
 * Handle Google OAuth callback
 * 
 * @return array ['success' => bool, 'message' => string, 'redirect' => string|null]
 */
function handle_google_callback(PDO $pdo, string $auth_code): array {
    // Exchange authorization code for access token
    $token_data = [
        'code'          => $auth_code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return ['success' => false, 'message' => 'Failed to connect to Google.', 'redirect' => null];
    }
    curl_close($ch);

    $token = json_decode($response, true);

    if (!isset($token['access_token'])) {
        return ['success' => false, 'message' => 'Failed to get access token from Google.', 'redirect' => null];
    }

    // Fetch user info from Google
    $ch = curl_init(GOOGLE_USERINFO_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token['access_token']
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return ['success' => false, 'message' => 'Failed to fetch Google user info.', 'redirect' => null];
    }
    curl_close($ch);

    $google_user = json_decode($response, true);

    if (!isset($google_user['email'])) {
        return ['success' => false, 'message' => 'Failed to get email from Google.', 'redirect' => null];
    }

    $google_id = $google_user['id'];
    $email     = $google_user['email'];
    $naam      = $google_user['name'] ?? 'Google User';

    // Check if user exists (by google_id or email)
    $stmt = $pdo->prepare("SELECT id, naam, email, role FROM users WHERE google_id = :google_id OR email = :email LIMIT 1");
    $stmt->execute([':google_id' => $google_id, ':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update google_id if not set
        if (empty($user['google_id'])) {
            $update = $pdo->prepare("UPDATE users SET google_id = :google_id WHERE id = :id");
            $update->execute([':google_id' => $google_id, ':id' => $user['id']]);
        }
    } else {
        // Create new user (no password — Google-only account)
        $stmt = $pdo->prepare(
            "INSERT INTO users (naam, email, google_id, role) VALUES (:naam, :email, :google_id, 'customer')"
        );
        $stmt->execute([
            ':naam'      => $naam,
            ':email'     => $email,
            ':google_id' => $google_id
        ]);

        // Fetch the newly created user
        $user = [
            'id'    => $pdo->lastInsertId(),
            'naam'  => $naam,
            'email' => $email,
            'role'  => 'customer'
        ];
    }

    // Set session
    set_user_session($pdo, $user);

    // Role-based redirect
    $redirect = ($user['role'] === 'admin') ? ADMIN_URL . '/dashboard.php' : PUBLIC_URL . '/index.php';

    return ['success' => true, 'message' => 'Google login successful!', 'redirect' => $redirect];
}

/**
 * Validate redirect URL to prevent open redirect attacks
 * Only allows internal relative paths or specific whitelisted tokens
 */
function get_safe_redirect(): string {
    $redirect = $_GET['redirect'] ?? null;
    
    // If no redirect, use default
    if (!$redirect) {
        return PUBLIC_URL . '/index.php';
    }
    
    // Whitelist of allowed redirect tokens
    $allowed_tokens = ['checkout', 'account'];
    
    // If it's a whitelisted token, convert to full URL
    if (in_array($redirect, $allowed_tokens)) {
        return PUBLIC_URL . '/' . $redirect . '.php';
    }
    
    // If it's a relative path (no protocol, no //), validate it's within the site
    if (!preg_match('/^(https?:|\/\/)/i', $redirect)) {
        // Ensure it's a relative path starting with / or just a filename
        if (strpos($redirect, '/') === 0 || !strpos($redirect, '/')) {
            // Additional check: ensure no directory traversal
            if (strpos($redirect, '..') === false) {
                return PUBLIC_URL . '/' . ltrim($redirect, '/');
            }
        }
    }
    
    // Default to safe redirect if validation fails
    return PUBLIC_URL . '/index.php';
}

/**
 * Merge guest cart items into user cart after login
 */
function merge_guest_cart(PDO $pdo, int $user_id, string $guest_session_id): void {
    if (empty($guest_session_id)) {
        return;
    }
    
    // Get guest cart items
    $stmt = $pdo->prepare("
        SELECT product_id, variant_id, quantity, discounted_price, discount_percent, personalization_text
        FROM cart_items
        WHERE session_id = ? AND user_id IS NULL
    ");
    $stmt->execute([$guest_session_id]);
    $guest_items = $stmt->fetchAll();
    
    if (empty($guest_items)) {
        return;
    }
    
    // Merge each guest item into user cart
    foreach ($guest_items as $item) {
        // Check if user already has this product/variant in cart
        $check_stmt = $pdo->prepare("
            SELECT id, quantity FROM cart_items
            WHERE user_id = ? AND product_id = ? 
            AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))
        ");
        $check_stmt->execute([
            $user_id,
            $item['product_id'],
            $item['variant_id'],
            $item['variant_id']
        ]);
        $existing = $check_stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $update_stmt = $pdo->prepare("
                UPDATE cart_items 
                SET quantity = quantity + ?, 
                    discounted_price = ?, 
                    discount_percent = ?
                WHERE id = ?
            ");
            $update_stmt->execute([
                $item['quantity'],
                $item['discounted_price'],
                $item['discount_percent'],
                $existing['id']
            ]);
        } else {
            // Insert new item with user_id
            $insert_stmt = $pdo->prepare("
                INSERT INTO cart_items (
                    user_id, product_id, variant_id, quantity, 
                    discounted_price, discount_percent, personalization_text
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([
                $user_id,
                $item['product_id'],
                $item['variant_id'],
                $item['quantity'],
                $item['discounted_price'],
                $item['discount_percent'],
                $item['personalization_text']
            ]);
        }
    }
    
    // Delete old guest cart items
    $delete_stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ? AND user_id IS NULL");
    $delete_stmt->execute([$guest_session_id]);
}

/**
 * Set user session data
 */
function set_user_session(PDO $pdo, array $user): void {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_naam'] = $user['naam'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    // Merge guest cart if exists
    if (isset($_SESSION['guest_cart_session_id'])) {
        merge_guest_cart($pdo, $user['id'], $_SESSION['guest_cart_session_id']);
        unset($_SESSION['guest_cart_session_id']);
    }
}

/**
 * Logout user — destroy session
 */
function logout_user(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}
