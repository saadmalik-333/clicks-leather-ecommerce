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
    $category_name = str_replace('-', ' ', ucwords($category_slug));
    $category = get_category_by_name($pdo, $category_name);

    // If category doesn't exist, show all products with a message
    if (!$category) {
        set_flash_message('info', 'Category not found. Showing all products.');
        $category = null;
        $category_name = '';
    }
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
        JOIN categories c ON p.category_id = c.id";

$params = [];
$where_clauses = [];

// Add category filter if category is selected
if (!empty($category_name)) {
    $where_clauses[] = "c.naam = :category_name";
    $params[':category_name'] = $category_name;
}

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
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

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

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>

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
    </script>
</body>
</html>
