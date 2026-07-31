<?php
/**
 * Clicks Leather — Settings Management
 */
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Verify admin access
require_admin();

// Handle settings update (MUST be before header include)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $shipping_is_free = $_POST['shipping_is_free'] ?? 'yes';
    $shipping_flat_cost = $_POST['shipping_flat_cost'] ?? '15.00';
    
    // Validate shipping cost
    $shipping_flat_cost = number_format(floatval($shipping_flat_cost), 2, '.', '');
    
    // Update settings
    update_setting($pdo, 'shipping_is_free', $shipping_is_free);
    update_setting($pdo, 'shipping_flat_cost', $shipping_flat_cost);
    
    set_flash_message('success', 'Settings updated successfully.');
    header('Location: ' . ADMIN_URL . '/settings.php');
    exit;
}

$page_title = 'Settings';
require_once __DIR__ . '/includes/header.php';

// Get current settings
$shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
$shipping_flat_cost = get_setting($pdo, 'shipping_flat_cost', '15.00');
?>

<!-- Settings Page -->
<div class="dashboard-section">
    <h3 class="section-title">Shipping Settings</h3>
    
    <div class="form-card">
        <form method="POST" id="settings-form">
            <div class="form-section-title">Shipping Method</div>
            
            <div class="toggle-group" style="margin-bottom: var(--space-lg);">
                <label class="toggle-label">
                    <input type="radio" name="shipping_is_free" value="yes" <?= $shipping_is_free === 'yes' ? 'checked' : '' ?>>
                    <span class="toggle-btn">Free Shipping</span>
                </label>
                <label class="toggle-label">
                    <input type="radio" name="shipping_is_free" value="no" <?= $shipping_is_free === 'no' ? 'checked' : '' ?>>
                    <span class="toggle-btn">Flat Rate Shipping</span>
                </label>
            </div>
            
            <div class="form-group" id="shipping-cost-group" style="display: <?= $shipping_is_free === 'no' ? 'block' : 'none' ?>;">
                <label for="shipping_flat_cost">Shipping Cost (USD)</label>
                <input type="number" name="shipping_flat_cost" id="shipping_flat_cost" 
                       value="<?= htmlspecialchars($shipping_flat_cost) ?>" 
                       step="0.01" min="0" 
                       style="width: 200px; padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem;">
                <small style="color: var(--text-muted); display: block; margin-top: var(--space-xs);">
                    Enter the flat shipping cost in USD (e.g., 15.00)
                </small>
            </div>
            
            <div class="form-actions" style="margin-top: var(--space-xl); padding-top: var(--space-xl); border-top: 1px solid var(--border-color);">
                <button type="submit" name="save_settings" class="btn btn-primary" id="save-settings-btn">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle shipping cost input based on shipping method
document.addEventListener('DOMContentLoaded', function() {
    const shippingRadios = document.querySelectorAll('input[name="shipping_is_free"]');
    const shippingCostGroup = document.getElementById('shipping-cost-group');
    
    shippingRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'no') {
                shippingCostGroup.style.display = 'block';
            } else {
                shippingCostGroup.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
