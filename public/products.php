<?php
/**
 * Clicks Leather — Product Listing Page
 * Dynamic page for displaying products by category
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Get category from URL parameter
$category_slug = $_GET['category'] ?? '';

// Get category info from database if category slug is provided
$category = null;
$category_name = '';

if (!empty($category_slug)) {
    // Convert slug back to category name (e.g., 'ladies-bags' -> 'Ladies Bags')
    $category_name = ucwords(str_replace('-', ' ', $category_slug));
    $category = get_category_by_name($pdo, $category_name);

    // If category doesn't exist, show all products with a message
    if (!$category) {
        set_flash_message('info', 'Category not found. Showing all products.');
        $category = null;
        $category_name = '';
    }
}

// Get filter parameters from URL
$selected_types = $_GET['type'] ?? [];
$selected_price = $_GET['price'] ?? 'all';

// Ensure arrays for multiple selections
if (!is_array($selected_types)) {
    $selected_types = [$selected_types];
}

// Build query with filters
$sql = "SELECT p.*, c.naam as category_naam
        FROM products p
        JOIN categories c ON p.category_id = c.id";

$params = [];
$where_clauses = [];

// Add category filter if category is selected
if (!empty($category_name)) {
    $where_clauses[] = "c.naam = :category_name";
    $params[':category_name'] = $category_name;
}

// Add type filter
if (!empty($selected_types)) {
    $placeholders = [];
    foreach ($selected_types as $i => $type) {
        $placeholders[] = ':type_' . $i;
        $params[':type_' . $i] = $type;
    }
    $where_clauses[] = "p.type IN (" . implode(',', $placeholders) . ")";
}

// Add price filter
switch ($selected_price) {
    case 'under-100':
        $where_clauses[] = "p.price < 100";
        break;
    case '100-250':
        $where_clauses[] = "p.price >= 100 AND p.price <= 250";
        break;
    case 'over-250':
        $where_clauses[] = "p.price > 250";
        break;
    // 'all' - no filter
}

// Append WHERE clauses
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY p.naam ASC";

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch discounts for all products
$product_discounts = [];
foreach ($products as $product) {
    $discount = get_product_discount($pdo, $product['id'], $product['category_id']);
    if ($discount) {
        $product_discounts[$product['id']] = $discount;
    }
}

// Fetch gallery images for all products (batch query)
$product_ids = array_column($products, 'id');
$gallery_images_map = [];
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $gallery_stmt = $pdo->prepare(
        "SELECT product_id, image_path, sort_order, image_hash 
         FROM product_images 
         WHERE product_id IN ($placeholders) 
         AND media_type = 'image' 
         ORDER BY product_id, sort_order ASC"
    );
    $gallery_stmt->execute($product_ids);
    $all_gallery_images = $gallery_stmt->fetchAll();
    
    // Group by product_id and dedupe against main image (by content hash)
    foreach ($all_gallery_images as $gallery_img) {
        $pid = $gallery_img['product_id'];
        $main_image_hash = null;
        foreach ($products as $p) {
            if ($p['id'] == $pid) {
                $main_image_hash = $p['image_hash'];
                break;
            }
        }
        // Skip if gallery image hash matches main image hash (same content)
        // Only treat as duplicate when BOTH hashes are non-null AND equal
        if ($main_image_hash === null || $gallery_img['image_hash'] === null || $gallery_img['image_hash'] !== $main_image_hash) {
            // include it — not a confirmed duplicate
            if (!isset($gallery_images_map[$pid])) {
                $gallery_images_map[$pid] = [];
            }
            $gallery_images_map[$pid][] = $gallery_img['image_path'];
        }
    }
}

// Helper function to check if a filter value is selected
function is_filter_selected($value, $selected_array) {
    return in_array($value, $selected_array);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clicks Leather — <?= $category ? htmlspecialchars($category['naam']) . ' collection' : 'Shop All' ?>. Premium handcrafted leather goods.">
    <title><?= $category ? htmlspecialchars($category['naam']) : 'Shop All' ?> — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="<?= PUBLIC_URL ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= PUBLIC_URL ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= PUBLIC_URL ?>/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= PUBLIC_URL ?>/apple-touch-icon.png">
    <meta property="og:title" content="<?= $category ? htmlspecialchars($category['naam']) : 'Shop All' ?> — Clicks Leather">
    <meta property="og:description" content="Clicks Leather — <?= $category ? htmlspecialchars($category['naam']) . ' collection' : 'Shop All' ?>. Premium handcrafted leather goods.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/products.php<?= $category_slug ? '?category=' . htmlspecialchars($category_slug) : '' ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

    <!-- Product Listing Section -->
    <section class="products-page">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="<?= PUBLIC_URL ?>/index.php">Home</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?= htmlspecialchars($category['naam']) ?></span>
            </nav>

            <div class="products-layout">
                <!-- Left Sidebar: Filters -->
                <aside class="filters-sidebar">
                    <form method="GET" action="" id="filter-form">
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_slug) ?>">

                        <?php if ($category_name === 'Wallets'): ?>
                        <div class="filter-section">
                            <h3 class="filter-title">Type</h3>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Bifold Wallet" <?= is_filter_selected('Bifold Wallet', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Bifold Wallet</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Long Wallet" <?= is_filter_selected('Long Wallet', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Long Wallet</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Card Holder" <?= is_filter_selected('Card Holder', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Card Holder</span>
                                </label>
                            </div>
                        </div>
                        <?php elseif ($category_name === 'Leather Shoes'): ?>
                        <div class="filter-section">
                            <h3 class="filter-title">Type</h3>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Loafers" <?= is_filter_selected('Loafers', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Loafers</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Chelsea" <?= is_filter_selected('Chelsea', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Chelsea</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Long Boots" <?= is_filter_selected('Long Boots', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Long Boots</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Cowboy Boots" <?= is_filter_selected('Cowboy Boots', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Cowboy Boots</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="type[]" value="Oxford Shoes" <?= is_filter_selected('Oxford Shoes', $selected_types) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Oxford Shoes</span>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="filter-section">
                            <h3 class="filter-title">Price Range</h3>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="radio" name="price" value="all" <?= $selected_price === 'all' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>All Prices</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="price" value="under-100" <?= $selected_price === 'under-100' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Under $100</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="price" value="100-250" <?= $selected_price === '100-250' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>$100 - $250</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="price" value="over-250" <?= $selected_price === 'over-250' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Over $250</span>
                                </label>
                            </div>
                        </div>

                        <?php if (!empty($selected_types) || $selected_price !== 'all'): ?>
                            <div class="filter-section">
                                <a href="?category=<?= htmlspecialchars($category_slug) ?>" class="btn btn-outline btn-sm">Clear All Filters</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </aside>

                <!-- Right Side: Product Grid -->
                <main class="products-main">
                    <div class="products-header">
                        <h1 class="products-title"><?= htmlspecialchars($category['naam']) ?></h1>
                        <div class="products-header-right">
                            <p class="products-count"><?= count($products) ?> products</p>
                            <button class="filters-toggle-btn" id="filters-toggle-btn" aria-label="Toggle filters">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <line x1="4" y1="21" x2="4" y2="14"></line>
                                    <line x1="4" y1="10" x2="4" y2="3"></line>
                                    <line x1="12" y1="21" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12" y2="3"></line>
                                    <line x1="20" y1="21" x2="20" y2="16"></line>
                                    <line x1="20" y1="12" x2="20" y2="3"></line>
                                    <line x1="1" y1="14" x2="7" y2="14"></line>
                                    <line x1="9" y1="8" x2="15" y2="8"></line>
                                    <line x1="17" y1="16" x2="23" y2="16"></line>
                                </svg>
                                <span>Filters</span>
                            </button>
                        </div>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                            <h3>No products found</h3>
                            <p>We're working on adding more products to this category. Check back soon!</p>
                            <a href="<?= PUBLIC_URL ?>/index.php" class="btn btn-primary">Back to Home</a>
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                                <a href="<?= PUBLIC_URL ?>/product-detail.php?id=<?= $product['id'] ?>" class="product-card">
                                    <div class="product-image">
                                        <div class="product-image-carousel" data-product-id="<?= $product['id'] ?>">
                                            <div class="product-image-track">
                                                <?php if ($product['image_path']): ?>
                                                    <div class="product-image-slide">
                                                        <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['naam']) ?>">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="product-image-slide">
                                                        <div class="product-img-placeholder"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($product['has_personalization'] === 'yes'): ?>
                                            <span class="personalization-badge">Personalizable</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name"><?= htmlspecialchars($product['naam']) ?></h3>
                                        <?php if (isset($product_discounts[$product['id']])): 
                                            $discount = $product_discounts[$product['id']];
                                            $discounted_price = $product['price'] * (1 - $discount['discount_percent'] / 100);
                                        ?>
                                            <div class="product-price-container">
                                                <p class="product-price-original" style="text-decoration: line-through; color: var(--text-muted); font-size: 0.9rem;"><?= format_price($product['price']) ?></p>
                                                <p class="product-price" style="color: var(--color-primary); font-weight: 600;"><?= format_price($discounted_price) ?></p>
                                                <span class="discount-badge" style="background: #8B7355; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;"><?= number_format($discount['discount_percent'], 0) ?>% OFF</span>
                                            </div>
                                        <?php else: ?>
                                            <p class="product-price"><?= format_price($product['price']) ?></p>
                                        <?php endif; ?>
                                        <p class="product-description">
                                            <?php
                                            if (!empty($product['description'])) {
                                                $short_desc = substr(strip_tags($product['description']), 0, 90);
                                                echo htmlspecialchars($short_desc) . (strlen($short_desc) >= 90 ? '...' : '');
                                            } else {
                                                echo 'Handcrafted premium leather goods';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </section>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Filter Drawer Overlay -->
    <div class="filter-drawer-overlay" id="filter-drawer-overlay"></div>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/filter-drawer.js"></script>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Product Card Image Carousel
        (function() {
            const galleryImagesMap = <?= json_encode($gallery_images_map) ?>;
            
            document.querySelectorAll('.product-image-carousel').forEach(function(carousel) {
                const productId = parseInt(carousel.dataset.productId);
                const track = carousel.querySelector('.product-image-track');
                const galleryImages = galleryImagesMap[productId] || [];
                
                // Add gallery image slides
                galleryImages.forEach(function(imagePath) {
                    const slide = document.createElement('div');
                    slide.className = 'product-image-slide';
                    slide.innerHTML = `<img src="<?= PUBLIC_URL ?>/uploads/${imagePath}" alt="Gallery image" loading="lazy">`;
                    track.appendChild(slide);
                });
                
                const slides = track.querySelectorAll('.product-image-slide');
                let currentIndex = 0;
                let startX = 0;
                let isDragging = false;
                
                function updateCarousel() {
                    track.style.transform = `translateX(-${currentIndex * 100}%)`;
                }
                
                // Touch events
                carousel.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                });
                
                carousel.addEventListener('touchmove', function(e) {
                    if (!isDragging) return;
                    const diff = startX - e.touches[0].clientX;
                    if (Math.abs(diff) > 50) {
                        if (diff > 0 && currentIndex < slides.length - 1) {
                            currentIndex++;
                        } else if (diff < 0 && currentIndex > 0) {
                            currentIndex--;
                        }
                        updateCarousel();
                        isDragging = false;
                    }
                });
                
                carousel.addEventListener('touchend', function() {
                    isDragging = false;
                });
                
                // Wheel event for trackpad two-finger swipe (desktop)
                let isLocked = false;
                let lockStartTime = null;
                let hasSettled = false;
                let smallDeltaStreak = 0;
                let gestureEndTimer = null;
                
                carousel.addEventListener('wheel', function(e) {
                    // Only handle horizontal gestures (trackpad two-finger swipe)
                    if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
                        // Prevent default only for horizontal swipes to avoid blocking vertical page scroll
                        e.preventDefault();
                        
                        if (isLocked) {
                            // Track consecutive small-delta readings to detect genuine settling
                            if (Math.abs(e.deltaX) <= 3) {
                                smallDeltaStreak++;
                                if (smallDeltaStreak >= 3 && !hasSettled) {
                                    hasSettled = true;
                                }
                            } else {
                                // A real momentum value breaks the streak — wasn't actually settling
                                smallDeltaStreak = 0;
                            }
                            
                            // If settled and a new large delta arrives (fresh user gesture after settle)
                            if (hasSettled && Math.abs(e.deltaX) >= 15) {
                                // Unlock and re-process this event as a fresh gesture
                                isLocked = false;
                                lockStartTime = null;
                                hasSettled = false;
                                smallDeltaStreak = 0;
                                clearTimeout(gestureEndTimer);
                                // Fall through to re-process this event below
                            } 
                            // Safety net: absolute max lock duration (3000ms) as fallback
                            else if (lockStartTime && Date.now() - lockStartTime > 3000) {
                                isLocked = false;
                                lockStartTime = null;
                                hasSettled = false;
                                smallDeltaStreak = 0;
                                clearTimeout(gestureEndTimer);
                                // Fall through to re-process this event below
                            } 
                            // Still within a gesture (or its decaying tail) — just extend the quiet timer
                            else {
                                clearTimeout(gestureEndTimer);
                                gestureEndTimer = setTimeout(function() {
                                    isLocked = false;
                                    lockStartTime = null;
                                    hasSettled = false;
                                    smallDeltaStreak = 0;
                                }, 150);
                                return;
                            }
                        }
                        
                        // Only treat as the START of a genuinely new gesture if the delta is large enough
                        // (ignores small trailing inertia values that shouldn't trigger a fresh advance)
                        if (Math.abs(e.deltaX) < 15) {
                            return; // too small to be a real new swipe — ignore
                        }
                        
                        // First event of a new gesture - advance one slide and lock
                        isLocked = true;
                        lockStartTime = Date.now();
                        hasSettled = false;
                        smallDeltaStreak = 0;
                        
                        // deltaX > 0 = swipe right (next), deltaX < 0 = swipe left (previous)
                        if (e.deltaX > 0 && currentIndex < slides.length - 1) {
                            currentIndex++;
                        } else if (e.deltaX < 0 && currentIndex > 0) {
                            currentIndex--;
                        }
                        updateCarousel();
                        
                        // Start "gesture end" timer - will be reset by subsequent events
                        gestureEndTimer = setTimeout(function() {
                            isLocked = false;
                            lockStartTime = null;
                            hasSettled = false;
                            smallDeltaStreak = 0;
                        }, 150);
                    }
                }, { passive: false });
            });
        })();
    </script>
    </div>
</body>
</html>
