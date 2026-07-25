<?php
/**
 * Clicks Leather — Edit Product
 */
ob_start();
$page_title = 'Edit Product';
require_once __DIR__ . '/includes/header.php';

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    set_flash_message('error', 'Invalid product ID.');
    redirect(ADMIN_URL . '/products.php');
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash_message('error', 'Product not found.');
    redirect(ADMIN_URL . '/products.php');
}

// Fetch variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY id ASC");
$stmt->execute([':product_id' => $product_id]);
$variants = $stmt->fetchAll();

// Fetch categories
$categories = get_all_categories($pdo);

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $naam = sanitize_input($_POST['naam'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $has_personalization = ($_POST['has_personalization'] ?? 'no') === 'yes' ? 'yes' : 'no';

        if (empty($naam)) $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        if ($category_id <= 0) $errors[] = 'Please select a category.';

        // Handle main image upload (optional on edit)
        $image_filename = $product['image_path']; // Keep existing by default
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['product_image']);
            if ($upload_result['success']) {
                // Delete old image
                if ($product['image_path']) {
                    delete_image($product['image_path']);
                }
                $image_filename = $upload_result['filename'];
            } else {
                $errors[] = $upload_result['message'];
            }
        }

        // Handle image removal
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if ($product['image_path']) {
                delete_image($product['image_path']);
            }
            $image_filename = null;
        }

        // Handle alternate image upload (optional on edit)
        $image_alt_filename = $product['image_path_alt'] ?? null; // Keep existing by default
        if (isset($_FILES['product_image_alt']) && $_FILES['product_image_alt']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['product_image_alt']);
            if ($upload_result['success']) {
                // Delete old alternate image
                if ($product['image_path_alt']) {
                    delete_image($product['image_path_alt']);
                }
                $image_alt_filename = $upload_result['filename'];
            } else {
                $errors[] = 'Alternate image: ' . $upload_result['message'];
            }
        }

        // Handle alternate image removal
        if (isset($_POST['remove_image_alt']) && $_POST['remove_image_alt'] === '1') {
            if ($product['image_path_alt']) {
                delete_image($product['image_path_alt']);
            }
            $image_alt_filename = null;
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update product
                $stmt = $pdo->prepare(
                    "UPDATE products SET naam = :naam, description = :description, price = :price, 
                     category_id = :category_id, has_personalization = :has_personalization, 
                     image_path = :image_path, image_path_alt = :image_path_alt WHERE id = :id"
                );
                $stmt->execute([
                    ':naam'                => $naam,
                    ':description'         => $description,
                    ':price'               => $price,
                    ':category_id'         => $category_id,
                    ':has_personalization'  => $has_personalization,
                    ':image_path'          => $image_filename,
                    ':image_path_alt'      => $image_alt_filename,
                    ':id'                  => $product_id
                ]);

                // Delete existing variants
                $pdo->prepare("DELETE FROM product_variants WHERE product_id = :product_id")
                    ->execute([':product_id' => $product_id]);

                // Re-insert variants
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
                set_flash_message('success', "Product '{$naam}' updated successfully!");
                redirect(ADMIN_URL . '/products.php');

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to update product. Please try again.';
                error_log("Edit Product Error: " . $e->getMessage());
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

    <form method="POST" action="" enctype="multipart/form-data" class="product-form" id="edit-product-form">
        <?= csrf_field() ?>

        <div class="form-grid">
            <!-- Left Column -->
            <div class="form-column">
                <h3 class="form-section-title">Product Details</h3>

                <div class="form-group">
                    <label for="naam">Product Name <span class="required">*</span></label>
                    <input type="text" id="naam" name="naam" 
                           value="<?= htmlspecialchars($product['naam']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" 
                               value="<?= htmlspecialchars($product['price']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" 
                                    data-category="<?= htmlspecialchars($cat['naam']) ?>"
                                    <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
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
                                   <?= $product['has_personalization'] === 'no' ? 'checked' : '' ?>>
                            <span class="toggle-btn">No</span>
                        </label>
                        <label class="toggle-label">
                            <input type="radio" name="has_personalization" value="yes"
                                   <?= $product['has_personalization'] === 'yes' ? 'checked' : '' ?>>
                            <span class="toggle-btn">Yes</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="form-column">
                <h3 class="form-section-title">Product Image</h3>

                <div class="form-group">
                    <label for="product_image">Main Image (JPG, PNG — max 2MB)</label>
                    <div class="image-upload-area" id="image-upload-area">
                        <input type="file" id="product_image" name="product_image" 
                               accept=".jpg,.jpeg,.png" class="file-input">
                        
                        <?php if ($product['image_path']): ?>
                            <div class="current-image" id="current-image">
                                <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" 
                                     alt="Current image">
                                <label class="remove-image-label">
                                    <input type="checkbox" name="remove_image" value="1" id="remove-image-checkbox"> 
                                    Remove current image
                                </label>
                            </div>
                        <?php else: ?>
                            <div class="upload-placeholder" id="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/>
                                </svg>
                                <p>Click to upload or drag & drop</p>
                            </div>
                        <?php endif; ?>
                        <img id="image-preview" class="image-preview" style="display:none;" alt="Preview">
                    </div>
                </div>

                <div class="form-group">
                    <label for="product_image_alt">Hover/Alternate Image (Optional — JPG, PNG — max 2MB)</label>
                    <div class="image-upload-area" id="image-upload-area-alt">
                        <input type="file" id="product_image_alt" name="product_image_alt" 
                               accept=".jpg,.jpeg,.png" class="file-input">
                        
                        <?php if ($product['image_path_alt']): ?>
                            <div class="current-image" id="current-image-alt">
                                <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path_alt']) ?>" 
                                     alt="Current alternate image">
                                <label class="remove-image-label">
                                    <input type="checkbox" name="remove_image_alt" value="1" id="remove-image-alt-checkbox"> 
                                    Remove current alternate image
                                </label>
                            </div>
                        <?php else: ?>
                            <div class="upload-placeholder" id="upload-placeholder-alt">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/>
                                </svg>
                                <p>Click to upload or drag & drop</p>
                            </div>
                        <?php endif; ?>
                        <img id="image-preview-alt" class="image-preview" style="display:none;" alt="Preview">
                    </div>
                </div>

                <h3 class="form-section-title">Variants (Size / Color / Stock)</h3>

                <div id="variants-container">
                    <?php if (!empty($variants)): ?>
                        <?php foreach ($variants as $i => $variant): ?>
                            <div class="variant-row" id="variant-row-<?= $i ?>">
                                <div class="variant-field size-field">
                                    <label>Size</label>
                                    <input type="text" name="variant_size[]" 
                                           value="<?= htmlspecialchars($variant['size'] ?? '') ?>" 
                                           placeholder="e.g., M, L, 42">
                                </div>
                                <div class="variant-field">
                                    <label>Color</label>
                                    <input type="text" name="variant_color[]" 
                                           value="<?= htmlspecialchars($variant['color'] ?? '') ?>" 
                                           placeholder="e.g., Brown">
                                </div>
                                <div class="variant-field">
                                    <label>Stock</label>
                                    <input type="number" name="variant_stock[]" min="0" 
                                           value="<?= $variant['stock_quantity'] ?>">
                                </div>
                                <button type="button" class="btn-remove-variant" onclick="removeVariant(this)" title="Remove">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
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
                            <button type="button" class="btn-remove-variant" onclick="removeVariant(this)" title="Remove">×</button>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-outline btn-sm" id="add-variant-btn" onclick="addVariant()">
                    + Add Another Variant
                </button>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="update-product-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
                Update Product
            </button>
            <a href="<?= ADMIN_URL ?>/products.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
