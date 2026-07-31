<?php
/**
 * Get Cart Data Endpoint
 * Returns current cart items as JSON for the current session/user
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Determine cart identifier (user_id or session_id)
    $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
    $session_id = $user_id ? null : ($_SESSION['cart_session_id'] ?? null);
    
    if (!$user_id && !$session_id) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'subtotal' => 0,
            'cart_count' => 0
        ]);
        exit;
    }
    
    // Get cart items with product and variant details
    if ($user_id) {
        $sql = "
            SELECT 
                ci.id as cart_item_id,
                ci.quantity,
                p.id as product_id,
                p.naam as product_name,
                p.price,
                p.image_path,
                pv.id as variant_id,
                pv.color,
                pv.size
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_variants pv ON ci.variant_id = pv.id
            WHERE ci.user_id = ?
            ORDER BY ci.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $sql = "
            SELECT 
                ci.id as cart_item_id,
                ci.quantity,
                p.id as product_id,
                p.naam as product_name,
                p.price,
                p.image_path,
                pv.id as variant_id,
                pv.color,
                pv.size
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_variants pv ON ci.variant_id = pv.id
            WHERE ci.session_id = ?
            ORDER BY ci.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id]);
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $subtotal = 0;
    $cart_count = 0;
    
    foreach ($items as &$item) {
        $item['line_total'] = $item['price'] * $item['quantity'];
        $subtotal += $item['line_total'];
        $cart_count += $item['quantity'];
        
        // Build image URL
        if ($item['image_path']) {
            $item['image_url'] = PUBLIC_URL . '/uploads/' . $item['image_path'];
        } else {
            $item['image_url'] = PUBLIC_URL . '/img/placeholder.jpg';
        }
        
        // Format price
        $item['price_formatted'] = format_price($item['price']);
        $item['line_total_formatted'] = format_price($item['line_total']);
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'subtotal' => $subtotal,
        'subtotal_formatted' => format_price($subtotal),
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
