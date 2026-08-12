<?php
/**
 * Clicks Leather — Admin Low Stock Page
 */
$page_title = 'Low Stock';
require_once __DIR__ . '/includes/header.php';

// Fetch low stock products (stock <= 5)
$low_stock = $pdo->query(
    "SELECT p.naam, c.naam as category_name, pv.size, pv.color, pv.stock_quantity 
     FROM product_variants pv 
     JOIN products p ON pv.product_id = p.id 
     JOIN categories c ON p.category_id = c.id 
     WHERE pv.stock_quantity <= 5 
     ORDER BY pv.stock_quantity ASC"
)->fetchAll();
?>

<div class="dashboard-section">
    <h3 class="section-title">Low Stock Products</h3>
    <?php if (empty($low_stock)): ?>
        <div class="empty-state">
            <p>No products with low stock. All variants have more than 5 units in stock.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" id="low-stock-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['naam']) ?></td>
                            <td><?= htmlspecialchars($item['category_name']) ?></td>
                            <td><?= htmlspecialchars($item['size'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['color'] ?? '—') ?></td>
                            <td>
                                <span class="stock-warning"><?= $item['stock_quantity'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
