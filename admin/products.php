<?php
/**
 * Clicks Leather — All Products
 */
$page_title = 'All Products';
require_once __DIR__ . '/includes/header.php';

// Get filter
$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Build query with optional category filter
$sql = "SELECT p.*, c.naam as category_naam,
        (SELECT SUM(pv.stock_quantity) FROM product_variants pv WHERE pv.product_id = p.id) as total_stock
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";

$params = [];

if ($filter_category > 0) {
    $sql .= " WHERE p.category_id = :category_id";
    $params[':category_id'] = $filter_category;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = get_all_categories($pdo);
?>

<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-left">
        <span class="product-count"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?></span>
        
        <form method="GET" class="filter-form" id="filter-form">
            <select name="category" onchange="this.form.submit()" id="category-filter">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['naam']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="toolbar-right">
        <a href="<?= ADMIN_URL ?>/add_product.php" class="btn btn-primary btn-sm" id="toolbar-add-product">
            + Add Product
        </a>
    </div>
</div>

<!-- Products Table -->
<?php if (empty($products)): ?>
    <div class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
            <line x1="7" y1="7" x2="7.01" y2="7"/>
        </svg>
        <h3>No Products Found</h3>
        <p>Start adding products to your store.</p>
        <a href="<?= ADMIN_URL ?>/add_product.php" class="btn btn-primary">Add First Product</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="data-table" id="products-table">
            <thead>
                <tr>
                    <th width="60">Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Personalization</th>
                    <th>Created</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image_path']): ?>
                                <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($product['naam']) ?>" 
                                     class="table-product-img">
                            <?php else: ?>
                                <div class="table-product-placeholder">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($product['naam']) ?></strong>
                        </td>
                        <td>
                            <span class="category-tag"><?= htmlspecialchars($product['category_naam']) ?></span>
                        </td>
                        <td><?= format_price($product['price']) ?></td>
                        <td>
                            <?php 
                            $stock = $product['total_stock'] ?? 0;
                            $stock_class = $stock <= 5 ? 'stock-low' : ($stock <= 20 ? 'stock-medium' : 'stock-good');
                            ?>
                            <span class="<?= $stock_class ?>"><?= $stock ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $product['has_personalization'] === 'yes' ? 'success' : 'default' ?>">
                                <?= ucfirst($product['has_personalization']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($product['created_at'])) ?></td>
                        <td class="actions-cell">
                            <div class="actions-wrapper">
                                <a href="<?= ADMIN_URL ?>/edit_product.php?id=<?= $product['id'] ?>" 
                                   class="btn-action btn-edit" title="Edit" id="edit-product-<?= $product['id'] ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="<?= ADMIN_URL ?>/delete_product.php" class="inline-form delete-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Delete" 
                                            id="delete-product-<?= $product['id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this product?');">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
