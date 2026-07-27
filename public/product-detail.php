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
                                         alt="Thumbnail <?= $index + 1 ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Product Info -->
                <div class="product-info-detail">
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
                            <span>Free Delivery</span>
                        </div>
                    </div>

                    <div class="product-detail-price">
                        <?= format_price($product['price']) ?>
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

                    <button class="btn btn-primary btn-lg btn-full add-to-cart-btn">
                        Add to Cart
                    </button>
                </div>
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

        // Option buttons (color/size)
        const optionBtns = document.querySelectorAll('.option-btn');
        optionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const group = this.parentElement;
                group.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
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
