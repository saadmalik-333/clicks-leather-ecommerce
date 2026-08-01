<?php
/**
 * Product Search API
 * Returns JSON with matching products based on search query
 */
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

// Split query into words and filter empty
$words = array_filter(explode(' ', trim($query)));
if (empty($words)) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

// Build LIKE conditions for each word
$conditions = [];
$params = [];
foreach ($words as $word) {
    $conditions[] = 'naam LIKE ?';
    $params[] = '%' . $word . '%';
}

$whereClause = implode(' OR ', $conditions);

// Search products
$sql = "
    SELECT id, naam, image_path, price
    FROM products
    WHERE $whereClause
    ORDER BY naam ASC
    LIMIT 20
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format results
    $results = [];
    foreach ($products as $product) {
        $results[] = [
            'id' => $product['id'],
            'name' => $product['naam'],
            'image' => $product['image_path'] ? PUBLIC_URL . '/uploads/' . $product['image_path'] : null,
            'price' => format_price($product['price']),
            'url' => PUBLIC_URL . '/product-detail.php?id=' . $product['id']
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
