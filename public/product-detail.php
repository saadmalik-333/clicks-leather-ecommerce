<?php
/**
 * Clicks Leather — Product Detail Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    set_flash_message('error', 'Invalid product ID.');
    redirect(PUBLIC_URL . '/index.php');
}

// Fetch product with category
$stmt = $pdo->prepare("
    SELECT p.*, c.naam as category_naam 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = :id
");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash_message('error', 'Product not found.');
    redirect(PUBLIC_URL . '/index.php');
}

// Get shipping settings
$shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
$shipping_flat_cost = floatval(get_setting($pdo, 'shipping_flat_cost', '15.00'));

// Get product discount
$product_discount = get_product_discount($pdo, $product_id, $product['category_id']);

// Fetch gallery images
$gallery_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC");
$gallery_stmt->execute([':product_id' => $product_id]);
$gallery_images = $gallery_stmt->fetchAll();

// Fetch variants
$variants_stmt = $pdo->prepare("SELECT DISTINCT size, color FROM product_variants WHERE product_id = :product_id");
$variants_stmt->execute([':product_id' => $product_id]);
$variants = $variants_stmt->fetchAll();

// Extract unique colors and sizes
$colors = array_unique(array_column($variants, 'color'));
$sizes = array_unique(array_column($variants, 'size'));
$colors = array_filter($colors);
$sizes = array_filter($sizes);

// Build variant combinations array for JavaScript
$variant_combinations = [];
foreach ($variants as $variant) {
    if (!empty($variant['color']) && !empty($variant['size'])) {
        $variant_combinations[] = [
            'color' => $variant['color'],
            'size' => $variant['size']
        ];
    }
}

// Determine title to display
$display_title = !empty($product['detail_title']) ? $product['detail_title'] : $product['naam'];

// Determine description to display
$display_description = !empty($product['detail_description']) ? $product['detail_description'] : $product['description'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clicks Leather — <?= htmlspecialchars($display_title) ?>. Premium handcrafted leather goods.">
    <title><?= htmlspecialchars($display_title) ?> — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <meta property="og:title" content="<?= htmlspecialchars($display_title) ?> — Clicks Leather">
    <meta property="og:description" content="Clicks Leather — <?= htmlspecialchars($display_title) ?>. Premium handcrafted leather goods.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/product-detail.php?id=<?= htmlspecialchars($product['id']) ?>">
    <meta property="og:type" content="product">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .option-optional {
            font-weight: 400;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .personalization-input-wrapper {
            position: relative;
        }

        .personalization-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-family: var(--font-body);
            padding-right: 50px;
        }

        .personalization-input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .char-counter {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

    <!-- Product Detail Section -->
    <section class="product-detail-page">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="<?= PUBLIC_URL ?>/index.php">Home</a>
                <span class="breadcrumb-separator">/</span>
                <a href="<?= PUBLIC_URL ?>/products.php?category=<?= strtolower(str_replace(' ', '-', $product['category_naam'])) ?>"><?= htmlspecialchars($product['category_naam']) ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?= htmlspecialchars($product['naam']) ?></span>
            </nav>

            <div class="product-detail-layout">
                <!-- Left: Image Gallery -->
                <div class="product-gallery">
                    <div class="gallery-main">
                        <button class="gallery-nav gallery-prev" id="gallery-prev">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"/></svg>
                        </button>
                        <div class="gallery-image-container" id="gallery-image-container">
                            <?php if (!empty($gallery_images)): ?>
                                <?php foreach ($gallery_images as $img): ?>
                                    <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($display_title) ?>" 
                                         class="gallery-slide <?= $img === $gallery_images[0] ? 'active' : '' ?>"
                                         data-index="<?= array_search($img, $gallery_images) ?>">
                                <?php endforeach; ?>
                            <?php elseif ($product['image_path']): ?>
                                <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($display_title) ?>" 
                                     class="gallery-slide active">
                            <?php else: ?>
                                <div class="gallery-placeholder">No image available</div>
                            <?php endif; ?>
                        </div>
                        <button class="gallery-nav gallery-next" id="gallery-next">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg>
                        </button>
                    </div>
                    <?php if (!empty($gallery_images)): ?>
                        <div class="gallery-thumbnails">
                            <?php foreach ($gallery_images as $index => $img): ?>
                                <button class="thumbnail <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                                    <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($display_title) ?> - Thumbnail <?= $index + 1 ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Product Info -->
                <div class="product-info-detail" data-variants="<?= htmlspecialchars(json_encode($variant_combinations)) ?>">
                    <h1 class="product-detail-title"><?= htmlspecialchars($display_title) ?></h1>
                    
                    <div class="trust-badges">
                        <div class="trust-badge-item">
                            <svg class="trust-badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                            <span>No Extra Duties</span>
                        </div>
                        <span>•</span>
                        <div class="trust-badge-item">
                            <svg class="trust-badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                            <span>No Hidden Fees</span>
                        </div>
                        <span>•</span>
                        <div class="trust-badge-item">
                            <svg class="trust-badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                            <span><?= $shipping_is_free === 'yes' ? 'Free Delivery' : 'Delivery from ' . format_price($shipping_flat_cost) ?></span>
                        </div>
                    </div>

                    <div class="product-detail-price">
                        <?php if ($product_discount): 
                            $discounted_price = $product['price'] * (1 - $product_discount['discount_percent'] / 100);
                        ?>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span style="text-decoration: line-through; color: var(--text-muted); font-size: 1.1rem;"><?= format_price($product['price']) ?></span>
                                <span style="color: var(--color-primary); font-weight: 600; font-size: 1.5rem;"><?= format_price($discounted_price) ?></span>
                                <span class="discount-badge" style="background: #8B7355; color: white; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 500;"><?= number_format($product_discount['discount_percent'], 0) ?>% OFF</span>
                            </div>
                        <?php else: ?>
                            <?= format_price($product['price']) ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($colors)): ?>
                        <div class="product-option-group">
                            <h3 class="option-title">Color</h3>
                            <div class="option-buttons">
                                <?php foreach ($colors as $color): ?>
                                    <button class="option-btn color-btn" data-value="<?= htmlspecialchars($color) ?>">
                                        <?= htmlspecialchars($color) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($sizes)): ?>
                        <div class="product-option-group">
                            <h3 class="option-title">Size</h3>
                            <div class="option-buttons">
                                <?php foreach ($sizes as $size): ?>
                                    <button class="option-btn size-btn" data-value="<?= htmlspecialchars($size) ?>">
                                        <?= htmlspecialchars($size) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($product['has_personalization'] === 'yes'): ?>
                        <div class="product-option-group">
                            <h3 class="option-title">Personalization <span class="option-optional">(Optional)</span></h3>
                            <div class="personalization-input-wrapper">
                                <input type="text" id="personalization_text" name="personalization_text" 
                                       placeholder="Enter text to engrave, e.g. your name or initials" 
                                       maxlength="20"
                                       class="personalization-input">
                                <div class="char-counter">
                                    <span id="char-count">0</span>/20
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="product-detail-description">
                        <?php
                        // Parse description into sections
                        $desc_lines = explode("\n", $display_description);
                        $current_section = null;
                        $section_content = '';
                        $intro_content = '';
                        $sections = [];

                        foreach ($desc_lines as $line) {
                            $line = trim($line);

                            // Skip empty lines
                            if (empty($line)) {
                                continue;
                            }

                            // Check if line is a section heading (ends with colon)
                            $is_heading = str_ends_with($line, ':');

                            if ($is_heading) {
                                // Save previous section if exists
                                if (!empty($current_section)) {
                                    $sections[$current_section] = trim($section_content);
                                } elseif (empty($sections) && !empty($section_content)) {
                                    // This is intro content before first heading
                                    $intro_content = trim($section_content);
                                }

                                // Strip colon for display
                                $current_section = rtrim($line, ':');
                                $section_content = '';
                            } else {
                                // Add line to current section content
                                $section_content .= $line . "\n";
                            }
                        }

                        // Don't forget the last section
                        if (!empty($current_section)) {
                            $sections[$current_section] = trim($section_content);
                        } elseif (empty($sections) && !empty($section_content)) {
                            // No headings found, treat all as intro
                            $intro_content = trim($section_content);
                        }

                        // If no sections found and no intro, treat entire description as intro
                        if (empty($sections) && empty($intro_content) && !empty($display_description)) {
                            $intro_content = $display_description;
                        }

                        // Render sections
                        ?>
                        <?php if (!empty($intro_content)): ?>
                            <div class="desc-intro">
                                <?= nl2br(htmlspecialchars($intro_content)) ?>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($sections as $title => $content): ?>
                            <div class="desc-section">
                                <h3 class="desc-section-title"><?= htmlspecialchars($title) ?></h3>
                                <div class="desc-section-content">
                                    <?php
                                    $content_lines = explode("\n", $content);
                                    $non_empty_lines = [];
                                    foreach ($content_lines as $content_line) {
                                        $content_line = trim($content_line);
                                        if (!empty($content_line)) {
                                            $non_empty_lines[] = $content_line;
                                        }
                                    }

                                    // If only one line, render as paragraph
                                    if (count($non_empty_lines) === 1) {
                                        echo nl2br(htmlspecialchars($non_empty_lines[0]));
                                    }
                                    // If multiple lines, render as bullet list
                                    else {
                                    ?>
                                        <ul>
                                            <?php foreach ($non_empty_lines as $content_line): ?>
                                                <li><?= htmlspecialchars($content_line) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="add-to-cart-error" class="add-to-cart-error"></div>

                    <button class="btn btn-primary btn-lg btn-full add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <script>
        // Gallery Navigation
        const gallerySlides = document.querySelectorAll('.gallery-slide');
        const thumbnails = document.querySelectorAll('.thumbnail');
        const prevBtn = document.getElementById('gallery-prev');
        const nextBtn = document.getElementById('gallery-next');
        let currentIndex = 0;

        function showSlide(index) {
            if (gallerySlides.length === 0) return;
            
            // Wrap around
            if (index >= gallerySlides.length) index = 0;
            if (index < 0) index = gallerySlides.length - 1;
            
            currentIndex = index;
            
            // Update slides
            gallerySlides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === currentIndex) slide.classList.add('active');
            });
            
            // Update thumbnails
            thumbnails.forEach((thumb, i) => {
                thumb.classList.remove('active');
                if (i === currentIndex) thumb.classList.add('active');
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => showSlide(currentIndex - 1));
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => showSlide(currentIndex + 1));
        }

        thumbnails.forEach((thumb, index) => {
            thumb.addEventListener('click', () => showSlide(index));
        });

        // Option buttons (color/size) with variant validation
        const productInfo = document.querySelector('.product-info-detail');
        const variantCombinations = productInfo ? JSON.parse(productInfo.dataset.variants || '[]') : [];

        // Helper function to get valid options for a selected option
        function getValidOptions(selectedType, selectedValue) {
            const validOptions = [];

            variantCombinations.forEach(combo => {
                if (combo[selectedType] === selectedValue) {
                    const otherType = selectedType === 'color' ? 'size' : 'color';
                    validOptions.push(combo[otherType]);
                }
            });

            return [...new Set(validOptions)]; // Remove duplicates
        }

        const optionBtns = document.querySelectorAll('.option-btn');
        optionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const group = this.parentElement;
                const isColor = this.classList.contains('color-btn');

                // Remove active from all buttons in this group
                group.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Get selected value
                const selectedValue = this.dataset.value;

                // Disable/enable options in the other group
                const otherGroupClass = isColor ? '.size-btn' : '.color-btn';
                const otherGroup = document.querySelector(otherGroupClass)?.parentElement;

                if (otherGroup) {
                    const validOptions = getValidOptions(isColor ? 'color' : 'size', selectedValue);
                    const otherBtns = otherGroup.querySelectorAll('.option-btn');

                    otherBtns.forEach(otherBtn => {
                        const otherValue = otherBtn.dataset.value;
                        const isValid = validOptions.includes(otherValue);

                        if (isValid) {
                            otherBtn.classList.remove('disabled');
                            otherBtn.disabled = false;
                        } else {
                            otherBtn.classList.add('disabled');
                            otherBtn.disabled = true;

                            // Clear selection if this button was active
                            if (otherBtn.classList.contains('active')) {
                                otherBtn.classList.remove('active');
                            }
                        }
                    });
                }
            });
        });

        // Add to Cart functionality
        const addToCartBtn = document.querySelector('.add-to-cart-btn');
        const addToCartError = document.getElementById('add-to-cart-error');

        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', async function() {
                const productId = this.dataset.productId;
                const selectedColor = document.querySelector('.color-btn.active')?.dataset.value || '';
                const selectedSize = document.querySelector('.size-btn.active')?.dataset.value || '';

                // Check if product has variants and none selected
                const hasColors = document.querySelectorAll('.color-btn').length > 0;
                const hasSizes = document.querySelectorAll('.size-btn').length > 0;

                if ((hasColors && !selectedColor) || (hasSizes && !selectedSize)) {
                    addToCartError.textContent = 'Please select a color/size';
                    addToCartError.style.display = 'block';
                    return;
                }

                addToCartError.style.display = 'none';
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Adding...';

                const personalizationText = document.getElementById('personalization_text')?.value || '';
                const result = await addToCart(productId, selectedColor, selectedSize, 1, personalizationText);

                addToCartBtn.disabled = false;
                addToCartBtn.textContent = 'Add to Cart';

                if (!result.success) {
                    addToCartError.textContent = result.message || 'Error adding to cart';
                    addToCartError.style.display = 'block';
                }
            });
        }

        // Character counter for personalization input
        const personalizationInput = document.getElementById('personalization_text');
        const charCount = document.getElementById('char-count');

        if (personalizationInput && charCount) {
            personalizationInput.addEventListener('input', function() {
                const currentLength = this.value.length;
                charCount.textContent = currentLength;
            });
        }
    </script>
</body>
</html>
