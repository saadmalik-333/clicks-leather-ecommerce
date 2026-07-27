<?php
/**
 * Clicks Leather — Product Listing Page
 * Dynamic page for displaying products by category
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Get category from URL parameter
$category_slug = $_GET['category'] ?? '';

// Convert slug back to category name (e.g., 'ladies-bags' -> 'Ladies Bags')
$category_name = str_replace('-', ' ', ucwords($category_slug));

// Get category info from database
$category = get_category_by_name($pdo, $category_name);

// If category doesn't exist, redirect to homepage
if (!$category) {
    set_flash_message('error', 'Category not found.');
    redirect(PUBLIC_URL . '/index.php');
}

// Get filter parameters from URL
$selected_colors = $_GET['color'] ?? [];
$selected_materials = $_GET['material'] ?? [];
$selected_price = $_GET['price'] ?? 'all';

// Ensure arrays for multiple selections
if (!is_array($selected_colors)) {
    $selected_colors = [$selected_colors];
}
if (!is_array($selected_materials)) {
    $selected_materials = [$selected_materials];
}

// Build query with filters
$sql = "SELECT p.*, c.naam as category_naam 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE c.naam = :category_name";

$params = [':category_name' => $category_name];
$where_clauses = [];

// Add color filter
if (!empty($selected_colors)) {
    $placeholders = [];
    foreach ($selected_colors as $i => $color) {
        $placeholders[] = ':color_' . $i;
        $params[':color_' . $i] = $color;
    }
    $where_clauses[] = "p.color IN (" . implode(',', $placeholders) . ")";
}

// Add material filter
if (!empty($selected_materials)) {
    $placeholders = [];
    foreach ($selected_materials as $i => $material) {
        $placeholders[] = ':material_' . $i;
        $params[':material_' . $i] = $material;
    }
    $where_clauses[] = "p.material IN (" . implode(',', $placeholders) . ")";
}

// Add price filter
switch ($selected_price) {
    case 'under-50':
        $where_clauses[] = "p.price < 50";
        break;
    case '50-100':
        $where_clauses[] = "p.price >= 50 AND p.price <= 100";
        break;
    case 'over-100':
        $where_clauses[] = "p.price > 100";
        break;
    // 'all' - no filter
}

// Append WHERE clauses
if (!empty($where_clauses)) {
    $sql .= " AND " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY p.naam ASC";

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

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
    <meta name="description" content="Clicks Leather — <?= htmlspecialchars($category['naam']) ?> collection. Premium handcrafted leather goods.">
    <title><?= htmlspecialchars($category['naam']) ?> — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
</head>
<body>
    <!-- Sticky Header Container -->
    <div class="header-container" id="site-header">
        
        <!-- Announcement Bar (Tier 1) -->
        <div class="announcement-bar">
            <span>60 DAY RETURNS</span>
            <span class="separator">|</span>
            <span>WORLDWIDE SHIPPING</span>
            <span class="separator">|</span>
            <span>1 YEAR WARRANTY</span>
        </div>

        <!-- Main Header (Tier 2) -->
        <header class="main-header">
            <div class="header-left">
            </div>
            
            <div class="header-center">
                <a href="<?= PUBLIC_URL ?>/index.php" class="header-logo-link">
                    <img src="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png" alt="Clicks Leather" class="header-logo-img">
                </a>
            </div>
            
            <div class="header-right">
                <div class="header-icons">
                    <a href="#" class="icon-link" title="Search">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </a>
                    
                    <?php if (is_logged_in()): ?>
                        <a href="<?= is_admin() ? ADMIN_URL . '/dashboard.php' : PUBLIC_URL . '/account.php' ?>" class="icon-link" title="Account">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </a>
                    <?php else: ?>
                        <a href="<?= PUBLIC_URL ?>/login.php" class="icon-link" title="Account">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </a>
                    <?php endif; ?>
                    
                    <a href="#" class="icon-link" title="Help">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </a>
                    
                    <a href="#" class="icon-link cart-link" title="Bag">
                        <div class="cart-icon-wrapper">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                            <span class="cart-count">0</span>
                        </div>
                    </a>
                </div>
                <a href="#newsletter" class="btn btn-primary btn-sm subscribe-btn">SUBSCRIBE AND GET 10% OFF</a>
            </div>
        </header>

        <!-- Navigation Bar (Tier 3) -->
        <nav class="main-nav">
            <ul class="nav-categories">
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=wallets">WALLETS</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=ladies-bags">LADIES BAGS</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=leather-jackets">LEATHER JACKETS</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=laptop-bags">LAPTOP BAGS</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=backpacks">BACKPACKS</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=duffel-bags">DUFFEL BAGS</a></li>
                <li><a href="<?= PUBLIC_URL ?>/products.php?category=leather-shoes">LEATHER SHOES</a></li>
            </ul>
        </nav>
    </div>

    <!-- Flash Messages -->
    <div style="position:fixed; top:80px; right:20px; z-index:1001; max-width:400px;">
        <?= display_flash_message() ?>
    </div>

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
                        
                        <div class="filter-section">
                            <h3 class="filter-title">Color</h3>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="color[]" value="black" <?= is_filter_selected('black', $selected_colors) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Black</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="color[]" value="brown" <?= is_filter_selected('brown', $selected_colors) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Brown</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="color[]" value="tan" <?= is_filter_selected('tan', $selected_colors) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Tan</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="color[]" value="cognac" <?= is_filter_selected('cognac', $selected_colors) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Cognac</span>
                                </label>
                            </div>
                        </div>

                        <div class="filter-section">
                            <h3 class="filter-title">Material</h3>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="material[]" value="full-grain" <?= is_filter_selected('full-grain', $selected_materials) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Full Grain</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="material[]" value="top-grain" <?= is_filter_selected('top-grain', $selected_materials) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Top Grain</span>
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="material[]" value="genuine" <?= is_filter_selected('genuine', $selected_materials) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Genuine</span>
                                </label>
                            </div>
                        </div>

                        <div class="filter-section">
                            <h3 class="filter-title">Price Range</h3>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="radio" name="price" value="all" <?= $selected_price === 'all' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>All Prices</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="price" value="under-50" <?= $selected_price === 'under-50' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Under $50</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="price" value="50-100" <?= $selected_price === '50-100' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>$50 - $100</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="price" value="over-100" <?= $selected_price === 'over-100' ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span>Over $100</span>
                                </label>
                            </div>
                        </div>

                        <?php if (!empty($selected_colors) || !empty($selected_materials) || $selected_price !== 'all'): ?>
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
                        <p class="products-count"><?= count($products) ?> products</p>
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
                                    <div class="product-image <?php echo empty($product['image_path_alt']) ? 'no-alt' : ''; ?>">
                                        <?php if ($product['image_path']): ?>
                                            <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['naam']) ?>" class="product-img-main">
                                        <?php else: ?>
                                            <div class="product-img-placeholder product-img-main"></div>
                                        <?php endif; ?>
                                        <?php if (!empty($product['image_path_alt'])): ?>
                                            <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path_alt']) ?>" alt="<?= htmlspecialchars($product['naam']) ?> - Alternate" class="product-img-alt">
                                        <?php endif; ?>
                                        <?php if ($product['has_personalization'] === 'yes'): ?>
                                            <span class="personalization-badge">Personalizable</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name"><?= htmlspecialchars($product['naam']) ?></h3>
                                        <p class="product-price"><?= format_price($product['price']) ?></p>
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

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="<?= PUBLIC_URL ?>/index.php" class="footer-logo-link">
                    <img src="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png" alt="Clicks Leather" class="footer-logo-img">
                </a>
                <p>Premium handcrafted leather goods.</p>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="<?= PUBLIC_URL ?>/products.php?category=wallets">Wallets</a></li>
                    <li><a href="<?= PUBLIC_URL ?>/products.php?category=ladies-bags">Bags</a></li>
                    <li><a href="#">Accessories</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Journal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Shipping & Returns</a></li>
                    <li><a href="#">Warranty</a></li>
                </ul>
            </div>
            <div class="footer-col newsletter-col">
                <h4>Newsletter</h4>
                <p>Join our list for 10% off your first order.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Your email address" required>
                    <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Clicks Leather. All rights reserved. Handcrafted with ❤️</p>
        </div>
    </footer>

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

        // Multi-Stage Header Scroll Effect with Hysteresis
        let isTicking = false;
        let currentStage = 1;

        window.addEventListener('scroll', function() {
            if (!isTicking) {
                window.requestAnimationFrame(function() {
                    const header = document.getElementById('site-header');
                    const currentScrollY = window.scrollY;
                    
                    const stage2Activate = 40;
                    const stage2Deactivate = 20;
                    const stage3Activate = 160;
                    const stage3Deactivate = 140;
                    
                    if (currentStage === 1 && currentScrollY < stage2Activate) {
                        header.style.boxShadow = 'none';
                        header.classList.remove('stage-2', 'stage-3');
                    }
                    else if (currentStage === 1 && currentScrollY >= stage2Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                        currentStage = 2;
                    }
                    else if (currentStage === 2 && currentScrollY >= stage2Deactivate && currentScrollY < stage3Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                    }
                    else if (currentStage === 2 && currentScrollY < stage2Deactivate) {
                        header.style.boxShadow = 'none';
                        header.classList.remove('stage-2', 'stage-3');
                        currentStage = 1;
                    }
                    else if ((currentStage === 2 || currentStage === 1) && currentScrollY >= stage3Activate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2', 'stage-3');
                        currentStage = 3;
                    }
                    else if (currentStage === 3 && currentScrollY >= stage3Deactivate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2', 'stage-3');
                    }
                    else if (currentStage === 3 && currentScrollY < stage3Deactivate) {
                        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                        header.classList.add('stage-2');
                        header.classList.remove('stage-3');
                        currentStage = 2;
                    }
                    
                    isTicking = false;
                });
                isTicking = true;
            }
        });
    </script>
</body>
</html>
