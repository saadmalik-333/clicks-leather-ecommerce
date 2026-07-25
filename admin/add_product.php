<?php
/**
 * Clicks Leather — Add Product
 */
$page_title = 'Add Product';
require_once __DIR__ . '/includes/header.php';

// Fetch categories for dropdown
$categories = get_all_categories($pdo);

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Collect product data
        $naam = sanitize_input($_POST['naam'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $has_personalization = ($_POST['has_personalization'] ?? 'no') === 'yes' ? 'yes' : 'no';

        // Validate
        if (empty($naam)) $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        if ($category_id <= 0) $errors[] = 'Please select a category.';

        // Handle image upload
        $image_filename = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['product_image']);
            if ($upload_result['success']) {
                $image_filename = $upload_result['filename'];
            } else {
                $errors[] = $upload_result['message'];
            }
        }

        // If no errors, insert product
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert product
                $stmt = $pdo->prepare(
                    "INSERT INTO products (naam, description, price, category_id, has_personalization, image_path) 
                     VALUES (:naam, :description, :price, :category_id, :has_personalization, :image_path)"
                );
                $stmt->execute([
                    ':naam'                => $naam,
                    ':description'         => $description,
                    ':price'               => $price,
                    ':category_id'         => $category_id,
                    ':has_personalization'  => $has_personalization,
                    ':image_path'          => $image_filename
                ]);

                $product_id = $pdo->lastInsertId();

                // Insert variants if provided
                $sizes  = $_POST['variant_size'] ?? [];
                $colors = $_POST['variant_color'] ?? [];
                $stocks = $_POST['variant_stock'] ?? [];

                $variant_stmt = $pdo->prepare(
                    "INSERT INTO product_variants (product_id, size, color, stock_quantity) 
                     VALUES (:product_id, :size, :color, :stock)"
                );

                for ($i = 0; $i < count($sizes); $i++) {
                    $size  = sanitize_input($sizes[$i] ?? '');
                    $color = sanitize_input($colors[$i] ?? '');
                    $stock = intval($stocks[$i] ?? 0);

                    // Only insert if at least one field has data
                    if (!empty($size) || !empty($color)) {
                        $variant_stmt->execute([
                            ':product_id' => $product_id,
                            ':size'       => $size ?: null,
                            ':color'      => $color ?: null,
                            ':stock'      => $stock
                        ]);
                    }
                }

                $pdo->commit();
                set_flash_message('success', "Product '{$naam}' added successfully!");
                redirect(ADMIN_URL . '/products.php');

            } catch (Exception $e) {
                $pdo->rollBack();
                // Delete uploaded image if DB insert failed
                if ($image_filename) {
                    delete_image($image_filename);
                }
                $errors[] = 'Failed to add product. Please try again.';
                error_log("Add Product Error: " . $e->getMessage());
            }
        }
    }
}
?>

<div class="form-card">
    <?php if (!empty($errors)): ?>
        <div class="flash-message flash-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="product-form" id="add-product-form">
        <?= csrf_field() ?>

        <div class="form-grid">
            <!-- Left Column: Product Details -->
            <div class="form-column">
                <h3 class="form-section-title">Product Details</h3>

                <div class="form-group">
                    <label for="naam">Product Name <span class="required">*</span></label>
                    <input type="text" id="naam" name="naam" 
                           value="<?= htmlspecialchars($_POST['naam'] ?? '') ?>" 
                           placeholder="e.g., Classic Leather Wallet" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" 
                              placeholder="Describe the product..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (Rs.) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" 
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" 
                               placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" 
                                    data-category="<?= htmlspecialchars($cat['naam']) ?>"
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['naam']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Personalization Available?</label>
                    <div class="toggle-group">
                        <label class="toggle-label">
                            <input type="radio" name="has_personalization" value="no" 
                                   <?= ($_POST['has_personalization'] ?? 'no') === 'no' ? 'checked' : '' ?>>
                            <span class="toggle-btn">No</span>
                        </label>
                        <label class="toggle-label">
                            <input type="radio" name="has_personalization" value="yes"
                                   <?= ($_POST['has_personalization'] ?? '') === 'yes' ? 'checked' : '' ?>>
                            <span class="toggle-btn">Yes</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Column: Image & Variants -->
            <div class="form-column">
                <h3 class="form-section-title">Product Image</h3>

                <div class="form-group">
                    <label for="product_image">Upload Image (JPG, PNG — max 2MB)</label>
                    <div class="image-upload-area" id="image-upload-area">
                        <input type="file" id="product_image" name="product_image" 
                               accept=".jpg,.jpeg,.png" class="file-input">
                        <div class="upload-placeholder" id="upload-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/>
                            </svg>
                            <p>Click to upload or drag & drop</p>
                            <span>JPG, JPEG, PNG (max 2MB)</span>
                        </div>
                        <img id="image-preview" class="image-preview" style="display:none;" alt="Preview">
                    </div>
                </div>

                <h3 class="form-section-title">Variants (Size / Color / Stock)</h3>
                <p class="form-hint" id="variant-hint">Add product variants below. Size fields are shown for Shoes, Jackets, and similar categories.</p>

                <div id="variants-container">
                    <!-- Default variant row -->
                    <div class="variant-row" id="variant-row-0">
                        <div class="variant-field size-field">
                            <label>Size</label>
                            <input type="text" name="variant_size[]" placeholder="e.g., M, L, 42">
                        </div>
                        <div class="variant-field">
                            <label>Color</label>
                            <input type="text" name="variant_color[]" placeholder="e.g., Brown">
                        </div>
                        <div class="variant-field">
                            <label>Stock</label>
                            <input type="number" name="variant_stock[]" min="0" value="0">
                        </div>
                        <button type="button" class="btn-remove-variant" onclick="removeVariant(this)" title="Remove variant">×</button>
                    </div>
                </div>

                <button type="button" class="btn btn-outline btn-sm" id="add-variant-btn" onclick="addVariant()">
                    + Add Another Variant
                </button>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="submit-product-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
                Add Product
            </button>
            <a href="<?= ADMIN_URL ?>/products.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
