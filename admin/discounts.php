<?php
/**
 * Clicks Leather — Discounts Management
 */
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Verify admin access
require_admin();

// Handle POST requests (MUST be before header include)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Update discount
    if (isset($_POST['save_discount'])) {
        $discount_id = isset($_POST['discount_id']) ? intval($_POST['discount_id']) : null;
        $type = $_POST['type'] ?? 'sitewide';
        
        // Get target_id based on type
        if ($type === 'category') {
            $target_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        } elseif ($type === 'product') {
            $target_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        } else {
            $target_id = null;
        }
        
        $discount_percent = floatval($_POST['discount_percent'] ?? 0);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        // Validate
        if ($discount_percent < 0 || $discount_percent > 100) {
            set_flash_message('error', 'Discount percent must be between 0 and 100.');
            header('Location: ' . ADMIN_URL . '/discounts.php');
            exit;
        }
        
        if ($type !== 'sitewide' && empty($target_id)) {
            set_flash_message('error', 'Target is required for category and product discounts.');
            header('Location: ' . ADMIN_URL . '/discounts.php');
            exit;
        }
        
        // Validate date range
        if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
            set_flash_message('error', 'Start date must be before or equal to end date.');
            header('Location: ' . ADMIN_URL . '/discounts.php');
            exit;
        }
        
        if ($discount_id) {
            // Update existing discount
            $stmt = $pdo->prepare("
                UPDATE discounts 
                SET type = ?, target_id = ?, discount_percent = ?, 
                    start_date = ?, end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$type, $target_id, $discount_percent, $start_date, $end_date, $discount_id]);
            set_flash_message('success', 'Discount updated successfully.');
        } else {
            // Insert new discount
            $stmt = $pdo->prepare("
                INSERT INTO discounts (type, target_id, discount_percent, start_date, end_date, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$type, $target_id, $discount_percent, $start_date, $end_date]);
            set_flash_message('success', 'Discount created successfully.');
        }
        
        header('Location: ' . ADMIN_URL . '/discounts.php');
        exit;
    }
    
    // Delete discount
    if (isset($_POST['delete_discount'])) {
        $discount_id = intval($_POST['discount_id']);
        $stmt = $pdo->prepare("DELETE FROM discounts WHERE id = ?");
        $stmt->execute([$discount_id]);
        set_flash_message('success', 'Discount deleted successfully.');
        header('Location: ' . ADMIN_URL . '/discounts.php');
        exit;
    }
    
    // Toggle active status
    if (isset($_POST['toggle_discount'])) {
        $discount_id = intval($_POST['discount_id']);
        $is_active = intval($_POST['is_active']);
        $stmt = $pdo->prepare("UPDATE discounts SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $discount_id]);
        set_flash_message('success', 'Discount status updated successfully.');
        header('Location: ' . ADMIN_URL . '/discounts.php');
        exit;
    }
}

// Get edit parameter
$edit_discount_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
$edit_discount = null;

