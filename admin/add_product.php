<?php
/**
 * Clicks Leather — Add Product
 */
ob_start();
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
        $naam = clean_input($_POST['naam'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $detail_title = clean_input($_POST['detail_title'] ?? '');
        $detail_description = $_POST['detail_description'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $custom_size_price = !empty($_POST['custom_size_price']) ? floatval($_POST['custom_size_price']) : null;
        $category_id = intval($_POST['category_id'] ?? 0);
        $has_personalization = ($_POST['has_personalization'] ?? 'no') === 'yes' ? 'yes' : 'no';
        $type = clean_input($_POST['type'] ?? '');

        // Validate
        if (empty($naam)) $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        if ($category_id <= 0) $errors[] = 'Please select a category.';

        // Validate type for Wallets and Leather Shoes
        $stmt = $pdo->prepare("SELECT naam FROM categories WHERE id = :category_id");
        $stmt->execute([':category_id' => $category_id]);
        $category = $stmt->fetch();
        if ($category && in_array($category['naam'], ['Wallets', 'Leather Shoes'])) {
            if (empty($type)) $errors[] = 'Type is required for ' . $category['naam'] . '.';
        }

        // Handle main image/video upload
        $image_filename = null;
        $image_media_type = null;
        $image_hash = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_media($_FILES['product_image']);
            if ($upload_result['success']) {
                $image_filename = $upload_result['filename'];
                $image_media_type = $upload_result['media_type'];
                $image_hash = $upload_result['image_hash'];
            } else {
                $errors[] = $upload_result['message'];
            }
        }

        // Remove alternate image/video upload handling - no longer needed

        // Handle gallery images/videos upload
        $gallery_images = [];
        if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
            foreach ($_FILES['gallery_images']['name'] as $key => $name) {
                if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['gallery_images']['name'][$key],
                        'type' => $_FILES['gallery_images']['type'][$key],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$key],
                        'error' => $_FILES['gallery_images']['error'][$key],
                        'size' => $_FILES['gallery_images']['size'][$key]
                    ];
                    $upload_result = upload_media($file);
                    if ($upload_result['success']) {
                        $gallery_images[] = [
                            'filename' => $upload_result['filename'],
                            'media_type' => $upload_result['media_type'],
                            'image_hash' => $upload_result['image_hash'],
                            'sort_order' => intval($_POST['gallery_sort_order'][$key] ?? 0)
                        ];
                    } else {
                        $errors[] = 'Gallery media: ' . $upload_result['message'];
                    }
                }
            }
        }

        // If no errors, insert product
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert product
                $stmt = $pdo->prepare(
                    "INSERT INTO products (naam, description, detail_title, detail_description, price, custom_size_price, category_id, has_personalization, type, image_path, image_path_alt, image_media_type, image_alt_media_type, image_hash)
                     VALUES (:naam, :description, :detail_title, :detail_description, :price, :custom_size_price, :category_id, :has_personalization, :type, :image_path, NULL, :image_media_type, NULL, :image_hash)"
                );
                $stmt->execute([
                    ':naam'                => $naam,
                    ':description'         => $description,
                    ':detail_title'        => $detail_title,
                    ':detail_description'  => $detail_description,
                    ':price'               => $price,
                    ':custom_size_price'   => $custom_size_price,
                    ':category_id'         => $category_id,
                    ':has_personalization'  => $has_personalization,
                    ':type'                => $type ?: null,
                    ':image_path'          => $image_filename,
                    ':image_media_type'    => $image_media_type,
                    ':image_hash'          => $image_hash
                ]);
                $product_id = $pdo->lastInsertId();

                // Insert gallery images/videos
                foreach ($gallery_images as $gallery_img) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO product_images (product_id, image_path, media_type, sort_order, image_hash) 
                         VALUES (:product_id, :image_path, :media_type, :sort_order, :image_hash)"
                    );
                    $stmt->execute([
                        ':product_id' => $product_id,
                        ':image_path' => $gallery_img['filename'],
                        ':media_type' => $gallery_img['media_type'],
                        ':sort_order' => $gallery_img['sort_order'],
                        ':image_hash' => $gallery_img['image_hash']
                    ]);
                }

                // Insert variants if provided
                $sizes  = $_POST['variant_size'] ?? [];
                $colors = $_POST['variant_color'] ?? [];
                $hexes  = $_POST['variant_hex'] ?? [];
                $stocks = $_POST['variant_stock'] ?? [];

                $variant_stmt = $pdo->prepare(
                    "INSERT INTO product_variants (product_id, size, color, hex_code, stock_quantity) 
                     VALUES (:product_id, :size, :color, :hex_code, :stock)"
                );

                for ($i = 0; $i < count($sizes); $i++) {
                    $size  = clean_input($sizes[$i] ?? '');
                    $color = clean_input($colors[$i] ?? '');
                    $hex   = clean_input($hexes[$i] ?? '');
                    $stock = intval($stocks[$i] ?? 0);

                    // Only insert if at least one field has data
                    if (!empty($size) || !empty($color)) {
                        $variant_stmt->execute([
                            ':product_id' => $product_id,
                            ':size'       => $size ?: null,
                            ':color'      => $color ?: null,
                            ':hex_code'   => $hex ?: null,
                            ':stock'      => $stock
                        ]);
                    }
                }

                // Handle color images
                if (isset($_POST['color_image_color']) && is_array($_POST['color_image_color'])) {
                    // Build color-image array
                    $color_image_data = [];
                    foreach ($_POST['color_image_color'] as $key => $color_name) {
                        $color_name = trim($color_name);
                        
                        // Skip if color name is empty
                        if (empty($color_name)) {
                            continue;
                        }
                        
                        // Check if a new file was uploaded
                        $image_path = null;
                        if (isset($_FILES['color_image_file']['name'][$key]) && $_FILES['color_image_file']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['color_image_file']['name'][$key],
                                'type' => $_FILES['color_image_file']['type'][$key],
                                'tmp_name' => $_FILES['color_image_file']['tmp_name'][$key],
                                'error' => $_FILES['color_image_file']['error'][$key],
                                'size' => $_FILES['color_image_file']['size'][$key]
                            ];
                            $upload_result = upload_media($file);
                            if ($upload_result['success']) {
                                $image_path = $upload_result['filename'];
                            } else {
                                $errors[] = 'Color image (' . htmlspecialchars($color_name) . '): ' . $upload_result['message'];
                            }
                        }
                        
                        // Only add if we have an image path
                        if ($image_path) {
                            $color_lower = strtolower($color_name);
                            $color_image_data[$color_lower] = $image_path;
                        }
                    }
                    
                    // Deduplicate: last entry wins (in-memory dedup by color key)
                    foreach ($color_image_data as $color => $image_path) {
                        $insert_stmt = $pdo->prepare("
                            INSERT INTO product_color_images (product_id, color, image_path)
                            VALUES (:product_id, :color, :image_path)
                        ");
                        $insert_stmt->execute([
                            ':product_id' => $product_id,
                            ':color' => $color,
                            ':image_path' => $image_path
                        ]);
                    }
                }

                // Handle description images (after product is created)
                if (isset($_FILES['description_image_file']) && is_array($_FILES['description_image_file']['name'])) {
                    // Validate max 5 images
                    $desc_image_count = 0;
                    foreach ($_FILES['description_image_file']['name'] as $name) {
                        if (!empty($name)) {
                            $desc_image_count++;
                        }
                    }
                    
                    if ($desc_image_count > 5) {
                        $errors[] = 'Maximum 5 description images allowed.';
                    } else {
                        foreach ($_FILES['description_image_file']['name'] as $key => $name) {
                            if (empty($name) || $_FILES['description_image_file']['error'][$key] !== UPLOAD_ERR_OK) {
                                continue;
                            }
                            
                            $file = [
                                'name' => $_FILES['description_image_file']['name'][$key],
                                'type' => $_FILES['description_image_file']['type'][$key],
                                'tmp_name' => $_FILES['description_image_file']['tmp_name'][$key],
                                'error' => $_FILES['description_image_file']['error'][$key],
                                'size' => $_FILES['description_image_file']['size'][$key]
                            ];
                            $upload_result = upload_media($file);
                            if ($upload_result['success']) {
                                $insert_stmt = $pdo->prepare("
                                    INSERT INTO product_description_images (product_id, image_path, sort_order)
                                    VALUES (:product_id, :image_path, :sort_order)
                                ");
                                $insert_stmt->execute([
                                    ':product_id' => $product_id,
                                    ':image_path' => $upload_result['filename'],
                                    ':sort_order' => $key
                                ]);
                            } else {
                                $errors[] = 'Description image upload failed: ' . $upload_result['message'];
                            }
                        }
                    }
                }

                $pdo->commit();
                set_flash_message('success', "Product '{$naam}' added successfully!");
                redirect(ADMIN_URL . '/products.php');

            } catch (Exception $e) {
                $pdo->rollBack();
                // Delete uploaded images if DB insert failed
                if ($image_filename) {
                    delete_image($image_filename);
                }
                if ($image_alt_filename) {
                    delete_image($image_alt_filename);
                }
                foreach ($gallery_images as $gallery_img) {
                    delete_image($gallery_img['filename']);
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
                        <label for="price">Price ($) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" 
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" 
                               placeholder="0.00" required>
                    </div>

                    <div class="form-group" id="custom-size-price-group" style="display: none;">
                        <label for="custom_size_price">Custom Size Price (optional)</label>
                        <input type="number" id="custom_size_price" name="custom_size_price" 
                               step="0.01" placeholder="e.g. 50.00" 
                               value="<?= isset($_POST['custom_size_price']) ? htmlspecialchars($_POST['custom_size_price']) : '' ?>">
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

                    <div class="form-group" id="type-group" style="display: none;">
                        <label for="type">Type <span class="required">*</span></label>
                        <select id="type" name="type">
                            <option value="">Select Type</option>
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

                <h3 class="form-section-title" style="margin-top: var(--space-xl);">Product Detail Page Content</h3>

                <div class="form-group">
                    <label for="detail_title">Detail Title</label>
                    <input type="text" id="detail_title" name="detail_title"
                           value="<?= htmlspecialchars($_POST['detail_title'] ?? '') ?>"
                           placeholder="e.g., Men's Brown Leather Hooded Bomber Jacket, Casual Streetwear Style">
                </div>

                <div class="form-group">
                    <label for="detail_description">Detail Description</label>
                    <textarea id="detail_description" name="detail_description" rows="8"
                              placeholder="Write your intro/tagline as plain text first. For each section heading (e.g. Key Features, Perfect For, Care Instructions), end that line with a colon (:). List each point on its own line below it."><?= htmlspecialchars($_POST['detail_description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Right Column: Image & Variants -->
            <div class="form-column">
                <h3 class="form-section-title">Product Image</h3>

                <div class="form-group">
                    <label for="product_image">Main Image (JPG, PNG — max 2MB) <span class="required">*</span></label>
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

                <!-- Hover/Alternate Image field removed - no longer needed -->

                <h3 class="form-section-title">Variants (Size / Color / Stock)</h3>
                <p class="form-hint" id="variant-hint">Add product variants below. Size fields are shown for Shoes, Jackets, and similar categories.</p>

                <div id="variants-container">
                    <!-- Default variant row -->
                    <div class="variant-row" id="variant-row-0">
                        <div class="variant-field size-field">
                            <label>Size</label>
                            <input type="text" name="variant_size[]" placeholder="e.g., M, L, 42">
                        </div>
                        <div class="variant-field color-field">
                            <label>Color</label>
                            <input type="text" name="variant_color[]" placeholder="e.g., Brown">
                        </div>
                        <div class="variant-field hex-field">
                            <label>Hex Code</label>
                            <input type="color" name="variant_hex[]" value="#000000">
                        </div>
                        <div class="variant-field stock-field">
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

            <h3 class="form-section-title" style="margin-top: var(--space-xl);">Color Images</h3>
            <p class="form-hint">Assign a main image for each color. These images will be shown when a customer selects that color on the product detail page.</p>

            <div id="color-images-container" style="display: flex; flex-wrap: wrap; gap: var(--space-md);">
                <!-- Color image cards will be added here dynamically -->
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="add-color-image-btn" onclick="addColorImageRow()">
                + Add Another Color Image
            </button>

            <h3 class="form-section-title" style="margin-top: var(--space-xl);">Description Images</h3>
            <p class="form-hint">Up to 5 additional images shown at the end of the product description.</p>

            <div id="description-images-container" style="display: flex; flex-wrap: wrap; gap: var(--space-md);">
                <!-- Description image cards will be added here dynamically -->
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="add-description-image-btn" onclick="addDescriptionImageRow()">
                + Add Another Description Image
            </button>

            <h3 class="form-section-title" style="margin-top: var(--space-xl);">Gallery Images</h3>
            <p class="form-hint">Additional images for the product detail page (separate from main/hover images)</p>

            <div id="gallery-images-container">
                <!-- Gallery image rows will be added here dynamically -->
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="add-gallery-image-btn" onclick="addGalleryImage()">
                + Add Another Image
            </button>
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

<script>
const categorySelect = document.getElementById('category_id');
const typeDropdown = document.getElementById('type');
const typeGroup = document.getElementById('type-group');

const typeOptions = {
    'Wallets': ['Bifold Wallet', 'Long Wallet', 'Card Holder'],
    'Leather Shoes': ['Loafers', 'Chelsea', 'Long Boots', 'Cowboy Boots', 'Oxford Shoes']
};

function updateTypeDropdown() {
    const selectedCategory = categorySelect.options[categorySelect.selectedIndex].getAttribute('data-category');

    if (typeOptions[selectedCategory]) {
        // Show dropdown and populate
        typeDropdown.innerHTML = '<option value="">Select Type</option>';
        typeOptions[selectedCategory].forEach(type => {
            const selected = (<?= isset($_POST['type']) ? json_encode($_POST['type']) : '""' ?>) === type ? 'selected' : '';
            typeDropdown.innerHTML += `<option value="${type}" ${selected}>${type}</option>`;
        });
        typeGroup.style.display = 'block';
        typeDropdown.required = true;
    } else {
        // Hide dropdown
        typeGroup.style.display = 'none';
        typeDropdown.required = false;
    }
}

categorySelect.addEventListener('change', updateTypeDropdown);

// Trigger on page load
updateTypeDropdown();

const customSizePriceGroup = document.getElementById('custom-size-price-group');

function updateCustomSizePriceField() {
    const selectedCategory = categorySelect.options[categorySelect.selectedIndex].getAttribute('data-category');
    
    if (selectedCategory === 'Leather Jackets' || selectedCategory === 'Leather Shoes') {
        customSizePriceGroup.style.display = 'block';
    } else {
        customSizePriceGroup.style.display = 'none';
    }
}

categorySelect.addEventListener('change', updateCustomSizePriceField);
updateCustomSizePriceField();

function addDescriptionImageRow() {
    const container = document.getElementById('description-images-container');
    const currentCount = container.querySelectorAll('.description-image-card').length;
    
    if (currentCount >= 5) {
        alert('Maximum 5 description images allowed.');
        return;
    }
    
    const index = currentCount;
    const card = document.createElement('div');
    card.className = 'description-image-card';
    card.id = 'description-image-card-' + index;
    card.style.cssText = 'display: inline-block; position: relative;';
    
    card.innerHTML = `
        <label style="cursor: pointer; display: block;">
            <input type="file" name="description_image_file[]" accept=".jpg,.jpeg,.png" style="display: none;" onchange="previewDescriptionImage(this)">
            <div style="width: 80px; height: 80px; border: 2px dashed var(--border-color); border-radius: var(--radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--bg-muted);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">Add Image</span>
            </div>
        </label>
        <input type="hidden" name="description_image_id[]" value="">
        <button type="button" class="btn-remove-variant" onclick="removeDescriptionImageRow(this)" title="Remove" style="position: absolute; top: -8px; right: -8px;">×</button>
    `;
    
    container.appendChild(card);
}

function removeDescriptionImageRow(button) {
    const card = button.closest('.description-image-card');
    card.remove();
}

function previewDescriptionImage(input) {
    const card = input.closest('.description-image-card');
    const imgContainer = card.querySelector('label > div');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgContainer.innerHTML = `
                <img src="${e.target.result}" alt="Description image" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.6); border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                </div>
            `;
            imgContainer.style.position = 'relative';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
