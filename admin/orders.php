<?php
/**
 * Clicks Leather — Orders Management
 */
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Verify admin access
require_admin();

// Handle status update (MUST be before header include)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $valid_statuses = ['pending_payment', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        set_flash_message('success', 'Order status updated successfully.');
        header('Location: ' . ADMIN_URL . '/orders.php');
        exit;
    }
}

$page_title = 'Orders';
require_once __DIR__ . '/includes/header.php';

// Get view parameter (detail view)
$view_order_id = isset($_GET['view']) ? intval($_GET['view']) : null;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build base query
$sql = "SELECT o.* FROM orders o WHERE 1=1";
$params = [];

// Apply status filter
if (!empty($status_filter)) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

// Apply search filter
if (!empty($search_query)) {
    $sql .= " AND (o.id LIKE ? OR o.full_name LIKE ? OR o.email LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Get total count for pagination
$count_sql = str_replace('SELECT o.*', 'SELECT COUNT(*)', $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Get orders with pagination
$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// If viewing specific order details
$order_detail = null;
$order_items = null;
if ($view_order_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$view_order_id]);
    $order_detail = $stmt->fetch();
    
    if ($order_detail) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.naam as product_name, pv.size, pv.color 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$view_order_id]);
        $order_items = $stmt->fetchAll();
    }
}
?>

<?php if ($view_order_id && $order_detail): ?>
    <!-- Order Detail View -->
    <div class="dashboard-section">
        <div class="section-header">
            <a href="<?= ADMIN_URL ?>/orders.php" class="btn btn-outline btn-sm" id="back-to-orders">
                ← Back to Orders
            </a>
            <h3 class="section-title">Order #<?= $order_detail['id'] ?></h3>
        </div>

        <div class="order-detail-grid">
            <!-- Order Info -->
            <div class="order-detail-card">
                <h4 class="card-title">Order Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">#<?= $order_detail['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?= $order_detail['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $order_detail['status'])) ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?= date('M d, Y g:i A', strtotime($order_detail['created_at'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value"><?= format_price($order_detail['total_amount']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Shipping Cost:</span>
                    <span class="detail-value"><?= format_price($order_detail['shipping_cost']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Shipping Method:</span>
                    <span class="detail-value"><?= ucfirst($order_detail['shipping_method']) ?></span>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="order-detail-card">
                <h4 class="card-title">Customer Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['full_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['phone']) ?></span>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="order-detail-card">
                <h4 class="card-title">Shipping Address</h4>
                <div class="detail-row">
                    <span class="detail-label">Address Line 1:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['address_line1']) ?></span>
                </div>
                <?php if ($order_detail['address_line2']): ?>
                <div class="detail-row">
                    <span class="detail-label">Address Line 2:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['address_line2']) ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">City:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['city']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">State/Province:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['state']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Country:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['country']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Postal Code:</span>
                    <span class="detail-value"><?= htmlspecialchars($order_detail['postal_code']) ?></span>
                </div>
            </div>
        </div>

        <!-- Update Status -->
        <div class="order-detail-card" style="margin-top: 1.5rem;">
            <h4 class="card-title">Update Order Status</h4>
            <form method="POST" class="status-update-form" id="status-update-form">
                <input type="hidden" name="order_id" value="<?= $order_detail['id'] ?>">
                <div class="form-group">
                    <label for="status">New Status:</label>
                    <select name="status" id="status" required>
                        <option value="pending_payment" <?= $order_detail['status'] === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                        <option value="pending" <?= $order_detail['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $order_detail['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $order_detail['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $order_detail['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $order_detail['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary" id="update-status-btn">
                    Update Status
                </button>
            </form>
        </div>

        <!-- Order Items -->
        <div class="order-detail-card" style="margin-top: 1.5rem;">
            <h4 class="card-title">Order Items</h4>
            <?php if (empty($order_items)): ?>
                <p class="empty-state-text">No items found for this order.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table" id="order-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variant</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= htmlspecialchars($item['color'] ?? 'N/A') ?>
                                        <?php if ($item['size']): ?>
                                            / <?= htmlspecialchars($item['size']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= format_price($item['price_at_order']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Order Summary -->
                <div class="order-summary" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Subtotal</span>
                        <span><?= format_price(array_sum(array_map(fn($item) => $item['price_at_order'] * $item['quantity'], $order_items))) ?></span>
                    </div>
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Shipping</span>
                        <span><?= format_price($order_detail['shipping_cost']) ?></span>
                    </div>
                    <div class="summary-row total" style="display: flex; justify-content: space-between; font-weight: 600; font-size: 1rem;">
                        <span>Total</span>
                        <span><?= format_price($order_detail['total_amount']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Orders List View -->
    <!-- Toolbar -->
    <div class="toolbar">
        <div class="toolbar-left">
            <span class="product-count"><?= $total_orders ?> order<?= $total_orders !== 1 ? 's' : '' ?></span>
            
            <form method="GET" class="filter-form" id="filter-form">
                <select name="status" onchange="this.form.submit()" id="status-filter">
                    <option value="">All Statuses</option>
                    <option value="pending_payment" <?= $status_filter === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </form>
        </div>

        <div class="toolbar-right">
            <form method="GET" class="search-form" id="search-form" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" name="search" placeholder="Search order #, name, or email..." 
                       value="<?= htmlspecialchars($search_query) ?>" id="search-input"
                       style="padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; min-width: 250px;">
                <button type="submit" class="btn btn-primary btn-sm" id="search-btn">Search</button>
                <?php if (!empty($search_query)): ?>
                    <a href="<?= ADMIN_URL ?>/orders.php" class="btn btn-outline btn-sm" id="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            <h3>No Orders Found</h3>
            <p>Orders will appear here once customers start placing them.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" id="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th width="80">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                            <td><?= htmlspecialchars($order['email']) ?></td>
                            <td><?= htmlspecialchars($order['phone']) ?></td>
                            <td><?= format_price($order['total_amount']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            <td>
                                <a href="<?= ADMIN_URL ?>/orders.php?view=<?= $order['id'] ?>" 
                                   class="btn btn-outline btn-sm" id="view-order-<?= $order['id'] ?>">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= ADMIN_URL ?>/orders.php?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= htmlspecialchars($search_query) ?>" 
                       class="pagination-link" id="prev-page">← Previous</a>
                <?php endif; ?>
                
                <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?= ADMIN_URL ?>/orders.php?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= htmlspecialchars($search_query) ?>" 
                       class="pagination-link" id="next-page">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
