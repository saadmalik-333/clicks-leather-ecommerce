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

// Fetch color images for this product
$color_images_stmt = $pdo->prepare("SELECT * FROM product_color_images WHERE product_id = :product_id ORDER BY color ASC");
$color_images_stmt->execute([':product_id' => $product_id]);
$color_images = $color_images_stmt->fetchAll();

// Fetch description images for this product
$description_images_stmt = $pdo->prepare("SELECT * FROM product_description_images WHERE product_id = :product_id ORDER BY sort_order ASC");
$description_images_stmt->execute([':product_id' => $product_id]);
$description_images = $description_images_stmt->fetchAll();

// Fetch categories
$categories = get_all_categories($pdo);

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $naam = clean_input($_POST['naam'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $detail_title = clean_input($_POST['detail_title'] ?? '');
        $detail_description = $_POST['detail_description'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $custom_size_price = !empty($_POST['custom_size_price']) ? floatval($_POST['custom_size_price']) : null;
        $category_id = intval($_POST['category_id'] ?? 0);
        $has_personalization = ($_POST['has_personalization'] ?? 'no') === 'yes' ? 'yes' : 'no';
        $is_popular = ($_POST['is_popular'] ?? '0') === '1' ? 1 : 0;
        $type = clean_input($_POST['type'] ?? '');

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

        // Validate is_popular: max 8 products can be popular
        if ($is_popular === 1) {
            // Check if this product is already popular
            if ($product['is_popular'] != 1) {
                // Count current popular products (excluding this one)
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_popular = 1 AND id != :id");
                $stmt->execute([':id' => $product_id]);
                $count = $stmt->fetch()['count'];
                if ($count >= 8) {
                    $errors[] = 'Maximum of 8 Most Popular products already selected. Turn one off first to add another.';
                }
            }
        }

        // Handle image/video removal
        $image_was_touched = false;
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if ($product['image_path']) {
                delete_image($product['image_path']);
            }
            $image_filename = null;
            $image_media_type = null;
            $image_hash = null;
            $image_was_touched = true;
        }

        // Handle main image/video upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_media($_FILES['product_image']);
            if ($upload_result['success']) {
                // Delete old image/video
                if ($product['image_path']) {
                    delete_image($product['image_path']);
                }
                $image_filename = $upload_result['filename'];
                $image_media_type = $upload_result['media_type'];
                $image_hash = $upload_result['image_hash'];
                $image_was_touched = true;
            } else {
                $errors[] = $upload_result['message'];
            }
        }

        // Preserve existing image ONLY if nothing happened at all (no removal, no new upload)
        if (!$image_was_touched) {
            $image_filename = $product['image_path'];
            $image_media_type = $product['image_media_type'];
            $image_hash = $product['image_hash'] ?? null;
        }

        // Remove alternate image/video upload handling - no longer needed
        $image_alt_filename = null;
        $image_alt_media_type = null;

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

        // Handle gallery images
        // FIRST: Fetch existing gallery images into memory before deleting
        $existing_gallery_stmt = $pdo->prepare("SELECT id, image_path, media_type, sort_order, image_hash FROM product_images WHERE product_id = :product_id");
        $existing_gallery_stmt->execute([':product_id' => $product_id]);
        $existing_gallery_map = [];
        foreach ($existing_gallery_stmt->fetchAll() as $existing) {
            $existing_gallery_map[$existing['id']] = [
                'image_path' => $existing['image_path'],
                'media_type' => $existing['media_type'],
                'sort_order' => $existing['sort_order'],
                'image_hash' => $existing['image_hash']
            ];
        }

        // Handle gallery image deletion (specific IDs marked for deletion)
        $delete_gallery_ids = $_POST['delete_gallery_image'] ?? [];
        if (!empty($delete_gallery_ids)) {
            foreach ($delete_gallery_ids as $gallery_id) {
                $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = :id");
                $stmt->execute([':id' => intval($gallery_id)]);
                $gallery_img = $stmt->fetch();
                if ($gallery_img) {
                    delete_image($gallery_img['image_path']);
                    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = :id");
                    $stmt->execute([':id' => intval($gallery_id)]);
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update product
                $stmt = $pdo->prepare(
                    "UPDATE products SET naam = :naam, description = :description, detail_title = :detail_title, detail_description = :detail_description, price = :price, custom_size_price = :custom_size_price,
                     category_id = :category_id, has_personalization = :has_personalization, is_popular = :is_popular, type = :type,
                     image_path = :image_path, image_media_type = :image_media_type, image_hash = :image_hash WHERE id = :id"
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
                    ':is_popular'          => $is_popular,
                    ':type'                => $type ?: null,
                    ':image_path'          => $image_filename,
                    ':image_media_type'    => $image_media_type,
                    ':image_hash'          => $image_hash,
                    ':id'                  => $product_id
                ]);

                // Delete all remaining gallery images for this product
                $pdo->prepare("DELETE FROM product_images WHERE product_id = :product_id")->execute([':product_id' => $product_id]);

                // Re-insert gallery images (new uploads + preserved existing)
                // First, insert new uploads
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

                // Then, re-insert preserved existing images (those not deleted)
                foreach ($existing_gallery_map as $id => $existing) {
                    // Skip if this ID was marked for deletion
                    if (in_array($id, $delete_gallery_ids)) {
                        continue;
                    }
                    $stmt = $pdo->prepare(
                        "INSERT INTO product_images (product_id, image_path, media_type, sort_order, image_hash)
                         VALUES (:product_id, :image_path, :media_type, :sort_order, :image_hash)"
                    );
                    $stmt->execute([
                        ':product_id' => $product_id,
                        ':image_path' => $existing['image_path'],
                        ':media_type' => $existing['media_type'],
                        ':sort_order' => $existing['sort_order'],
                        ':image_hash' => $existing['image_hash']
                    ]);
                }

                // Delete existing variants
                $pdo->prepare("DELETE FROM product_variants WHERE product_id = :product_id")
                    ->execute([':product_id' => $product_id]);

                // Re-insert variants
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
                // FIRST: Fetch existing color images into memory before deleting
                $existing_color_images_stmt = $pdo->prepare("SELECT id, color, image_path FROM product_color_images WHERE product_id = :product_id");
                $existing_color_images_stmt->execute([':product_id' => $product_id]);
                $existing_color_images_map = [];
                foreach ($existing_color_images_stmt->fetchAll() as $existing) {
                    $existing_color_images_map[$existing['id']] = $existing['image_path'];
                }

                // THEN: Delete all existing color images for this product
                $pdo->prepare("DELETE FROM product_color_images WHERE product_id = :product_id")->execute([':product_id' => $product_id]);

                // Process color image submissions
                if (isset($_POST['color_image_color']) && is_array($_POST['color_image_color'])) {
                    // Build color-image array with dedup (last entry wins per color)
                    $color_image_data = [];
                    
                    foreach ($_POST['color_image_color'] as $key => $color_name) {
                        $color_name = trim($color_name);
                        
                        // Skip if color name is empty
                        if (empty($color_name)) {
                            continue;
                        }
                        
                        // Check if this row is marked for removal
                        if (isset($_POST['color_image_remove']) && is_array($_POST['color_image_remove']) && in_array($_POST['color_image_id'][$key] ?? '', $_POST['color_image_remove'])) {
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
                        } else {
                            // Keep existing image from in-memory map if no new file uploaded
                            $existing_id = $_POST['color_image_id'][$key] ?? '';
                            if (!empty($existing_id) && isset($existing_color_images_map[$existing_id])) {
                                $image_path = $existing_color_images_map[$existing_id];
                            }
                        }
                        
                        // Only add if we have an image path
                        if ($image_path) {
                            // Store color in lowercase for case-insensitive matching
                            $color_lower = strtolower($color_name);
                            // Dedup: last entry wins (overwrite if same color)
                            $color_image_data[$color_lower] = $image_path;
                        }
                    }
                    
                    // Insert deduplicated color images
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

                // Handle description images
                // FIRST: Fetch existing description images into memory before deleting
                $existing_desc_images_stmt = $pdo->prepare("SELECT id, image_path FROM product_description_images WHERE product_id = :product_id");
                $existing_desc_images_stmt->execute([':product_id' => $product_id]);
                $existing_desc_images_map = [];
                foreach ($existing_desc_images_stmt->fetchAll() as $existing) {
                    $existing_desc_images_map[$existing['id']] = $existing['image_path'];
                }

                // THEN: Delete all existing description images for this product
                $pdo->prepare("DELETE FROM product_description_images WHERE product_id = :product_id")->execute([':product_id' => $product_id]);

                // Process description image submissions
                if (isset($_POST['description_image_id']) && is_array($_POST['description_image_id'])) {
                    // Validate max 5 images
                    $desc_image_count = 0;
                    foreach ($_POST['description_image_id'] as $key => $id) {
                        // Check if this row is marked for removal
                        if (isset($_POST['description_image_remove']) && is_array($_POST['description_image_remove']) && in_array($id, $_POST['description_image_remove'])) {
                            continue;
                        }
                        // Check if a new file was uploaded OR if there's an existing ID
                        $has_new_file = isset($_FILES['description_image_file']['name'][$key]) && $_FILES['description_image_file']['error'][$key] === UPLOAD_ERR_OK;
                        $has_existing_id = !empty($id);
                        if ($has_new_file || $has_existing_id) {
                            $desc_image_count++;
                        }
                    }
                    
                    if ($desc_image_count > 5) {
                        $errors[] = 'Maximum 5 description images allowed.';
                    } else {
                        foreach ($_POST['description_image_id'] as $key => $existing_id) {
                            // Check if this row is marked for removal
                            if (isset($_POST['description_image_remove']) && is_array($_POST['description_image_remove']) && in_array($existing_id, $_POST['description_image_remove'])) {
                                continue;
                            }
                            
                            // Check if a new file was uploaded
                            $image_path = null;
                            if (isset($_FILES['description_image_file']['name'][$key]) && $_FILES['description_image_file']['error'][$key] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['description_image_file']['name'][$key],
                                    'type' => $_FILES['description_image_file']['type'][$key],
                                    'tmp_name' => $_FILES['description_image_file']['tmp_name'][$key],
                                    'error' => $_FILES['description_image_file']['error'][$key],
                                    'size' => $_FILES['description_image_file']['size'][$key]
                                ];
                                $upload_result = upload_media($file);
                                if ($upload_result['success']) {
                                    $image_path = $upload_result['filename'];
                                } else {
                                    $errors[] = 'Description image upload failed: ' . $upload_result['message'];
                                }
                            } else {
                                // Keep existing image from in-memory map if no new file uploaded
                                if (!empty($existing_id) && isset($existing_desc_images_map[$existing_id])) {
                                    $image_path = $existing_desc_images_map[$existing_id];
                                }
                            }
                            
                            // Only add if we have an image path
                            if ($image_path) {
                                $insert_stmt = $pdo->prepare("
                                    INSERT INTO product_description_images (product_id, image_path, sort_order)
                                    VALUES (:product_id, :image_path, :sort_order)
                                ");
                                $insert_stmt->execute([
                                    ':product_id' => $product_id,
                                    ':image_path' => $image_path,
                                    ':sort_order' => $key
                                ]);
                            }
                        }
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

// Handle review visibility toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_review_visibility'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('error', 'Invalid form submission.');
        redirect(ADMIN_URL . '/edit_product.php?id=' . $product_id);
    }
    
    $review_id = intval($_POST['review_id'] ?? 0);
    $new_is_hidden = intval($_POST['new_is_hidden'] ?? 0);
    
    if ($review_id <= 0) {
        set_flash_message('error', 'Invalid review ID.');
        redirect(ADMIN_URL . '/edit_product.php?id=' . $product_id);
    }
    
    // Verify review belongs to this product
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = :id AND product_id = :product_id");
    $stmt->execute([':id' => $review_id, ':product_id' => $product_id]);
    if (!$stmt->fetch()) {
        set_flash_message('error', 'Review not found or does not belong to this product.');
        redirect(ADMIN_URL . '/edit_product.php?id=' . $product_id);
    }
    
    // Update review visibility
    $stmt = $pdo->prepare("UPDATE reviews SET is_hidden = :is_hidden WHERE id = :id");
    $stmt->execute([':is_hidden' => $new_is_hidden, ':id' => $review_id]);
    
    set_flash_message('success', 'Review visibility updated successfully.');
    redirect(ADMIN_URL . '/edit_product.php?id=' . $product_id);
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

                    <div class="form-group" id="custom-size-price-group" style="display: none;">
                        <label for="custom_size_price">Custom Size Price (optional)</label>
                        <input type="number" id="custom_size_price" name="custom_size_price" 
                               step="0.01" placeholder="e.g. 50.00" 
                               value="<?= $product['custom_size_price'] ?? '' ?>">
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

                <div class="form-group">
                    <label>Mark as Most Popular?</label>
                    <div class="toggle-group">
                        <label class="toggle-label">
                            <input type="radio" name="is_popular" value="0"
                                   <?= ($product['is_popular'] ?? 0) != 1 ? 'checked' : '' ?>>
                            <span class="toggle-btn">No</span>
                        </label>
                        <label class="toggle-label">
                            <input type="radio" name="is_popular" value="1"
                                   <?= ($product['is_popular'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <span class="toggle-btn">Yes</span>
                        </label>
                    </div>
                    <p class="form-hint">Maximum 8 products can be marked as Most Popular.</p>
                </div>

                <h3 class="form-section-title" style="margin-top: var(--space-xl);">Product Detail Page Content</h3>

                <div class="form-group">
                    <label for="detail_title">Detail Title</label>
                    <input type="text" id="detail_title" name="detail_title"
                           value="<?= htmlspecialchars($product['detail_title'] ?? '') ?>"
                           placeholder="e.g., Men's Brown Leather Hooded Bomber Jacket, Casual Streetwear Style">
                </div>

                <div class="form-group">
                    <label for="detail_description">Detail Description</label>
                    <textarea id="detail_description" name="detail_description" rows="8"
                              placeholder="Write your intro/tagline as plain text first. For each section heading (e.g. Key Features, Perfect For, Care Instructions), end that line with a colon (:). List each point on its own line below it."><?= htmlspecialchars($product['detail_description'] ?? '') ?></textarea>
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
                                <span>JPG, JPEG, PNG (max 2MB)</span>
                            </div>
                        <?php endif; ?>
                        <img id="image-preview" class="image-preview" style="display:none;" alt="Preview">
                    </div>
                </div>

                <!-- Hover/Alternate Image field removed - no longer needed -->

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
                                <div class="variant-field color-field">
                                    <label>Color</label>
                                    <input type="text" name="variant_color[]" 
                                           value="<?= htmlspecialchars($variant['color'] ?? '') ?>" 
                                           placeholder="e.g., Brown">
                                </div>
                                <div class="variant-field hex-field">
                                    <label>Hex Code</label>
                                    <input type="color" name="variant_hex[]" 
                                           value="<?= htmlspecialchars($variant['hex_code'] ?? '#000000') ?>">
                                </div>
                                <div class="variant-field stock-field">
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
                            <button type="button" class="btn-remove-variant" onclick="removeVariant(this)" title="Remove">×</button>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-outline btn-sm" id="add-variant-btn" onclick="addVariant()">
                    + Add Another Variant
                </button>
            </div>

            <h3 class="form-section-title" style="margin-top: var(--space-xl);">Color Images</h3>
            <p class="form-hint">Assign a main image for each color. These images will be shown when a customer selects that color on the product detail page.</p>

            <div id="color-images-container" style="display: flex; flex-wrap: wrap; gap: var(--space-md);">
                <?php if (!empty($color_images)): ?>
                    <?php foreach ($color_images as $i => $color_img): ?>
                        <div class="color-image-card" id="color-image-card-<?= $i ?>" style="display: inline-block; position: relative;">
                            <label style="cursor: pointer; display: block;">
                                <input type="file" name="color_image_file[]" accept=".jpg,.jpeg,.png" style="display: none;" onchange="previewColorImage(this)">
                                <?php if ($color_img['image_path']): ?>
                                    <div style="position: relative; width: 80px; height: 80px;">
                                        <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($color_img['image_path']) ?>" 
                                             alt="Color image" 
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                        <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.6); border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                                <circle cx="12" cy="13" r="4"/>
                                            </svg>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="width: 80px; height: 80px; border: 2px dashed var(--border-color); border-radius: var(--radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--bg-muted);">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                            <circle cx="12" cy="13" r="4"/>
                                        </svg>
                                        <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">Add Image</span>
                                    </div>
                                <?php endif; ?>
                            </label>
                            <input type="text" name="color_image_color[]" 
                                   value="<?= htmlspecialchars($color_img['color']) ?>" 
                                   placeholder="Color" 
                                   style="width: 80px; font-size: 0.75rem; margin-top: var(--space-xs); padding: 4px;">
                            <input type="hidden" name="color_image_id[]" value="<?= $color_img['id'] ?>">
                            <label style="display: block; font-size: 0.75rem; margin-top: var(--space-xs);">
                                <input type="checkbox" name="color_image_remove[]" value="<?= $color_img['id'] ?>"> Remove
                            </label>
                            <button type="button" class="btn-remove-variant" onclick="removeColorImageRow(this)" title="Remove" style="position: absolute; top: -8px; right: -8px;">×</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="add-color-image-btn" onclick="addColorImageRow()">
                + Add Another Color Image
            </button>

            <h3 class="form-section-title" style="margin-top: var(--space-xl);">Description Images</h3>
            <p class="form-hint">Up to 5 additional images shown at the end of the product description.</p>

            <div id="description-images-container" style="display: flex; flex-wrap: wrap; gap: var(--space-md);">
                <?php if (!empty($description_images)): ?>
                    <?php foreach ($description_images as $i => $desc_img): ?>
                        <div class="description-image-card" id="description-image-card-<?= $i ?>" style="display: inline-block; position: relative;">
                            <label style="cursor: pointer; display: block;">
                                <input type="file" name="description_image_file[]" accept=".jpg,.jpeg,.png" style="display: none;" onchange="previewDescriptionImage(this)">
                                <?php if ($desc_img['image_path']): ?>
                                    <div style="position: relative; width: 80px; height: 80px;">
                                        <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($desc_img['image_path']) ?>" 
                                             alt="Description image" 
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                        <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.6); border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                                <circle cx="12" cy="13" r="4"/>
                                            </svg>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="width: 80px; height: 80px; border: 2px dashed var(--border-color); border-radius: var(--radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--bg-muted);">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                            <circle cx="12" cy="13" r="4"/>
                                        </svg>
                                        <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">Add Image</span>
                                    </div>
                                <?php endif; ?>
                            </label>
                            <input type="hidden" name="description_image_id[]" value="<?= $desc_img['id'] ?>">
                            <label style="display: block; font-size: 0.75rem; margin-top: var(--space-xs);">
                                <input type="checkbox" name="description_image_remove[]" value="<?= $desc_img['id'] ?>"> Remove
                            </label>
                            <button type="button" class="btn-remove-variant" onclick="removeDescriptionImageRow(this)" title="Remove" style="position: absolute; top: -8px; right: -8px;">×</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="add-description-image-btn" onclick="addDescriptionImageRow()">
                + Add Another Description Image
            </button>

            <h3 class="form-section-title" style="margin-top: var(--space-xl);">Gallery Images</h3>
            <p class="form-hint">Additional images for the product detail page (separate from main/hover images)</p>

            <?php
            // Fetch existing gallery images
            $gallery_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC");
            $gallery_stmt->execute([':product_id' => $product_id]);
            $existing_gallery = $gallery_stmt->fetchAll();
            ?>

            <?php if (!empty($existing_gallery)): ?>
                <div style="margin-bottom: var(--space-lg);">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: var(--space-sm);">Existing Gallery Images:</p>
                    <?php foreach ($existing_gallery as $img): ?>
                        <div style="display: inline-block; margin-right: var(--space-md); margin-bottom: var(--space-md); position: relative;">
                            <?php if (($img['media_type'] ?? 'image') === 'video'): ?>
                                <video src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>"
                                       style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"
                                       muted playsinline preload="metadata"></video>
                            <?php else: ?>
                                <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>"
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <?php endif; ?>
                            <label style="display: block; font-size: 0.75rem; margin-top: var(--space-xs);">
                                <input type="checkbox" name="delete_gallery_image[]" value="<?= $img['id'] ?>">
                                Delete
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div id="gallery-images-container">
                <!-- Gallery image rows will be added here dynamically -->
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="add-gallery-image-btn" onclick="addGalleryImage()">
                + Add Another Image
            </button>
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

<!-- Reviews Moderation Section -->
<div class="form-card" style="margin-top: var(--space-xl);">
    <h3 class="form-section-title">Reviews for this Product</h3>
    
    <?php
    // Fetch reviews for this product (including hidden ones for admin view)
    $reviews_stmt = $pdo->prepare("
        SELECT r.*, u.naam as reviewer_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = :product_id 
        ORDER BY r.created_at DESC
    ");
    $reviews_stmt->execute([':product_id' => $product_id]);
    $product_reviews = $reviews_stmt->fetchAll();
    ?>
    
    <?php if (empty($product_reviews)): ?>
        <p style="color: var(--text-muted);">No reviews yet for this product.</p>
    <?php else: ?>
        <div class="reviews-moderation-list">
            <?php foreach ($product_reviews as $review): ?>
                <div class="review-moderation-card <?= $review['is_hidden'] ? 'review-hidden' : '' ?>">
                    <div class="review-moderation-header">
                        <div class="review-rating">★ <?= $review['rating'] ?></div>
                        <div class="review-meta">
                            <strong><?= escape_html($review['reviewer_name']) ?></strong>
                            <span class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                    </div>
                    <?php if (!empty($review['review_text'])): ?>
                        <div class="review-text"><?= nl2br(escape_html($review['review_text'])) ?></div>
                    <?php endif; ?>
                    <div class="review-moderation-actions">
                        <form method="POST" action="<?= ADMIN_URL ?>/edit_product.php?id=<?= $product_id ?>" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="toggle_review_visibility" value="1">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <input type="hidden" name="new_is_hidden" value="<?= $review['is_hidden'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-sm <?= $review['is_hidden'] ? 'btn-primary' : 'btn-outline' ?>">
                                <?= $review['is_hidden'] ? 'Show Review' : 'Hide Review' ?>
                            </button>
                        </form>
                        <span class="review-status-badge <?= $review['is_hidden'] ? 'status-hidden' : 'status-visible' ?>">
                            <?= $review['is_hidden'] ? 'Hidden' : 'Visible' ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- CSS for reviews moderation -->
<style>
.reviews-moderation-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.review-moderation-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: var(--space-lg);
}

.review-moderation-card.review-hidden {
    background: var(--bg-card);
    border-color: var(--border-color);
    opacity: 0.7;
}

.review-moderation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-sm);
}

.review-rating {
    color: #f59e0b;
    font-size: 1.25rem;
    font-weight: 500;
}

.review-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.review-date {
    font-size: 0.8rem;
}

.review-text {
    margin-bottom: var(--space-md);
    line-height: 1.5;
}

.review-moderation-card .review-text::before {
    content: none;
}

.review-moderation-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.review-status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
}

.review-status-badge.status-visible {
    background: #d1fae5;
    color: #065f46;
}

.review-status-badge.status-hidden {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<script>
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

document.getElementById('edit-product-form').addEventListener('submit', function(e) {
    const isPopularRadios = document.getElementsByName('is_popular');
    let isPopular = false;
    for (const radio of isPopularRadios) {
        if (radio.checked && radio.value === '1') {
            isPopular = true;
            break;
        }
    }
    
    if (isPopular) {
        // Check if this product is already popular
        const currentIsPopular = <?= ($product['is_popular'] ?? 0) == 1 ? 'true' : 'false' ?>;
        
        if (!currentIsPopular) {
            // Fetch current popular count via AJAX
            fetch('<?= ADMIN_URL ?>/api/check_popular_count.php?exclude_id=<?= $product_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.count >= 8) {
                        e.preventDefault();
                        alert('Maximum of 8 Most Popular products already selected. Turn one off first to add another.');
                    }
                })
                .catch(error => {
                    console.error('Error checking popular count:', error);
                    // Allow submission on error (server-side will catch it)
                });
        }
    }
});
</script>

<script>
const categorySelect = document.getElementById('category_id');
const typeDropdown = document.getElementById('type');
const typeGroup = document.getElementById('type-group');

const typeOptions = {
    'Wallets': ['Bifold Wallet', 'Long Wallet', 'Card Holder'],
    'Leather Shoes': ['Loafers', 'Chelsea', 'Long Boots', 'Cowboy Boots', 'Oxford Shoes']
};

const currentType = <?= json_encode($product['type'] ?? '') ?>;

function updateTypeDropdown() {
    const selectedCategory = categorySelect.options[categorySelect.selectedIndex].getAttribute('data-category');

    if (typeOptions[selectedCategory]) {
        // Show dropdown and populate
        typeDropdown.innerHTML = '<option value="">Select Type</option>';
        typeOptions[selectedCategory].forEach(type => {
            const selected = currentType === type ? 'selected' : '';
            typeDropdown.innerHTML += `<option value="${type}" ${selected}>${type}</option>`;
        });
        typeGroup.style.display = 'block';
        typeDropdown.required = true;
    } else {
        // Hide dropdown
        typeGroup.style.display = 'none';
        typeDropdown.required = false;
        typeDropdown.value = '';
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
