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

// Handle hero settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_hero_settings']) || isset($_POST['remove_slide']))) {
    $hero_subtitle = $_POST['hero_subtitle'] ?? '';
    $hero_title = $_POST['hero_title'] ?? '';
    $hero_description = $_POST['hero_description'] ?? '';
    
    // Update hero text settings
    update_setting($pdo, 'hero_subtitle', $hero_subtitle);
    update_setting($pdo, 'hero_title', $hero_title);
    update_setting($pdo, 'hero_description', $hero_description);
    
    // Handle slide removal (do this before uploads to avoid conflict)
    if (isset($_POST['remove_slide'])) {
        $remove_slide = intval($_POST['remove_slide']);
        $pdo->prepare("DELETE FROM hero_slides WHERE sort_order = ?")->execute([$remove_slide]);
    }
    
    // Handle hero slide image uploads
    for ($i = 1; $i <= 3; $i++) {
        $file_key = "hero_slide_{$i}";
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $result = upload_media($_FILES[$file_key]);
            if ($result['success']) {
                // Delete old slide if exists
                $pdo->prepare("DELETE FROM hero_slides WHERE sort_order = ?")->execute([$i]);
                // Insert new slide
                $stmt = $pdo->prepare("INSERT INTO hero_slides (image_path, sort_order) VALUES (?, ?)");
                $stmt->execute([$result['filename'], $i]);
            }
        }
    }
    
    set_flash_message('success', 'Hero settings updated successfully.');
    header('Location: ' . ADMIN_URL . '/settings.php');
    exit;
}

$page_title = 'Settings';
require_once __DIR__ . '/includes/header.php';

// Get current settings
$shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
$shipping_flat_cost = get_setting($pdo, 'shipping_flat_cost', '15.00');

// Get hero settings
$hero_subtitle = get_setting($pdo, 'hero_subtitle', 'Handcrafted Excellence');
$hero_title = get_setting($pdo, 'hero_title', 'Premium Leather, Timeless Craft');
$hero_description = get_setting($pdo, 'hero_description', 'Discover our collection of handcrafted leather goods...');

// Get hero slides
$hero_slides_stmt = $pdo->query("SELECT * FROM hero_slides ORDER BY sort_order");
$hero_slides = $hero_slides_stmt->fetchAll(PDO::FETCH_ASSOC);
$hero_slides_by_order = [];
foreach ($hero_slides as $slide) {
    $hero_slides_by_order[$slide['sort_order']] = $slide;
}
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

<!-- Homepage Hero Settings -->
<div class="dashboard-section">
    <h3 class="section-title">Homepage Hero Settings</h3>
    
    <div class="form-card">
        <form method="POST" id="hero-settings-form" enctype="multipart/form-data">
            
            <!-- Hero Text -->
            <div class="form-section-title">Hero Text</div>
            <div class="form-group">
                <label for="hero_subtitle">Hero Subtitle</label>
                <input type="text" name="hero_subtitle" id="hero_subtitle" 
                       value="<?= htmlspecialchars($hero_subtitle) ?>" 
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem;">
            </div>
            <div class="form-group">
                <label for="hero_title">Hero Title</label>
                <input type="text" name="hero_title" id="hero_title" 
                       value="<?= htmlspecialchars($hero_title) ?>" 
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem;">
            </div>
            <div class="form-group">
                <label for="hero_description">Hero Description</label>
                <textarea name="hero_description" id="hero_description" rows="3"
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($hero_description) ?></textarea>
            </div>
            
            <!-- Hero Slides -->
            <div class="form-section-title">Hero Slides (3 images)</div>
            
            <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="form-group">
                    <label>Slide <?= $i ?> Image</label>
                    <div class="image-upload-area" style="border: 2px dashed var(--border-color); border-radius: var(--radius-sm); padding: 1rem; text-align: center;">
                        <input type="file" name="hero_slide_<?= $i ?>" accept="image/*" style="margin-bottom: 0.5rem;">
                        <?php if (isset($hero_slides_by_order[$i])): ?>
                            <div class="image-preview" style="margin-top: 0.5rem; position: relative; display: inline-block;">
                                <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($hero_slides_by_order[$i]['image_path']) ?>" 
                                     alt="Slide <?= $i ?>" 
                                     style="max-width: 200px; max-height: 150px; border-radius: var(--radius-sm);">
                                <button type="submit" name="remove_slide" value="<?= $i ?>" 
                                        class="btn btn-danger" 
                                        style="position: absolute; top: 5px; right: 5px; padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--color-error, #e63946); color: white; border: none; border-radius: var(--radius-sm); cursor: pointer;">
                                    ×
                                </button>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">No image uploaded</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
            
            <div class="form-actions" style="margin-top: var(--space-xl); padding-top: var(--space-xl); border-top: 1px solid var(--border-color);">
                <button type="submit" name="save_hero_settings" class="btn btn-primary">
                    Save Hero Settings
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
