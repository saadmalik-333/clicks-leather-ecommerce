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
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
        set_flash_message('error', 'Please login to continue.');
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
 * Format price with currency
 */
function format_price(float $price): string {
    return 'Rs. ' . number_format($price, 2);
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
