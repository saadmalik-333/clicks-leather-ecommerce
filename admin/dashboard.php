<?php
/**
 * Clicks Leather — Admin Dashboard
 */
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// Fetch stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

// Fetch recent orders (last 10)
$recent_orders = $pdo->query(
    "SELECT o.*, u.naam as user_naam 
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.id 
     ORDER BY o.created_at DESC 
     LIMIT 10"
)->fetchAll();

// Fetch low stock products (stock <= 5)
$low_stock = $pdo->query(
    "SELECT p.naam, pv.size, pv.color, pv.stock_quantity 
     FROM product_variants pv 
     JOIN products p ON pv.product_id = p.id 
     WHERE pv.stock_quantity <= 5 
     ORDER BY pv.stock_quantity ASC 
     LIMIT 10"
)->fetchAll();
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card" id="stat-products">
        <div class="stat-icon stat-icon-blue">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= $total_products ?></h3>
            <p>Total Products</p>
        </div>
    </div>

    <div class="stat-card" id="stat-orders">
        <div class="stat-icon stat-icon-green">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= $total_orders ?></h3>
            <p>Total Orders</p>
        </div>
    </div>

    <div class="stat-card" id="stat-users">
        <div class="stat-icon stat-icon-purple">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/>
                <path d="M16 3.13a4 4 0 010 7.75"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= $total_users ?></h3>
            <p>Customers</p>
        </div>
    </div>

    <div class="stat-card" id="stat-revenue">
        <div class="stat-icon stat-icon-gold">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= format_price($total_revenue) ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="dashboard-section">
    <h3 class="section-title">Quick Actions</h3>
    <div class="quick-actions">
        <a href="<?= ADMIN_URL ?>/add_product.php" class="quick-action-btn" id="quick-add-product">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
            </svg>
            Add New Product
        </a>
        <a href="<?= ADMIN_URL ?>/products.php" class="quick-action-btn" id="quick-view-products">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            View All Products
        </a>
    </div>
</div>

<!-- Recent Orders -->
<div class="dashboard-section">
    <h3 class="section-title">Recent Orders</h3>
    <?php if (empty($recent_orders)): ?>
        <div class="empty-state">
            <p>No orders yet. They'll appear here once customers start ordering.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" id="recent-orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['user_naam']) ?></td>
                            <td><?= format_price($order['total_amount']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($low_stock)): ?>
<div class="dashboard-section">
    <h3 class="section-title">⚠️ Low Stock Alert</h3>
    <div class="table-responsive">
        <table class="data-table" id="low-stock-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['naam']) ?></td>
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
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
