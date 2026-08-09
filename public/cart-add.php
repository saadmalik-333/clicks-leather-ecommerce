<?php
/**
 * Add to Cart Endpoint
 * Handles AJAX requests to add items to the shopping cart
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure cart session ID exists
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = uniqid('cart_', true);
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $product_id = intval($input['product_id'] ?? 0);
    $color = sanitize_input($input['color'] ?? '');
    $size = sanitize_input($input['size'] ?? '');
    $quantity = intval($input['quantity'] ?? 1);
    $personalization_text = sanitize_input($input['personalization_text'] ?? '');
    
    // If personalization text is empty, set to null
    if (empty($personalization_text)) {
        $personalization_text = null;
    }
    
    // Validate product
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }
    
    // Validate quantity
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Get product details
    $stmt = $pdo->prepare("SELECT id, price, category_id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Get applicable discount for this product
    $discount = get_product_discount($pdo, $product_id, $product['category_id']);
    $discount_percent = $discount ? $discount['discount_percent'] : 0;
    $discounted_price = $discount ? $product['price'] * (1 - $discount_percent / 100) : $product['price'];
    
    // Find variant_id if color/size provided
    $variant_id = null;
    if (!empty($color) || !empty($size)) {
        $stmt = $pdo->prepare("SELECT id, stock_quantity FROM product_variants WHERE product_id = ? AND color = ? AND size = ?");
        $stmt->execute([$product_id, $color, $size]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($variant) {
            $variant_id = $variant['id'];
            
            // Check stock
            if ($variant['stock_quantity'] < $quantity) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit;
            }
        } else {
            // Variant not found - check if product has variants at all
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $has_variants = $stmt->fetchColumn() > 0;
            
            if ($has_variants) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Please select a color/size']);
                exit;
            }
        }
    }
    
    // Determine cart identifier (user_id or session_id)
    $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
    $session_id = $user_id ? null : $_SESSION['cart_session_id'];
    
    // Check if item already exists in cart
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND variant_id = ?");
        $stmt->execute([$user_id, $product_id, $variant_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE session_id = ? AND product_id = ? AND variant_id = ?");
        $stmt->execute([$session_id, $product_id, $variant_id]);
    }
    
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_item) {
        // Update quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        
        // Check stock again if variant
        if ($variant_id) {
            $stmt = $pdo->prepare("SELECT stock_quantity FROM product_variants WHERE id = ?");
            $stmt->execute([$variant_id]);
            $stock = $stmt->fetchColumn();
            
            if ($stock < $new_quantity) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        // Insert new item with discounted price
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, session_id, product_id, variant_id, quantity, discounted_price, discount_percent, personalization_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $session_id, $product_id, $variant_id, $quantity, $discounted_price, $discount_percent, $personalization_text]);
    }
    
    // Get updated cart count
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    
    $cart_count = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
