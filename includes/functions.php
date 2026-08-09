<?php
/**
 * Clicks Leather — Utility Functions
 * 
 * Common helper functions used across the application.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize user input — trim whitespace and escape HTML entities
 */
function sanitize_input(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    // Check session timeout (30 minutes = 1800 seconds)
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > 1800) {
            // Session expired - clear user session keys manually (keep session/cookie alive for flash message)
            unset($_SESSION['user_id']);
            unset($_SESSION['user_naam']);
            unset($_SESSION['user_email']);
            unset($_SESSION['user_role']);
            unset($_SESSION['logged_in']);
            unset($_SESSION['last_activity']);
            set_flash_message('error', 'Your session has expired. Please log in again.');
            $_SESSION['just_expired'] = true;
            return false;
        }
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    } else {
        // Set last_activity if not set (for existing sessions)
        $_SESSION['last_activity'] = time();
    }

    return true;
}

/**
 * Check if logged-in user is an admin
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login — redirect to login page if not authenticated
 */
function require_login(): void {
    if (!is_logged_in()) {
        // Only set flash message if not already set (e.g., by session expiry)
        if (!isset($_SESSION['flash'])) {
            set_flash_message('error', 'Please login to continue.');
        }
        redirect(PUBLIC_URL . '/login.php');
    }
}

/**
 * Require active session — redirect to login if session just expired
 * Called on all pages to handle session timeout immediately
 */
function require_active_session(): void {
    is_logged_in(); // This checks timeout and sets just_expired flag if expired
    if (isset($_SESSION['just_expired']) && $_SESSION['just_expired'] === true) {
        unset($_SESSION['just_expired']);
        redirect(PUBLIC_URL . '/login.php');
    }
}

/**
 * Require admin role — redirect if not admin
 */
function require_admin(): void {
    if (!is_admin()) {
        set_flash_message('error', 'Access denied. Admin privileges required.');
        redirect(PUBLIC_URL . '/login.php');
    }
}

/**
 * Set a flash message (stored in session, displayed once)
 */
function set_flash_message(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,    // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function get_flash_message(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message as HTML
 */
function display_flash_message(): string {
    $flash = get_flash_message();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        return "<div class='flash-message flash-{$type}'>{$message}</div>";
    }
    return '';
}

/**
 * Secure image upload
 * 
 * @param array  $file       The $_FILES['field'] array
 * @param string $upload_dir Target directory (default: uploads/)
 * @param int    $max_size   Max file size in bytes (default: 2MB)
 * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
 */
function upload_image(array $file, string $upload_dir = '', int $max_size = 2097152): array {
    // Default upload directory
    if (empty($upload_dir)) {
        $upload_dir = UPLOADS_PATH;
    }

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
        ];
        $msg = $error_messages[$file['error']] ?? 'Unknown upload error.';
        return ['success' => false, 'message' => $msg, 'filename' => null];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        $max_mb = $max_size / 1048576;
        return ['success' => false, 'message' => "File size exceeds {$max_mb}MB limit.", 'filename' => null];
    }

    // Allowed MIME types
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, and PNG files are allowed.', 'filename' => null];
    }

    // Allowed extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file extension. Only jpg, jpeg, png allowed.', 'filename' => null];
    }

    // Generate unique filename to prevent overwrites
    $new_filename = uniqid('product_', true) . '.' . $extension;
    $destination = $upload_dir . '/' . $new_filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'message' => 'Image uploaded successfully.', 'filename' => $new_filename];
    }

    return ['success' => false, 'message' => 'Failed to save uploaded file.', 'filename' => null];
}

/**
 * Delete an uploaded image
 */
function delete_image(string $filename): bool {
    $filepath = UPLOADS_PATH . '/' . $filename;
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get all categories from database
 */
function get_all_categories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY naam ASC");
    return $stmt->fetchAll();
}

/**
 * Get representative image for each category
 */
function get_category_representative_images(PDO $pdo): array {
    $sql = "
        SELECT 
            c.id as category_id,
            c.naam as category_name,
            p.image_path
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id AND p.image_path IS NOT NULL
        GROUP BY c.id
        ORDER BY c.naam ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Get category by name
 */
function get_category_by_name(PDO $pdo, string $name): ?array {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE naam = :naam LIMIT 1");
    $stmt->execute(['naam' => $name]);
    $category = $stmt->fetch();
    return $category ?: null;
}

/**
 * Get popular products (products marked as is_popular = 1)
 */
function get_popular_products(PDO $pdo, int $limit = 8): array {
    $sql = "
        SELECT 
            p.id,
            p.naam,
            p.price,
            p.image_path,
            c.naam as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.is_popular = 1
        ORDER BY p.id ASC
        LIMIT :limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['limit' => $limit]);
    return $stmt->fetchAll();
}

/**
 * Get products by category name
 */
function get_products_by_category(PDO $pdo, string $category_name): array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.naam as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE c.naam = :category_name 
        ORDER BY p.naam ASC
    ");
    $stmt->execute(['category_name' => $category_name]);
    return $stmt->fetchAll();
}

/**
 * Format price with currency
 */
function format_price(float $price): string {
    return '$' . number_format($price, 2);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(string $token): bool {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Regenerate token after successful verification
        unset($_SESSION['csrf_token']);
        return true;
    }
    return false;
}

/**
 * Output a hidden CSRF input field
 */
function csrf_field(): string {
    $token = generate_csrf_token();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}

/**
 * Get a setting value from the settings table
 */
function get_setting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

/**
 * Update or insert a setting value
 */
function update_setting(PDO $pdo, string $key, string $value): bool {
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Get applicable discount for a product
 * Priority: product-level > category-level > sitewide
 * 
 * @param PDO $pdo Database connection
 * @param int $product_id Product ID
 * @param int $category_id Category ID
 * @return array|null Discount info or null if no applicable discount
 */
function get_product_discount(PDO $pdo, int $product_id, int $category_id): ?array {
    $today = date('Y-m-d');
    
    // Priority 1: Product-level discount
    $stmt = $pdo->prepare("
        SELECT * FROM discounts 
        WHERE type = 'product' 
        AND target_id = ? 
        AND is_active = 1 
        AND (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$product_id, $today, $today]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($discount) {
        return $discount;
    }
    
    // Priority 2: Category-level discount
    $stmt = $pdo->prepare("
        SELECT * FROM discounts 
        WHERE type = 'category' 
        AND target_id = ? 
        AND is_active = 1 
        AND (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$category_id, $today, $today]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($discount) {
        return $discount;
    }
    
    // Priority 3: Sitewide discount
    $stmt = $pdo->prepare("
        SELECT * FROM discounts 
        WHERE type = 'sitewide' 
        AND target_id IS NULL 
        AND is_active = 1 
        AND (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$today, $today]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $discount ?: null;
}
