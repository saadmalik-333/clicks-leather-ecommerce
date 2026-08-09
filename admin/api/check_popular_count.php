<?php
/**
 * Clicks Leather — API: Check Popular Products Count
 * Returns the count of products marked as popular (excluding a specific product ID)
 */
require_once __DIR__ . '/../../includes/db_connect.php';

header('Content-Type: application/json');

$exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;

try {
    $sql = "SELECT COUNT(*) as count FROM products WHERE is_popular = 1";
    $params = [];
    
    if ($exclude_id > 0) {
        $sql .= " AND id != :exclude_id";
        $params[':exclude_id'] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    echo json_encode(['count' => (int)$result['count']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to check popular count']);
}
