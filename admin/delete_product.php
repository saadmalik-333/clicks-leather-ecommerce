<?php
/**
 * Clicks Leather — Delete Product
 * POST-only handler (no GET deletes for security)
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Verify admin access
require_admin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Invalid request method.');
    redirect(ADMIN_URL . '/products.php');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('error', 'Invalid form submission.');
    redirect(ADMIN_URL . '/products.php');
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    set_flash_message('error', 'Invalid product ID.');
    redirect(ADMIN_URL . '/products.php');
}

// Fetch product to get image path before deleting
$stmt = $pdo->prepare("SELECT naam, image_path FROM products WHERE id = :id");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash_message('error', 'Product not found.');
    redirect(ADMIN_URL . '/products.php');
}

// Check if product has associated order items
$stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = :product_id");
$stmt->execute([':product_id' => $product_id]);
$order_count = $stmt->fetchColumn();

if ($order_count > 0) {
    set_flash_message('error', "Cannot delete '{$product['naam']}' — it has {$order_count} associated order(s). Consider archiving instead.");
    redirect(ADMIN_URL . '/products.php');
}

try {
    // Delete product (variants will cascade-delete via FK)
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $product_id]);

    // Delete product image
    if ($product['image_path']) {
        delete_image($product['image_path']);
    }

    set_flash_message('success', "Product '{$product['naam']}' deleted successfully.");
} catch (Exception $e) {
    error_log("Delete Product Error: " . $e->getMessage());
    set_flash_message('error', 'Failed to delete product. Please try again.');
}

redirect(ADMIN_URL . '/products.php');
