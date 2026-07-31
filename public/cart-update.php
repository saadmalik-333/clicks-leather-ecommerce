<?php
/**
 * Update Cart Quantity Endpoint
 * Updates quantity for a cart item (or removes if quantity set to 0)
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $cart_item_id = intval($input['cart_item_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);
    
    // Validate cart item
    if ($cart_item_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
        exit;
    }
    
    // Determine cart identifier (user_id or session_id)
    $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
    $session_id = $user_id ? null : ($_SESSION['cart_session_id'] ?? null);
    
    if (!$user_id && !$session_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No cart session']);
        exit;
    }
    
    // Get cart item details
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT ci.id, ci.quantity, ci.variant_id, pv.stock_quantity FROM cart_items ci LEFT JOIN product_variants pv ON ci.variant_id = pv.id WHERE ci.id = ? AND ci.user_id = ?");
        $stmt->execute([$cart_item_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT ci.id, ci.quantity, ci.variant_id, pv.stock_quantity FROM cart_items ci LEFT JOIN product_variants pv ON ci.variant_id = pv.id WHERE ci.id = ? AND ci.session_id = ?");
        $stmt->execute([$cart_item_id, $session_id]);
    }
    
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart_item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }
    
    // If quantity is 0, remove item
    if ($quantity <= 0) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->execute([$cart_item_id]);
    } else {
        // Check stock if variant
        if ($cart_item['variant_id'] && $cart_item['stock_quantity'] < $quantity) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit;
        }
        
        // Update quantity
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$quantity, $cart_item_id]);
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
        'message' => 'Cart updated',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