if ($edit_discount_id) {
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE id = ?");
    $stmt->execute([$edit_discount_id]);
    $edit_discount = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all discounts
$stmt = $pdo->query("
    SELECT d.*, 
           CASE 
               WHEN d.is_active = 0 THEN 'Inactive'
               WHEN d.start_date IS NOT NULL AND d.start_date > CURDATE() THEN 'Scheduled'
               WHEN d.end_date IS NOT NULL AND d.end_date < CURDATE() THEN 'Expired'
               ELSE 'Active'
           END as computed_status,
           CASE 
               WHEN d.type = 'sitewide' THEN 'Sitewide'
               WHEN d.type = 'category' THEN (SELECT naam FROM categories WHERE id = d.target_id)
               WHEN d.type = 'product' THEN (SELECT naam FROM products WHERE id = d.target_id)
           END as target_name
    FROM discounts d 
    ORDER BY d.created_at DESC
");
$discounts = $stmt->fetchAll();

// Get categories for dropdown
$categories = get_all_categories($pdo);

// Get products for dropdown
$products_stmt = $pdo->query("SELECT id, naam, price FROM products ORDER BY naam ASC");
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Discounts';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Discounts Page -->
<div class="dashboard-section">
    <h3 class="section-title">Discounts Management</h3>
    
    <!-- Add/Edit Discount Form -->
    <div class="form-card" style="margin-bottom: var(--space-xl);">
        <h4 class="form-section-title"><?= $edit_discount ? 'Edit Discount' : 'Add New Discount' ?></h4>
        
        <form method="POST" id="discount-form">
            <?php if ($edit_discount): ?>
                <input type="hidden" name="discount_id" value="<?= $edit_discount['id'] ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Discount Type</label>
                    <select name="type" id="type" required onchange="updateTargetField()">
                        <option value="sitewide" <?= $edit_discount && $edit_discount['type'] === 'sitewide' ? 'selected' : '' ?>>Sitewide</option>
                        <option value="category" <?= $edit_discount && $edit_discount['type'] === 'category' ? 'selected' : '' ?>>Category</option>
                        <option value="product" <?= $edit_discount && $edit_discount['type'] === 'product' ? 'selected' : '' ?>>Product</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="discount_percent">Discount Percent (%)</label>
                    <input type="number" name="discount_percent" id="discount_percent" 
                           value="<?= $edit_discount ? htmlspecialchars($edit_discount['discount_percent']) : '' ?>" 
                           step="0.01" min="0" max="100" required>
                </div>
            </div>
            
            <div class="form-group" id="category-field" style="display: <?= $edit_discount && $edit_discount['type'] === 'category' ? 'block' : 'none' ?>;">
                <label for="category-select">Category</label>
                <select name="category_id" id="category-select">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $edit_discount && $edit_discount['type'] === 'category' && $edit_discount['target_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['naam']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="product-field" style="display: <?= $edit_discount && $edit_discount['type'] === 'product' ? 'block' : 'none' ?>;">
                <label for="product-select">Product</label>
                <select name="product_id" id="product-select">
                    <option value="">Select a product</option>
                    <?php foreach ($products as $prod): ?>
                        <option value="<?= $prod['id'] ?>" <?= $edit_discount && $edit_discount['type'] === 'product' && $edit_discount['target_id'] == $prod['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prod['naam']) ?> — <?= format_price($prod['price']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date (Optional)</label>
                    <input type="date" name="start_date" id="start_date" 
                           value="<?= $edit_discount && $edit_discount['start_date'] ? $edit_discount['start_date'] : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date (Optional)</label>
                    <input type="date" name="end_date" id="end_date" 
                           value="<?= $edit_discount && $edit_discount['end_date'] ? $edit_discount['end_date'] : '' ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_discount" class="btn btn-primary">
                    <?= $edit_discount ? 'Update Discount' : 'Create Discount' ?>
                </button>
                <?php if ($edit_discount): ?>
                    <a href="<?= ADMIN_URL ?>/discounts.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Discounts List -->
    <?php if (empty($discounts)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            <h3>No Discounts Found</h3>
            <p>Create your first discount to start offering promotions.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" id="discounts-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Target</th>
                        <th>Discount</th>
                        <th>Date Range</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td>
                                <span class="badge badge-default">
                                    <?= ucfirst($discount['type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($discount['target_name'] ?? 'N/A') ?></td>
                            <td><strong><?= number_format($discount['discount_percent'], 2) ?>%</strong></td>
                            <td>
                                <?php if ($discount['start_date'] || $discount['end_date']): ?>
                                    <?= $discount['start_date'] ? date('M d, Y', strtotime($discount['start_date'])) : 'Any' ?>
                                    to
                                    <?= $discount['end_date'] ? date('M d, Y', strtotime($discount['end_date'])) : 'Any' ?>
                                <?php else: ?>
                                    Always active
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($discount['computed_status']) ?>">
                                    <?= htmlspecialchars($discount['computed_status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions-wrapper">
                                    <form method="POST" action="<?= ADMIN_URL ?>/discounts.php" style="display: inline;">
                                        <input type="hidden" name="discount_id" value="<?= $discount['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $discount['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_discount" class="btn-action" title="<?= $discount['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: <?= $discount['is_active'] ? '#22c55e' : '#ef4444' ?>;"></span>
                                        </button>
                                    </form>
                                    <a href="<?= ADMIN_URL ?>/discounts.php?edit=<?= $discount['id'] ?>" class="btn-action btn-edit" title="Edit" style="display: inline-flex; align-items: center; justify-content: center;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="<?= ADMIN_URL ?>/discounts.php" style="display: inline;">
                                        <input type="hidden" name="discount_id" value="<?= $discount['id'] ?>">
                                        <button type="submit" name="delete_discount" class="btn-action btn-delete" title="Delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
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
</div>

<script>
function updateTargetField() {
    const type = document.getElementById('type').value;
    const categoryField = document.getElementById('category-field');
    const productField = document.getElementById('product-field');
    
    categoryField.style.display = type === 'category' ? 'block' : 'none';
    productField.style.display = type === 'product' ? 'block' : 'none';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTargetField();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
