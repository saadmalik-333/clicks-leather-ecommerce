<?php
/**
 * Clicks Leather — Product Detail Page
 */
ob_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle POST requests (review submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // User must be logged in
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to submit a review.');
        redirect(PUBLIC_URL . '/login.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_text = isset($_POST['review_text']) ? clean_input($_POST['review_text']) : '';
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        set_flash_message('error', 'Please select a rating between 1 and 5 stars.');
        redirect(PUBLIC_URL . '/product-detail.php?id=' . $product_id);
    }
    
    // Check verified purchase
    $purchase_stmt = $pdo->prepare("
        SELECT oi.* FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = :user_id 
        AND oi.product_id = :product_id
        AND o.status = 'delivered'
        LIMIT 1
    ");
    $purchase_stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
    if (!$purchase_stmt->fetch()) {
        set_flash_message('error', 'You must purchase this product before reviewing it.');
        redirect(PUBLIC_URL . '/product-detail.php?id=' . $product_id);
    }
    
    // Insert review (UNIQUE constraint will prevent duplicates)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, rating, review_text)
            VALUES (:product_id, :user_id, :rating, :review_text)
        ");
        $stmt->execute([
            ':product_id' => $product_id,
            ':user_id' => $user_id,
            ':rating' => $rating,
            ':review_text' => $review_text
        ]);
        set_flash_message('success', 'Thank you for your review!');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry (UNIQUE constraint)
            set_flash_message('error', 'You have already reviewed this product.');
        } else {
            set_flash_message('error', 'An error occurred while submitting your review. Please try again.');
        }
    }
    
    redirect(PUBLIC_URL . '/product-detail.php?id=' . $product_id);
}

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

// Fetch gallery images/videos
$gallery_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC");
$gallery_stmt->execute([':product_id' => $product_id]);
$gallery_images = $gallery_stmt->fetchAll();

// Fetch color images for this product
$color_images_stmt = $pdo->prepare("SELECT color, image_path FROM product_color_images WHERE product_id = :product_id");
$color_images_stmt->execute([':product_id' => $product_id]);
$color_images = $color_images_stmt->fetchAll();

// Build color-to-image lookup object (case-insensitive: keys stored in lowercase)
$color_image_lookup = [];
foreach ($color_images as $img) {
    $color_image_lookup[strtolower($img['color'])] = $img['image_path'];
}

// Fetch variants with stock
$variants_stmt = $pdo->prepare("SELECT size, color, hex_code, stock_quantity FROM product_variants WHERE product_id = :product_id");
$variants_stmt->execute([':product_id' => $product_id]);
$variants = $variants_stmt->fetchAll();

// Extract unique colors and sizes
$colors = array_unique(array_column($variants, 'color'));
$sizes = array_unique(array_column($variants, 'size'));
$colors = array_filter($colors);
$sizes = array_filter($sizes);

// Color name to hex code mapping (fallback for colors without hex_code in database)
$color_hex_map = [
    'Dark Brown' => '#8B4513',
    'Brown' => '#A52A2A',
    'Red' => '#C0392B',
    'Blue' => '#2980B9',
    'Black' => '#000000',
    'White' => '#FFFFFF',
    'Grey' => '#808080',
    'Green' => '#27AE60',
    'Pink' => '#E91E63',
    'Tan' => '#D2B48C',
    'Beige' => '#F5F5DC',
    'Navy' => '#000080',
    'Burgundy' => '#800020',
    'Camel' => '#C19A6B',
    'Cognac' => '#9F381D',
    'Olive' => '#808000',
    'Purple' => '#800080',
    'Yellow' => '#FFD700',
    'Orange' => '#FF8C00',
];

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

// Build variant data for JavaScript (with stock)
$variant_data = [];
foreach ($variants as $variant) {
    $variant_data[] = [
        'size' => $variant['size'],
        'color' => $variant['color'],
        'stock' => $variant['stock_quantity']
    ];
}

// Fetch related products from same category (excluding current product)
$related_stmt = $pdo->prepare("
    SELECT p.*, c.naam as category_naam 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = :category_id 
    AND p.id != :product_id 
    ORDER BY p.created_at DESC 
    LIMIT 4
");
$related_stmt->execute([':category_id' => $product['category_id'], ':product_id' => $product_id]);
$related_products = $related_stmt->fetchAll();

// Fetch reviews for this product
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.naam as reviewer_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = :product_id AND r.is_hidden = 0
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([':product_id' => $product_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$average_rating = 0;
$review_count = count($reviews);
if ($review_count > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $average_rating = round($total_rating / $review_count, 1);
}

// Fetch description images for this product
$description_images_stmt = $pdo->prepare("SELECT * FROM product_description_images WHERE product_id = :product_id ORDER BY sort_order ASC");
$description_images_stmt->execute([':product_id' => $product_id]);
$description_images = $description_images_stmt->fetchAll();

// Check if user can review this product
$user_can_review = false;
$user_already_reviewed = false;
$user_has_purchased = false;
$existing_user_review = null;
$current_user_id = is_logged_in() ? $_SESSION['user_id'] : null;
$should_auto_popup = false; // Initialize to false for logged-out users

if ($current_user_id) {
    // Check if user has purchased this product (verified purchase)
    $purchase_stmt = $pdo->prepare("
        SELECT oi.* FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = :user_id 
        AND oi.product_id = :product_id
        AND o.status = 'delivered'
        LIMIT 1
    ");
    $purchase_stmt->execute([':user_id' => $current_user_id, ':product_id' => $product_id]);
    $user_has_purchased = $purchase_stmt->fetch() !== false;
    
    // Check if user already reviewed this product
    $existing_review_stmt = $pdo->prepare("
        SELECT * FROM reviews 
        WHERE product_id = :product_id AND user_id = :user_id
    ");
    $existing_review_stmt->execute([':product_id' => $product_id, ':user_id' => $current_user_id]);
    $existing_user_review = $existing_review_stmt->fetch();
    $user_already_reviewed = $existing_user_review !== false;
    
    $user_can_review = $user_has_purchased && !$user_already_reviewed;
    
    // Determine if auto-popup should show for this visit
    $should_auto_popup = $user_can_review;
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
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="<?= PUBLIC_URL ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= PUBLIC_URL ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= PUBLIC_URL ?>/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= PUBLIC_URL ?>/apple-touch-icon.png">
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

        /* Color Swatches Styles */
        .color-swatches {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .color-swatch {
            width: 28px;
            height: 28px;
            padding: 0;
            min-width: 28px;
            min-height: 28px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
            background-color: #CCCCCC;
            background: none;
            font-size: 0;
            color: transparent;
        }

        .color-swatch:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .color-swatch.active {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px var(--bg-card), 0 0 0 4px var(--color-primary);
        }

        .color-swatch-check {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .color-swatch.active .color-swatch-check {
            display: block;
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

        /* Lightbox Styles */
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .lightbox.active {
            display: flex;
        }

        .lightbox-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Video Gallery Styles */
        .gallery-slide video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Video thumbnail styles */
        .thumbnail-video-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            border-radius: var(--radius-sm);
        }

        .thumbnail-video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumbnail-play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            pointer-events: none;
        }

        .gallery-slide {
            cursor: pointer;
        }

        /* Description Images Styles */
        .description-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .description-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        /* Product Description Accordion Styles */
        .product-description-accordion {
            margin-top: 1.5rem;
        }

        .product-desc-accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 0;
            cursor: pointer;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s ease;
        }

        .product-desc-accordion-header:hover {
            background: var(--bg-card);
        }

        .product-desc-accordion-header span:first-child {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .product-desc-accordion-header .product-desc-accordion-icon {
            font-size: 1.5rem;
            font-weight: 300;
            transition: transform 0.3s ease;
        }

        .product-desc-accordion-body {
            display: none;
            padding: 1.5rem 0;
            background: var(--bg-card);
        }

        .product-desc-accordion-body.active {
            display: block;
        }

        .product-desc-accordion-header.active .product-desc-accordion-icon {
            transform: rotate(45deg);
        }

        /* Reviews Styles */
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .review-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
        }

        .star {
            font-size: 1.25rem;
            color: var(--border-color);
        }

        .star-filled {
            color: #f59e0b;
        }

        .star-empty {
            color: var(--border-color);
        }

        .review-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .review-author {
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .review-text {
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Review Form Styles */
        .review-form-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .review-form-group {
            margin-bottom: 1rem;
        }

        .review-form-label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .star-rating-input {
            display: flex;
            gap: 0.5rem;
        }

        .star-rating-btn {
            font-size: 2rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--border-color);
            transition: color 0.2s ease;
            padding: 0;
        }

        .star-rating-btn:hover,
        .star-rating-btn.active {
            color: #f59e0b;
        }

        .review-form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 0.95rem;
            resize: vertical;
            min-height: 120px;
        }

        .review-form-textarea:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .review-message {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
        }

        .review-message-info {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* Reviews Modal Styles */
        .reviews-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .reviews-modal.active {
            display: flex;
        }

        .reviews-modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            max-width: 700px;
            max-height: 90vh;
            width: 100%;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
        }

        .reviews-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 36px;
            height: 36px;
            background: rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 50%;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .reviews-modal-close:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Reviews Slider Section Styles */
        .reviews-slider-section {
            padding: 4rem var(--space-xl);
            background: var(--bg-card);
        }

        .reviews-slider-section .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reviews-slider-section .section-header h2 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .reviews-slider-section .section-header p {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .reviews-slider-section .section-header .rating-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            flex-wrap: nowrap;
            white-space: nowrap;
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .reviews-slider-section .section-header .rating-summary .rating-summary-stars {
            color: #f59e0b;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .reviews-slider-container {
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
        }

        .reviews-slider {
            position: relative;
            background: transparent;
            border: none;
            border-radius: 0;
            overflow: hidden;
            padding: 0;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .reviews-slider-track {
            display: flex;
            gap: 1.5rem;
            transition: transform 0.5s ease;
            width: 100%;
            margin: 0 auto;
        }

        .reviews-slider-track.centered {
            justify-content: center;
        }

        .review-slider-card {
            flex: 0 0 100%;
            width: 100%;
            background: var(--bg-card-hover);
            padding: var(--space-2xl);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .review-slider-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        @media (min-width: 1025px) {
            .review-slider-card {
                flex: 0 0 calc(33.333% - 1rem);
                max-width: none;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .review-slider-card {
                flex: 0 0 calc(50% - 0.75rem);
                max-width: none;
            }
        }

        .review-slider-card .stars {
            font-size: 1.5rem;
            margin-bottom: var(--space-lg);
            letter-spacing: 0.1em;
        }

        .review-slider-card .star {
            color: var(--color-gold);
        }

        .review-slider-card .star-empty {
            color: #d1d5db;
        }

        .review-slider-card .review-text {
            font-family: var(--font-display);
            font-style: italic;
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
            position: relative;
        }

        .review-slider-card .review-text::before {
            content: '"';
            font-family: var(--font-display);
            font-size: 3rem;
            color: var(--color-primary);
            opacity: 0.2;
            position: absolute;
            top: -1.5rem;
            left: 0;
        }

        .review-slider-card .reviewer-name {
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.95rem;
            color: var(--text-secondary);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .review-slider-card .review-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .review-slider-card-header {
            display: none; /* Hide old header structure */
        }

        .reviews-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .reviews-nav:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .reviews-nav:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .reviews-nav:disabled:hover {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: none;
        }

        .reviews-prev {
            left: var(--space-md);
        }

        .reviews-next {
            right: var(--space-md);
        }

        .reviews-slider-footer {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .reviews-slider-footer .btn {
            padding: 0.75rem 2rem;
            font-size: 0.95rem;
        }

        .reviews-empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .reviews-empty-state p {
            font-size: 1rem;
        }

        /* All Reviews Modal Styles */
        .all-reviews-modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }

        .all-reviews-modal-content h2 {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .all-reviews-modal-content .rating-summary {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .all-reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .all-reviews-list .review-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
        }

        @media (max-width: 768px) {
            .reviews-slider-section .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .reviews-slider-track {
                width: 100%;
            }

            .review-slider-card {
                flex: 0 0 100%;
                width: 100%;
                border-right: none;
            }

            .reviews-prev {
                left: var(--space-sm);
            }

            .reviews-next {
                right: var(--space-sm);
            }
        }

        /* Hide related products navigation on desktop by default */
        .product-detail-page .related-products-nav {
            display: none;
        }

        /* Related Products Slider - Mobile Only */
        @media (max-width: 768px) {
            .product-detail-page .featured-grid {
                display: flex;
                gap: 1rem;
                width: 100%;
                transition: transform 0.5s ease;
            }

            .product-detail-page .featured-product-card {
                flex: 0 0 calc(50% - 0.5rem);
                max-width: none;
            }

            .product-detail-page .related-products-slider-container {
                position: relative;
                max-width: 100%;
                margin: 0 auto;
            }

            .product-detail-page .related-products-slider {
                position: relative;
                background: transparent;
                border: none;
                border-radius: 0;
                overflow: hidden;
                padding: 0;
                display: flex;
                justify-content: center;
                width: 100%;
            }

            .product-detail-page .related-products-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: 50%;
                cursor: pointer;
                z-index: 10;
                transition: all 0.3s ease;
            }

            .product-detail-page .related-products-nav:disabled {
                opacity: 0.3;
                cursor: not-allowed;
            }

            .product-detail-page .related-products-prev {
                left: var(--space-sm);
            }

            .product-detail-page .related-products-next {
                right: var(--space-sm);
            }

            .product-detail-page .featured-product-name {
                font-size: 0.95rem;
            }

            .product-detail-page .featured-product-price {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

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
                                    <?php if (($img['media_type'] ?? 'image') === 'video'): ?>
                                        <video src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>" 
                                               alt="<?= htmlspecialchars($display_title) ?>" 
                                               class="gallery-slide <?= $img === $gallery_images[0] ? 'active' : '' ?>"
                                               data-index="<?= array_search($img, $gallery_images) ?>"
                                               controls muted loop playsinline></video>
                                    <?php else: ?>
                                        <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($display_title) ?>" 
                                             class="gallery-slide <?= $img === $gallery_images[0] ? 'active' : '' ?>"
                                             data-index="<?= array_search($img, $gallery_images) ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php elseif ($product['image_path']): ?>
                                <?php if (($product['image_media_type'] ?? 'image') === 'video'): ?>
                                    <video src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" 
                                           alt="<?= htmlspecialchars($display_title) ?>" 
                                           class="gallery-slide active"
                                           controls muted loop playsinline></video>
                                <?php else: ?>
                                    <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($product['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($display_title) ?>" 
                                         class="gallery-slide active">
                                <?php endif; ?>
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
                                <?php if (($img['media_type'] ?? 'image') === 'video'): ?>
                                    <button class="thumbnail <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                                        <div class="thumbnail-video-wrapper">
                                            <video src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>" 
                                                   muted playsinline preload="metadata"></video>
                                            <div class="thumbnail-play-icon">▶</div>
                                        </div>
                                    </button>
                                <?php else: ?>
                                    <button class="thumbnail <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                                        <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($display_title) ?> - Thumbnail <?= $index + 1 ?>">
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Product Info -->
                <div class="product-info-detail" data-variants="<?= htmlspecialchars(json_encode($variant_combinations)) ?>" data-variant-stock="<?= htmlspecialchars(json_encode($variant_data)) ?>" data-color-images="<?= htmlspecialchars(json_encode($color_image_lookup)) ?>" data-category="<?= htmlspecialchars($product['category_naam']) ?>">
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
                        <?php if ($review_count > 0): ?>
                            <span>•</span>
                            <div class="trust-badge-item">
                                <span class="rating-summary-stars" style="color: #f59e0b;">★ <?= $average_rating ?></span>
                                <span>Ratings <?= $review_count ?></span>
                            </div>
                        <?php endif; ?>
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
                            <div class="color-swatches">
                                <?php foreach ($colors as $color): ?>
                                    <?php 
                                        $hex_code = null;
                                        // Try to get hex from variant data first
                                        foreach ($variants as $variant) {
                                            if ($variant['color'] === $color && !empty($variant['hex_code'])) {
                                                $hex_code = $variant['hex_code'];
                                                break;
                                            }
                                        }
                                        // Fallback to mapping
                                        if (!$hex_code && isset($color_hex_map[$color])) {
                                            $hex_code = $color_hex_map[$color];
                                        }
                                        // Default fallback
                                        if (!$hex_code) {
                                            $hex_code = '#CCCCCC';
                                        }
                                    ?>
                                    <button class="option-btn color-swatch" 
                                            data-value="<?= htmlspecialchars($color) ?>" 
                                            style="background-color: <?= htmlspecialchars($hex_code) ?>"
                                            title="<?= htmlspecialchars($color) ?>">
                                        <span class="color-swatch-check">✓</span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($sizes)): ?>
                        <?php if ($product['category_naam'] === 'Leather Shoes'): ?>
                            <div class="product-option-group">
                                <h3 class="option-title">Size</h3>
                                <div class="size-selector-trigger" id="size-selector-trigger">
                                    <span id="selected-size-display">Select a size</span>
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 8l4 4 4-4"/>
                                    </svg>
                                </div>
                                <input type="hidden" name="selected_size" id="selected-size-value" value="">
                                <input type="hidden" name="selected_size_display" id="selected-size-display-value" value="">
                                <!-- Hidden size button for Add-to-Cart compatibility -->
                                <button class="option-btn size-btn" id="hidden-size-btn" data-value="" style="display: none;"></button>
                            </div>
                        <?php elseif ($product['category_naam'] !== 'Leather Shoes'): ?>
                            <div class="product-option-group">
                                <h3 class="option-title">Size</h3>
                                <div class="size-selector-trigger" id="size-selector-trigger">
                                    <span id="selected-size-display">Select a size</span>
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 8l4 4 4-4"/>
                                    </svg>
                                </div>
                                <input type="hidden" name="selected_size" id="selected-size-value" value="">
                                <input type="hidden" name="selected_size_display" id="selected-size-display-value" value="">
                                <!-- Hidden size button for Add-to-Cart compatibility -->
                                <button class="option-btn size-btn" id="hidden-size-btn" data-value="" style="display: none;"></button>
                            </div>
                        <?php else: ?>
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
                    <?php endif; ?>

                    <?php if ($product['category_naam'] === 'Leather Jackets'): ?>
                        <div class="product-option-group">
                            <span class="custom-size-link">
                                Don't know your size? 
                                <a href="#" id="custom-size-btn">Enter custom measurements</a>
                            </span>
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

                    <div id="add-to-cart-error" class="add-to-cart-error"></div>

                    <button class="btn btn-primary btn-lg btn-full add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                        Add to Cart
                    </button>

                    <div class="product-description-accordion">
                        <div class="product-desc-accordion-header" data-target="description-content">
                            <span>Description</span>
                            <span class="product-desc-accordion-icon">+</span>
                        </div>
                        <div class="product-desc-accordion-body" id="description-content">
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

                            <?php if (!empty($description_images)): ?>
                                <div class="description-images">
                                    <?php foreach ($description_images as $desc_img): ?>
                                        <img src="<?= PUBLIC_URL ?>/uploads/<?= htmlspecialchars($desc_img['image_path']) ?>" 
                                             alt="Description image" 
                                             class="description-image">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Slider Section -->
    <section class="reviews-slider-section">
        <div class="section-header">
            <h2>Customer Reviews</h2>
            <?php if ($review_count > 0): ?>
                <div class="rating-summary">
                    <span class="rating-summary-stars">★ <?= $average_rating ?></span>
                    <span>(<?= $review_count ?> review<?= $review_count !== 1 ? 's' : '' ?>)</span>
                </div>
            <?php else: ?>
                <p>No reviews yet</p>
            <?php endif; ?>
        </div>
        
        <?php if (empty($reviews)): ?>
            <div class="reviews-empty-state">
                <p>Be the first to review this product!</p>
            </div>
        <?php else: ?>
            <div class="reviews-slider-container">
                <button class="reviews-nav reviews-prev" id="reviews-prev" disabled>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"/>
                    </svg>
                </button>
                <div class="reviews-slider">
                    <div class="reviews-slider-track" id="reviews-slider-track">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-slider-card">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $review['rating'] ? '' : 'star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <?php if (!empty($review['review_text'])): ?>
                                    <div class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
                                <?php endif; ?>
                                <div class="reviewer-name">- <?= htmlspecialchars($review['reviewer_name']) ?></div>
                                <div class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="reviews-nav reviews-next" id="reviews-next">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"/>
                    </svg>
                </button>
            </div>
            <div class="reviews-slider-footer">
                <button class="btn btn-secondary" id="all-reviews-btn">View All Reviews</button>
            </div>
        <?php endif; ?>
    </section>

    <!-- You May Also Like Section -->
    <?php if (!empty($related_products)): ?>
    <section class="featured-section product-detail-page">
        <div class="section-header">
            <h2>You May Also Like</h2>
            <p>More <?= htmlspecialchars($product['category_naam']) ?> from our collection</p>
        </div>
        
        <div class="related-products-slider-container">
            <button class="related-products-nav related-products-prev" id="related-products-prev" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"/>
                </svg>
            </button>
            
            <div class="related-products-slider">
                <div class="featured-grid related-products-track" id="related-products-track">
                    <?php foreach ($related_products as $related_product):
                        $related_slug = strtolower(str_replace(' ', '-', $related_product['naam']));
                        $related_image_url = $related_product['image_path'] ? PUBLIC_URL . '/uploads/' . $related_product['image_path'] : PUBLIC_URL . '/img/placeholder.jpg';
                        $related_category_slug = strtolower(str_replace(' ', '-', $related_product['category_naam']));
                    ?>
                        <a href="<?= PUBLIC_URL ?>/product-detail.php?id=<?= $related_product['id'] ?>" class="featured-product-card">
                            <div class="featured-product-image">
                                <img src="<?= $related_image_url ?>" alt="<?= htmlspecialchars($related_product['naam']) ?>">
                            </div>
                            <div class="featured-product-info">
                                <h3 class="featured-product-name"><?= htmlspecialchars($related_product['naam']) ?></h3>
                                <p class="featured-product-price"><?= format_price($related_product['price']) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button class="related-products-nav related-products-next" id="related-products-next">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"/>
                </svg>
            </button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Trust Bar Section -->
    <section class="trust-bar-section">
        <div class="trust-bar-grid">
            <div class="trust-bar-item">
                <div class="trust-bar-icon-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <div class="trust-bar-label">
                    <?= $shipping_is_free === 'yes' ? 'Free Shipping' : 'Shipping from ' . format_price($shipping_flat_cost) ?>
                </div>
                <div class="trust-bar-subtitle">On All Orders</div>
            </div>
            <div class="trust-bar-item">
                <div class="trust-bar-icon-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <div class="trust-bar-label">Secure Payment</div>
                <div class="trust-bar-subtitle">100% Safe</div>
            </div>
            <div class="trust-bar-item">
                <div class="trust-bar-icon-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </div>
                <div class="trust-bar-label">Easy Returns</div>
                <div class="trust-bar-subtitle">Hassle Free</div>
            </div>
            <div class="trust-bar-item">
                <div class="trust-bar-icon-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <div class="trust-bar-label">24/7 Support</div>
                <div class="trust-bar-subtitle">We're Here to Help</div>
            </div>
        </div>
    </section>

    <!-- Reviews Modal -->
    <div class="reviews-modal" id="reviews-modal">
        <div class="reviews-modal-content">
            <button class="reviews-modal-close" id="reviews-modal-close" aria-label="Close reviews">&times;</button>
            
            <h2 style="margin-bottom: 1rem;">Write a Review</h2>
            
            <?php if (!$current_user_id): ?>
                <div class="review-message review-message-info">
                    <p>Please <a href="<?= PUBLIC_URL ?>/login.php" style="color: inherit; text-decoration: underline;">log in</a> to review this product.</p>
                </div>
            <?php elseif (!$user_has_purchased): ?>
                <div class="review-message review-message-info">
                    <p>You must have received this product before you can review it. Only verified purchasers who have received their order can submit reviews.</p>
                </div>
            <?php elseif ($user_already_reviewed): ?>
                <div class="review-message review-message-info">
                    <p>You have already reviewed this product. Here is your review:</p>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $existing_user_review['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="review-date"><?= date('M d, Y', strtotime($existing_user_review['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($existing_user_review['review_text'])): ?>
                        <div class="review-text">
                            <?= nl2br(htmlspecialchars($existing_user_review['review_text'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="<?= PUBLIC_URL ?>/product-detail.php?id=<?= $product_id ?>" class="review-form-section">
                    <input type="hidden" name="submit_review" value="1">
                    
                    <div class="review-form-group">
                        <label class="review-form-label">Rating *</label>
                        <div class="star-rating-input" id="star-rating-input">
                            <button type="button" class="star-rating-btn" data-rating="1">★</button>
                            <button type="button" class="star-rating-btn" data-rating="2">★</button>
                            <button type="button" class="star-rating-btn" data-rating="3">★</button>
                            <button type="button" class="star-rating-btn" data-rating="4">★</button>
                            <button type="button" class="star-rating-btn" data-rating="5">★</button>
                        </div>
                        <input type="hidden" name="rating" id="rating-input" required>
                    </div>
                    
                    <div class="review-form-group">
                        <label class="review-form-label" for="review_text">Review (optional)</label>
                        <textarea name="review_text" id="review_text" class="review-form-textarea" maxlength="1000" placeholder="Share your experience with this product..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- All Reviews Modal -->
    <div class="reviews-modal" id="all-reviews-modal">
        <div class="reviews-modal-content all-reviews-modal-content">
            <button class="reviews-modal-close" id="all-reviews-modal-close" aria-label="Close all reviews">&times;</button>
            
            <h2>All Customer Reviews</h2>
            <div class="rating-summary">
                <?= $average_rating ?> ★ (<?= $review_count ?> review<?= $review_count !== 1 ? 's' : '' ?>)
            </div>
            
            <div class="all-reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $review['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                        <div class="review-author">
                            <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                        </div>
                        <?php if (!empty($review['review_text'])): ?>
                            <div class="review-text">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Size Selector Modal -->
    <?php if ($product['category_naam'] === 'Leather Shoes'): ?>
    <div class="size-selector-overlay" id="size-selector-overlay"></div>
    <div class="size-selector-modal" id="size-selector-modal">
        <div class="size-selector-content">
            <button class="size-selector-close" id="size-selector-close" aria-label="Close">&times;</button>
            
            <!-- Step 1: Size Format -->
            <div class="size-selector-step" id="size-step-1">
                <h2>Select Size Format</h2>
                <div class="size-format-buttons">
                    <button class="size-format-btn" data-format="US">US</button>
                    <button class="size-format-btn" data-format="UK">UK</button>
                    <button class="size-format-btn" data-format="EU">EU</button>
                </div>
                <button class="size-format-btn size-custom-btn" id="size-custom-btn">
                    Custom Size
                </button>
                <button class="size-selector-cancel" id="size-selector-cancel">Cancel</button>
            </div>
            
            <!-- Step 2: Size List -->
            <div class="size-selector-step" id="size-step-2" style="display: none;">
                <button class="size-back-btn" id="size-back-btn">‹ Back to size format</button>
                <h2 id="size-list-title">Select Size</h2>
                <div class="size-list" id="size-list">
                    <!-- Sizes populated by JavaScript -->
                </div>
            </div>
            
            <!-- Step 3: Custom Measurements (Shoes) -->
            <div class="size-selector-step custom-measurements-content" id="size-step-3" style="display: none;">
                <button class="custom-measurements-back" id="shoe-measurements-back">‹ Back to size format</button>
                <h2>Custom Measurements</h2>
                <p class="custom-measurements-description">Follow these 3 steps to measure your exact foot size, then enter each measurement in its matching box below.</p>
                
                <!-- Step 1: Place Foot on Paper -->
                <div class="measurement-section">
                    <h3 class="measurement-step-heading">Step 1: Place Foot on Paper</h3>
                    <div class="custom-measurements-diagram">
                        <div class="diagram-image-wrapper">
                            <img src="<?= PUBLIC_URL ?>/img/size-guide/shoe-measurement-step1-place-foot.jpeg" 
                                 alt="Place foot on paper" 
                                 class="diagram-image">
                        </div>
                    </div>
                    <div class="measurement-instructions">
                        <ul>
                            <li>Place a plain sheet of paper on a flat, hard floor</li>
                            <li>Stand on the paper with your full weight on the foot</li>
                            <li>Trace around your heel and toes with a pencil, keeping the pencil perpendicular to the paper</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Step 2: Measure Foot Length (C) -->
                <div class="measurement-section">
                    <h3 class="measurement-step-heading">Step 2: Measure Foot Length (C)</h3>
                    <div class="custom-measurements-diagram">
                        <div class="diagram-image-wrapper">
                            <img src="<?= PUBLIC_URL ?>/img/size-guide/shoe-measurement-step2-length.jpeg" 
                                 alt="Measure foot length" 
                                 class="diagram-image">
                        </div>
                    </div>
                    <div class="measurement-instructions">
                        <ul>
                            <li>Mark the point behind your heel on the tracing</li>
                            <li>Mark the point at your longest toe on the tracing</li>
                            <li>Measure the straight-line distance between the two marks — that's C</li>
                        </ul>
                    </div>
                    <div class="measurement-cards">
                        <div class="measurement-card">
                            <div class="measurement-card-content">
                                <label class="measurement-label">C — Foot Length (cm)</label>
                                <input type="number" class="measurement-input" placeholder="e.g. 26.5" step="0.1" id="shoe-measurement-c">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Measure Foot Width & Girth (A & B) -->
                <div class="measurement-section">
                    <h3 class="measurement-step-heading">Step 3: Measure Foot Width & Girth (A & B)</h3>
                    <div class="custom-measurements-diagram">
                        <div class="diagram-image-wrapper">
                            <img src="<?= PUBLIC_URL ?>/img/size-guide/shoe-measurement-step3-width-girth.jpeg" 
                                 alt="Measure foot width and girth" 
                                 class="diagram-image">
                        </div>
                    </div>
                    <div class="measurement-instructions">
                        <ul>
                            <li><strong>A (orange band)</strong> — Wrap the tape around the widest part of the ball of your foot</li>
                            <li><strong>B (green band)</strong> — Wrap the tape a bit further back, around the instep/arch area</li>
                        </ul>
                    </div>
                    <div class="measurement-cards">
                        <div class="measurement-card">
                            <div class="measurement-card-content">
                                <label class="measurement-label">A — Foot Width (cm)</label>
                                <input type="number" class="measurement-input" placeholder="e.g. 24.0" step="0.1" id="shoe-measurement-a">
                            </div>
                        </div>
                        <div class="measurement-card">
                            <div class="measurement-card-content">
                                <label class="measurement-label">B — Foot Girth (cm)</label>
                                <input type="number" class="measurement-input" placeholder="e.g. 23.0" step="0.1" id="shoe-measurement-b">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="measurement-tip">
                    <strong>Tip for best results:</strong> Measure in the evening, when feet are most swollen — for a better fit.
                </div>
                
                <div class="measurement-notes">
                    <label class="measurement-label">Notes (optional)</label>
                    <textarea class="measurement-textarea" placeholder="Any other detail — wide feet, high arch, etc."></textarea>
                </div>
                
                <button class="btn btn-primary btn-full" id="save-shoe-measurements">Save Custom Measurements</button>
                <div class="measurement-help">
                    <span>Need help with sizing?</span>
                    <a href="<?= PUBLIC_URL ?>/contact.php" class="btn btn-outline">Message us</a>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($product['category_naam'] !== 'Leather Shoes'): ?>
    <div class="size-selector-overlay" id="size-selector-overlay"></div>
    <div class="size-selector-modal" id="size-selector-modal">
        <div class="size-selector-content">
            <button class="size-selector-close" id="size-selector-close" aria-label="Close">&times;</button>
            
            <!-- Single Step: Size List (no format selection) -->
            <div class="size-selector-step" id="size-step-2" style="display: block;">
                <h2 id="size-list-title">Select Size</h2>
                <div class="size-list" id="size-list">
                    <!-- Sizes populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Custom Measurements Modal (Leather Jackets) -->
    <?php if ($product['category_naam'] === 'Leather Jackets'): ?>
    <div class="size-selector-overlay" id="custom-measurements-overlay"></div>
    <div class="size-selector-modal" id="custom-measurements-modal">
        <div class="size-selector-content custom-measurements-content">
            <button class="size-selector-close" id="custom-measurements-close" aria-label="Close">&times;</button>
            
            <button class="custom-measurements-back" id="custom-measurements-back">‹ Back</button>
            <h2>Custom Fit Measurements</h2>
            <p class="custom-measurements-description">Follow the numbered points in the diagram below, then enter each measurement in the matching box.</p>
            
            <div class="custom-measurements-diagram">
                <div class="diagram-image-wrapper">
                    <img src="<?= PUBLIC_URL ?>/img/size-guide/jacket-measurements.jpeg" 
                         alt="Jacket measurement guide" 
                         class="diagram-image">
                </div>
            </div>
            
            <div class="measurement-cards">
                <div class="measurement-card">
                    <div class="measurement-badge">1</div>
                    <div class="measurement-card-content">
                        <label class="measurement-label">Chest</label>
                        <p class="measurement-instruction">Wrap the tape around the fullest part of your chest, right under your arms. Keep it level all around and snug against the body — not pulled tight.</p>
                        <input type="number" class="measurement-input" placeholder="e.g. 40" data-field="chest">
                    </div>
                </div>
                
                <div class="measurement-card">
                    <div class="measurement-badge">2</div>
                    <div class="measurement-card-content">
                        <label class="measurement-label">Shoulder Width</label>
                        <p class="measurement-instruction">With the tape flat across your back, measure the straight-line distance from the edge of one shoulder to the edge of the other, where a seam would normally sit.</p>
                        <input type="number" class="measurement-input" placeholder="e.g. 18" data-field="shoulder">
                    </div>
                </div>
                
                <div class="measurement-card">
                    <div class="measurement-badge">3</div>
                    <div class="measurement-card-content">
                        <label class="measurement-label">Sleeve Length</label>
                        <p class="measurement-instruction">Starting at the shoulder edge, run the tape down the outside of your arm to your wrist bone. Keep your arm slightly bent, the way it rests naturally.</p>
                        <input type="number" class="measurement-input" placeholder="e.g. 25" data-field="sleeve">
                    </div>
                </div>
                
                <div class="measurement-card">
                    <div class="measurement-badge">4</div>
                    <div class="measurement-card-content">
                        <label class="measurement-label">Biceps</label>
                        <p class="measurement-instruction">Wrap the tape around the fullest part of your upper arm, just below the shoulder. Let your arm hang relaxed — don't flex while measuring.</p>
                        <input type="number" class="measurement-input" placeholder="e.g. 13" data-field="biceps">
                    </div>
                </div>
                
                <div class="measurement-card">
                    <div class="measurement-badge">5</div>
                    <div class="measurement-card-content">
                        <label class="measurement-label">Waist</label>
                        <p class="measurement-instruction">Wrap the tape around your natural waistline, roughly where the jacket will close. Keep it level and comfortably snug, not tight.</p>
                        <input type="number" class="measurement-input" placeholder="e.g. 34" data-field="waist">
                    </div>
                </div>
                
                <div class="measurement-card">
                    <div class="measurement-badge">6</div>
                    <div class="measurement-card-content">
                        <label class="measurement-label">Jacket Length</label>
                        <p class="measurement-instruction">From the collar seam at the top of the shoulder, measure straight down the back to wherever you'd like the jacket to end.</p>
                        <input type="number" class="measurement-input" placeholder="e.g. 27" data-field="length">
                    </div>
                </div>
            </div>
            
            <div class="measurement-tip">
                <strong>Tip for best results:</strong> Measure over a light shirt, not bare skin, to match how the jacket will actually fit.
            </div>
            
            <div class="measurement-notes">
                <label class="measurement-label">Notes (optional)</label>
                <textarea class="measurement-textarea" placeholder="Anything else we should know — broad shoulders, prefer looser fit, etc."></textarea>
            </div>
            
            <button class="btn btn-primary btn-full" id="save-custom-measurements">Save Custom Measurements</button>
            <div class="measurement-help">
                <span>Need help with sizing?</span>
                <a href="<?= PUBLIC_URL ?>/contact.php" class="btn btn-outline">Message us</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                const isColor = this.classList.contains('color-swatch');

                // Remove active from all buttons in this group
                group.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Get selected value
                const selectedValue = this.dataset.value;

                // Disable/enable options in the other group
                const otherGroupClass = isColor ? '.size-btn' : '.color-swatch';
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
                const selectedColor = document.querySelector('.color-swatch.active')?.dataset.value || '';
                const selectedSize = document.querySelector('.size-btn.active')?.dataset.value || '';
                const selectedSizeDisplay = document.getElementById('selected-size-display-value')?.value || '';
                const hasColors = document.querySelectorAll('.color-swatch').length > 0;
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
                const result = await addToCart(productId, selectedColor, selectedSize, 1, personalizationText, selectedSizeDisplay);

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

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" id="lightbox-close" aria-label="Close lightbox">&times;</button>
        <img src="" alt="" class="lightbox-image" id="lightbox-image">
    </div>

    <script>
        // Star rating input functionality
        const starRatingInput = document.getElementById('star-rating-input');
        const ratingInput = document.getElementById('rating-input');
        
        if (starRatingInput && ratingInput) {
            const starButtons = starRatingInput.querySelectorAll('.star-rating-btn');
            
            starButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const rating = this.dataset.rating;
                    ratingInput.value = rating;
                    
                    // Update active state
                    starButtons.forEach(b => b.classList.remove('active'));
                    for (let i = 0; i < rating; i++) {
                        starButtons[i].classList.add('active');
                    }
                });
                
                // Hover effect
                btn.addEventListener('mouseenter', function() {
                    const rating = this.dataset.rating;
                    starButtons.forEach((b, index) => {
                        if (index < rating) {
                            b.style.color = '#f59e0b';
                        } else {
                            b.style.color = '';
                        }
                    });
                });
            });
            
            // Reset on mouseleave
            starRatingInput.addEventListener('mouseleave', function() {
                const currentRating = ratingInput.value;
                starButtons.forEach((b, index) => {
                    if (index < currentRating) {
                        b.style.color = '#f59e0b';
                    } else {
                        b.style.color = '';
                    }
                });
            });
        }

        // Reviews Modal functionality
        const reviewsModal = document.getElementById('reviews-modal');
        const reviewsModalClose = document.getElementById('reviews-modal-close');
        const productId = <?= $product_id ?>;
        const shouldAutoPopup = <?= $should_auto_popup ? 'true' : 'false' ?>;

        function openReviewsModal() {
            reviewsModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeReviewsModal() {
            reviewsModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Open modal when rating badge is clicked

        // Close modal via X button
        if (reviewsModalClose) {
            reviewsModalClose.addEventListener('click', closeReviewsModal);
        }

        // Close on click outside content
        if (reviewsModal) {
            reviewsModal.addEventListener('click', function(e) {
                if (e.target === reviewsModal) {
                    closeReviewsModal();
                }
            });
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && reviewsModal.classList.contains('active')) {
                closeReviewsModal();
            }
        });

        // Auto-popup for eligible purchasers
        if (shouldAutoPopup) {
            // Delay slightly to allow page to render
            setTimeout(openReviewsModal, 500);
        }

        // Reviews Slider functionality
        const reviewsSliderTrack = document.getElementById('reviews-slider-track');
        const reviewsPrev = document.getElementById('reviews-prev');
        const reviewsNext = document.getElementById('reviews-next');
        let currentSlide = 0;
        let cardWidth = 380; // Will be measured dynamically
        const gap = 24; // 1.5rem in pixels
        
        function getCardWidth() {
            const firstCard = document.querySelector('.review-slider-card');
            if (firstCard) {
                return firstCard.getBoundingClientRect().width;
            }
            return 380; // Fallback
        }
        
        function getReviewsPerView() {
            if (window.innerWidth > 1024) {
                return 3; // Desktop: 3 cards
            } else if (window.innerWidth > 768) {
                return 2; // Tablet: 2 cards
            } else {
                return 1; // Mobile: 1 card
            }
        }
        
        let reviewsPerView = getReviewsPerView();
        const totalReviews = <?= count($reviews) ?>;
        let maxSlide = Math.max(0, totalReviews - reviewsPerView);

        // Initialize cardWidth measurement
        cardWidth = getCardWidth();

        if (reviewsSliderTrack && reviewsPrev && reviewsNext) {
            function updateSlider() {
                const translateX = -(currentSlide * (cardWidth + gap));
                reviewsSliderTrack.style.transform = `translateX(${translateX}px)`;
                
                reviewsPrev.disabled = currentSlide === 0;
                reviewsNext.disabled = currentSlide >= maxSlide;
            }

            reviewsPrev.addEventListener('click', function() {
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSlider();
                }
            });

            reviewsNext.addEventListener('click', function() {
                if (currentSlide < maxSlide) {
                    currentSlide++;
                    updateSlider();
                }
            });

            // Initialize slider state
            if (totalReviews <= reviewsPerView) {
                reviewsNext.disabled = true;
                // Hide nav buttons if only one review
                reviewsPrev.style.display = 'none';
                reviewsNext.style.display = 'none';
                // Center cards when they fit within view
                reviewsSliderTrack.classList.add('centered');
            } else {
                reviewsSliderTrack.classList.remove('centered');
            }
            updateSlider();

            // Recalculate on resize
            window.addEventListener('resize', function() {
                reviewsPerView = getReviewsPerView();
                cardWidth = getCardWidth(); // Recalculate card width on resize
                maxSlide = Math.max(0, totalReviews - reviewsPerView);
                if (currentSlide > maxSlide) {
                    currentSlide = maxSlide;
                }
                // Show/hide nav buttons based on review count
                if (totalReviews <= reviewsPerView) {
                    reviewsPrev.style.display = 'none';
                    reviewsNext.style.display = 'none';
                    // Center cards when they fit within view
                    reviewsSliderTrack.classList.add('centered');
                } else {
                    reviewsPrev.style.display = 'flex';
                    reviewsNext.style.display = 'flex';
                    // Remove centering when cards overflow
                    reviewsSliderTrack.classList.remove('centered');
                }
                updateSlider();
            });
        }

        // Related Products Slider functionality (mobile only)
        const relatedProductsTrack = document.getElementById('related-products-track');
        const relatedProductsPrev = document.getElementById('related-products-prev');
        const relatedProductsNext = document.getElementById('related-products-next');
        let currentRelatedPage = 0;
        let relatedCardWidth = 0;
        const relatedGap = 16; // 1rem in pixels
        const relatedProductsPerPage = 2; // 2 cards per page on mobile

        function getRelatedCardWidth() {
            const firstCard = relatedProductsTrack.querySelector('.featured-product-card');
            if (firstCard) {
                return firstCard.getBoundingClientRect().width;
            }
            return 0;
        }

        const totalRelatedProducts = <?= count($related_products) ?>;
        let maxRelatedPage = Math.max(0, Math.ceil(totalRelatedProducts / relatedProductsPerPage) - 1);

        if (relatedProductsTrack && relatedProductsPrev && relatedProductsNext) {
            function updateRelatedSlider() {
                const translateX = -(currentRelatedPage * (relatedCardWidth + relatedGap) * relatedProductsPerPage);
                relatedProductsTrack.style.transform = `translateX(${translateX}px)`;
                
                relatedProductsPrev.disabled = currentRelatedPage === 0;
                relatedProductsNext.disabled = currentRelatedPage >= maxRelatedPage;
            }
            
            relatedProductsPrev.addEventListener('click', function() {
                if (currentRelatedPage > 0) {
                    currentRelatedPage--;
                    updateRelatedSlider();
                }
            });
            
            relatedProductsNext.addEventListener('click', function() {
                if (currentRelatedPage < maxRelatedPage) {
                    currentRelatedPage++;
                    updateRelatedSlider();
                }
            });
            
            // Initialize
            relatedCardWidth = getRelatedCardWidth();
            
            // Hide nav buttons if only 1 page
            if (totalRelatedProducts <= relatedProductsPerPage) {
                relatedProductsPrev.style.display = 'none';
                relatedProductsNext.style.display = 'none';
            }
            
            updateRelatedSlider();
            
            // Recalculate on resize
            window.addEventListener('resize', function() {
                relatedCardWidth = getRelatedCardWidth();
                maxRelatedPage = Math.max(0, Math.ceil(totalRelatedProducts / relatedProductsPerPage) - 1);
                if (currentRelatedPage > maxRelatedPage) {
                    currentRelatedPage = maxRelatedPage;
                }
                
                // Show/hide nav buttons based on product count
                if (totalRelatedProducts <= relatedProductsPerPage) {
                    relatedProductsPrev.style.display = 'none';
                    relatedProductsNext.style.display = 'none';
                } else {
                    relatedProductsPrev.style.display = 'flex';
                    relatedProductsNext.style.display = 'flex';
                }
                
                updateRelatedSlider();
            });
        }

        // All Reviews Modal functionality
        const allReviewsBtn = document.getElementById('all-reviews-btn');
        const allReviewsModal = document.getElementById('all-reviews-modal');
        const allReviewsModalClose = document.getElementById('all-reviews-modal-close');

        function openAllReviewsModal() {
            allReviewsModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAllReviewsModal() {
            allReviewsModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (allReviewsBtn) {
            allReviewsBtn.addEventListener('click', openAllReviewsModal);
        }

        if (allReviewsModalClose) {
            allReviewsModalClose.addEventListener('click', closeAllReviewsModal);
        }

        if (allReviewsModal) {
            allReviewsModal.addEventListener('click', function(e) {
                if (e.target === allReviewsModal) {
                    closeAllReviewsModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && allReviewsModal.classList.contains('active')) {
                closeAllReviewsModal();
            }
        });

        // Product Description Accordion functionality
        const accordionHeaders = document.querySelectorAll('.product-desc-accordion-header');
        
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const body = document.getElementById(targetId);
                
                if (body) {
                    body.classList.toggle('active');
                    this.classList.toggle('active');
                }
            });
        });

        // Color to Main Image Swap functionality
        const colorImageLookup = productInfo ? JSON.parse(productInfo.dataset.colorImages || '{}') : {};

        function updateMainImageForColor(selectedColor) {
            if (!selectedColor || Object.keys(colorImageLookup).length === 0) {
                return;
            }
            
            // Case-insensitive lookup
            const colorLower = selectedColor.toLowerCase();
            const imagePath = colorImageLookup[colorLower];
            
            if (imagePath) {
                // Find the ACTIVE gallery slide (the one currently visible)
                const activeSlide = document.querySelector('.gallery-slide.active');
                if (activeSlide) {
                    activeSlide.src = '<?= PUBLIC_URL ?>/uploads/' + imagePath;
                }
            }
        }

        // Attach to color button clicks
        const colorButtons = document.querySelectorAll('.color-swatch');
        colorButtons.forEach(button => {
            button.addEventListener('click', function() {
                const selectedColor = this.dataset.value;
                updateMainImageForColor(selectedColor);
            });
        });

        // Lightbox functionality (images only)
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightbox-image');
        const lightboxClose = document.getElementById('lightbox-close');
        const gallerySlidesForLightbox = document.querySelectorAll('.gallery-slide');
        const descriptionImagesForLightbox = document.querySelectorAll('.description-image');

        // Open lightbox on single click - gallery slides
        gallerySlidesForLightbox.forEach(slide => {
            // Only attach to IMG elements, not VIDEO
            if (slide.tagName !== 'IMG') return;

            slide.addEventListener('click', function(e) {
                const imgSrc = this.src;
                lightboxImage.src = imgSrc;
                lightboxImage.alt = this.alt;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
        });

        // Open lightbox on single click - description images
        descriptionImagesForLightbox.forEach(img => {
            img.addEventListener('click', function(e) {
                const imgSrc = this.src;
                lightboxImage.src = imgSrc;
                lightboxImage.alt = this.alt;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
        });

        // Close lightbox
        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        lightboxClose.addEventListener('click', closeLightbox);

        // Close on click outside image
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                closeLightbox();
            }
        });

        // Size Selector for Leather Shoes
        (function() {
            const productInfo = document.querySelector('.product-info-detail');
            if (!productInfo) return;
            
            const category = productInfo.dataset.category;
            if (category !== 'Leather Shoes') return;
            
            const variantStock = JSON.parse(productInfo.dataset.variantStock || '[]');
            
            // Size conversion table (US as canonical)
            const sizeConversions = {
                'US': {
                    '5': { 'UK': '4', 'EU': '38' },
                    '5.5': { 'UK': '4.5', 'EU': '38.5' },
                    '6': { 'UK': '5', 'EU': '39' },
                    '6.5': { 'UK': '5.5', 'EU': '39.5' },
                    '7': { 'UK': '6', 'EU': '40' },
                    '7.5': { 'UK': '6.5', 'EU': '40.5' },
                    '8': { 'UK': '7', 'EU': '41' },
                    '8.5': { 'UK': '7.5', 'EU': '41.5' },
                    '9': { 'UK': '8', 'EU': '42' },
                    '9.5': { 'UK': '8.5', 'EU': '42.5' },
                    '10': { 'UK': '9', 'EU': '43' },
                    '10.5': { 'UK': '9.5', 'EU': '43.5' },
                    '11': { 'UK': '10', 'EU': '44' },
                    '11.5': { 'UK': '10.5', 'EU': '44.5' },
                    '12': { 'UK': '11', 'EU': '45' },
                    '13': { 'UK': '12', 'EU': '46' },
                    '14': { 'UK': '13', 'EU': '47' },
                    '15': { 'UK': '14', 'EU': '48' },
                    '16': { 'UK': '15', 'EU': '49' }
                },
                'UK': {
                    '4': { 'US': '5', 'EU': '38' },
                    '4.5': { 'US': '5.5', 'EU': '38.5' },
                    '5': { 'US': '6', 'EU': '39' },
                    '5.5': { 'US': '6.5', 'EU': '39.5' },
                    '6': { 'US': '7', 'EU': '40' },
                    '6.5': { 'US': '7.5', 'EU': '40.5' },
                    '7': { 'US': '8', 'EU': '41' },
                    '7.5': { 'US': '8.5', 'EU': '41.5' },
                    '8': { 'US': '9', 'EU': '42' },
                    '8.5': { 'US': '9.5', 'EU': '42.5' },
                    '9': { 'US': '10', 'EU': '43' },
                    '9.5': { 'US': '10.5', 'EU': '43.5' },
                    '10': { 'US': '11', 'EU': '44' },
                    '10.5': { 'US': '11.5', 'EU': '44.5' },
                    '11': { 'US': '12', 'EU': '45' },
                    '12': { 'US': '13', 'EU': '46' },
                    '13': { 'US': '14', 'EU': '47' },
                    '14': { 'US': '15', 'EU': '48' },
                    '15': { 'US': '16', 'EU': '49' }
                },
                'EU': {
                    '38': { 'US': '5', 'UK': '4' },
                    '38.5': { 'US': '5.5', 'UK': '4.5' },
                    '39': { 'US': '6', 'UK': '5' },
                    '39.5': { 'US': '6.5', 'UK': '5.5' },
                    '40': { 'US': '7', 'UK': '6' },
                    '40.5': { 'US': '7.5', 'UK': '6.5' },
                    '41': { 'US': '8', 'UK': '7' },
                    '41.5': { 'US': '8.5', 'UK': '7.5' },
                    '42': { 'US': '9', 'UK': '8' },
                    '42.5': { 'US': '9.5', 'UK': '8.5' },
                    '43': { 'US': '10', 'UK': '9' },
                    '43.5': { 'US': '10.5', 'UK': '9.5' },
                    '44': { 'US': '11', 'UK': '10' },
                    '44.5': { 'US': '11.5', 'UK': '10.5' },
                    '45': { 'US': '12', 'UK': '11' },
                    '46': { 'US': '13', 'UK': '12' },
                    '47': { 'US': '14', 'UK': '13' },
                    '48': { 'US': '15', 'UK': '14' },
                    '49': { 'US': '16', 'UK': '15' }
                }
            };
            
            // DOM elements
            const trigger = document.getElementById('size-selector-trigger');
            const overlay = document.getElementById('size-selector-overlay');
            const modal = document.getElementById('size-selector-modal');
            const closeBtn = document.getElementById('size-selector-close');
            const cancelBtn = document.getElementById('size-selector-cancel');
            const backBtn = document.getElementById('size-back-btn');
            const step1 = document.getElementById('size-step-1');
            const step2 = document.getElementById('size-step-2');
            const step3 = document.getElementById('size-step-3');
            const sizeList = document.getElementById('size-list');
            const sizeListTitle = document.getElementById('size-list-title');
            const selectedSizeDisplay = document.getElementById('selected-size-display');
            const selectedSizeValue = document.getElementById('selected-size-value');
            const hiddenSizeBtn = document.getElementById('hidden-size-btn');
            const customSizeBtn = document.getElementById('custom-size-btn');
            const sizeCustomBtn = document.getElementById('size-custom-btn');
            const shoeMeasurementsBack = document.getElementById('shoe-measurements-back');
            
            let currentFormat = 'US';
            let selectedSize = null;
            
            // Get currently selected color
            function getSelectedColor() {
                const colorBtn = document.querySelector('.color-swatch.active');
                return colorBtn ? colorBtn.dataset.value : null;
            }
            
            // Open modal
            function openSizeSelector() {
                overlay.classList.add('active');
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                showStep1();
            }
            
            // Close modal
            function closeSizeSelector() {
                overlay.classList.remove('active');
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            // Show step 1 (format selection)
            function showStep1() {
                step1.style.display = 'block';
                step2.style.display = 'none';
                if (step3) step3.style.display = 'none';
            }
            
            // Show step 2 (size list)
            function showStep2(format) {
                currentFormat = format;
                step1.style.display = 'none';
                step2.style.display = 'block';
                if (step3) step3.style.display = 'none';
                sizeListTitle.textContent = 'Select Size (' + format + ')';
                renderSizeList(format);
            }
            
            // Show step 3 (custom measurements)
            function showStep3() {
                step1.style.display = 'none';
                step2.style.display = 'none';
                if (step3) step3.style.display = 'block';
            }
            
            // Render size list
            function renderSizeList(format) {
                sizeList.innerHTML = '';
                
                const selectedColor = getSelectedColor();
                
                // Group variants by size and get stock (filtered by color if selected)
                const sizeStockMap = {};
                variantStock.forEach(function(v) {
                    // If color is selected, only count stock for that color
                    if (selectedColor && v.color !== selectedColor) {
                        return;
                    }
                    
                    const usSize = v.size;
                    if (!sizeStockMap[usSize]) {
                        sizeStockMap[usSize] = 0;
                    }
                    sizeStockMap[usSize] += v.stock;
                });
                
                // Get unique US sizes sorted
                const usSizes = Object.keys(sizeStockMap).sort(function(a, b) {
                    return parseFloat(a) - parseFloat(b);
                });
                
                if (usSizes.length === 0) {
                    sizeList.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 1rem;">No sizes available for selected color.</p>';
                    return;
                }
                
                usSizes.forEach(function(usSize) {
                    const stock = sizeStockMap[usSize];
                    let displaySize = usSize;
                    
                    // Convert to selected format
                    if (format !== 'US' && sizeConversions['US'][usSize]) {
                        displaySize = sizeConversions['US'][usSize][format] || usSize;
                    }
                    
                    const option = document.createElement('div');
                    option.className = 'size-option';
                    
                    // Stock status
                    let stockText = '';
                    if (stock === 0) {
                        option.classList.add('sold-out');
                        stockText = 'Sold out';
                    } else if (stock <= 2) {
                        option.classList.add('low-stock');
                        stockText = 'Only ' + stock + ' left';
                    }
                    
                    option.innerHTML = `
                        <span class="size-option-label">${format} ${displaySize}</span>
                        <span class="size-stock-status">${stockText}</span>
                    `;
                    
                    if (stock > 0) {
                        option.addEventListener('click', function() {
                            selectSize(usSize, format + ' ' + displaySize);
                        });
                    }
                    
                    sizeList.appendChild(option);
                });
            }
            
            // Select a size
            function selectSize(usSize, displaySize) {
                selectedSize = usSize;
                selectedSizeDisplay.textContent = displaySize;
                selectedSizeValue.value = usSize;
                const selectedSizeDisplayValue = document.getElementById('selected-size-display-value');
                if (selectedSizeDisplayValue) selectedSizeDisplayValue.value = displaySize;
                trigger.classList.add('has-selection');
                
                // Update hidden size button for Add-to-Cart compatibility
                if (hiddenSizeBtn) {
                    hiddenSizeBtn.dataset.value = usSize;
                    hiddenSizeBtn.classList.add('active');
                }
                
                closeSizeSelector();
            }
            
            // Event listeners
            if (trigger) {
                trigger.addEventListener('click', openSizeSelector);
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeSizeSelector);
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeSizeSelector);
            }
            
            if (backBtn) {
                backBtn.addEventListener('click', showStep1);
            }
            
            if (shoeMeasurementsBack) {
                shoeMeasurementsBack.addEventListener('click', showStep1);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeSizeSelector);
            }
            
            // Format buttons
            document.querySelectorAll('.size-format-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const format = this.dataset.format;
                    showStep2(format);
                });
            });
            
            // Custom Size button (Shoes modal)
            if (sizeCustomBtn) {
                sizeCustomBtn.addEventListener('click', function() {
                    showStep3();
                });
            }
            
            // Validate C input (Step 2)
            const measurementC = document.getElementById('shoe-measurement-c');
            if (measurementC) {
                measurementC.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    const isValid = value && value > 0;
                    // Enable/disable any next button if needed (not used in single-panel structure)
                });
            }
            
            // Validate A and B inputs (Step 3)
            const measurementA = document.getElementById('shoe-measurement-a');
            const measurementB = document.getElementById('shoe-measurement-b');
            const saveShoeMeasurementsBtn = document.getElementById('save-shoe-measurements');
            
            function validateShoeMeasurements() {
                const aValid = measurementA && measurementA.value && parseFloat(measurementA.value) > 0;
                const bValid = measurementB && measurementB.value && parseFloat(measurementB.value) > 0;
                
                if (saveShoeMeasurementsBtn) {
                    saveShoeMeasurementsBtn.disabled = !(aValid && bValid);
                }
            }
            
            if (measurementA) {
                measurementA.addEventListener('input', validateShoeMeasurements);
            }
            if (measurementB) {
                measurementB.addEventListener('input', validateShoeMeasurements);
            }
            if (saveShoeMeasurementsBtn) {
                saveShoeMeasurementsBtn.disabled = true; // Initially disabled
            }
            
            // Save shoe measurements
            if (saveShoeMeasurementsBtn) {
                saveShoeMeasurementsBtn.addEventListener('click', function() {
                    const c = measurementC ? measurementC.value : '';
                    const a = measurementA ? measurementA.value : '';
                    const b = measurementB ? measurementB.value : '';
                    const notes = document.querySelector('.measurement-textarea').value;
                    
                    // Construct size_display
                    let sizeDisplay = `Custom (C:${c} A:${a} B:${b})`;
                    if (notes && notes.trim()) {
                        sizeDisplay += ` — Notes: ${notes.trim()}`;
                    }
                    
                    // Set hidden inputs
                    if (selectedSizeValue) selectedSizeValue.value = 'custom';
                    const selectedSizeDisplayValue = document.getElementById('selected-size-display-value');
                    if (selectedSizeDisplayValue) selectedSizeDisplayValue.value = sizeDisplay;
                    if (selectedSizeDisplay) selectedSizeDisplay.textContent = 'Custom Size';
                    
                    // Update hidden size button for Add-to-Cart compatibility
                    if (hiddenSizeBtn) {
                        hiddenSizeBtn.dataset.value = 'custom';
                        hiddenSizeBtn.classList.add('active');
                    }
                    
                    if (trigger) trigger.classList.add('has-selection');
                    
                    // Close modal
                    closeSizeSelector();
                });
            }
            
            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeSizeSelector();
                }
            });
            
            // Re-render size list when color changes
            document.querySelectorAll('.color-swatch').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    // Reset size selection when color changes
                    if (selectedSizeDisplay) {
                        selectedSizeDisplay.textContent = 'Select a size';
                    }
                    if (selectedSizeValue) {
                        selectedSizeValue.value = '';
                    }
                    if (hiddenSizeBtn) {
                        hiddenSizeBtn.dataset.value = '';
                        hiddenSizeBtn.classList.remove('active');
                    }
                    if (trigger) {
                        trigger.classList.remove('has-selection');
                    }
                    selectedSize = null;
                    
                    // If modal is open and showing step 2, re-render
                    if (modal.classList.contains('active') && step2.style.display !== 'none') {
                        renderSizeList(currentFormat);
                    }
                });
            });
        })();

        // Custom Measurements Modal (Leather Jackets)
        (function() {
            const overlay = document.getElementById('custom-measurements-overlay');
            const modal = document.getElementById('custom-measurements-modal');
            const closeBtn = document.getElementById('custom-measurements-close');
            const backBtn = document.getElementById('custom-measurements-back');
            const saveBtn = document.getElementById('save-custom-measurements');
            const customSizeBtn = document.getElementById('custom-size-btn');
            
            function openCustomMeasurementsModal() {
                if (overlay && modal) {
                    overlay.classList.add('active');
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            function closeCustomMeasurementsModal() {
                if (overlay && modal) {
                    overlay.classList.remove('active');
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
            
            // Custom Size button (Jacket)
            if (customSizeBtn) {
                customSizeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openCustomMeasurementsModal();
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeCustomMeasurementsModal);
            }
            
            if (backBtn) {
                backBtn.addEventListener('click', closeCustomMeasurementsModal);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeCustomMeasurementsModal);
            }
            
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    const measurements = {};
                    document.querySelectorAll('.measurement-input').forEach(function(input) {
                        const field = input.dataset.field;
                        const value = input.value;
                        if (field && value) {
                            measurements[field] = parseFloat(value);
                        }
                    });
                    
                    const notes = document.querySelector('.measurement-textarea').value;
                    if (notes) {
                        measurements.notes = notes;
                    }
                    
                    // Construct size_display from measurements
                    let sizeDisplay = `Custom (Chest:${measurements.chest} Shoulder:${measurements.shoulder} Sleeve:${measurements.sleeve} Biceps:${measurements.biceps} Waist:${measurements.waist} Length:${measurements.length})`;
                    if (measurements.notes && measurements.notes.trim()) {
                        sizeDisplay += ` — Notes: ${measurements.notes.trim()}`;
                    }
                    
                    // Set hidden inputs
                    const selectedSizeValue = document.getElementById('selected-size-value');
                    const selectedSizeDisplayValue = document.getElementById('selected-size-display-value');
                    const hiddenSizeBtn = document.getElementById('hidden-size-btn');
                    const trigger = document.getElementById('size-selector-trigger');
                    const selectedSizeDisplay = document.getElementById('selected-size-display');
                    
                    if (selectedSizeValue) selectedSizeValue.value = 'custom';
                    if (selectedSizeDisplayValue) selectedSizeDisplayValue.value = sizeDisplay;
                    if (selectedSizeDisplay) selectedSizeDisplay.textContent = 'Custom Size';
                    
                    // Update hidden size button for Add-to-Cart compatibility
                    if (hiddenSizeBtn) {
                        hiddenSizeBtn.dataset.value = 'custom';
                        hiddenSizeBtn.classList.add('active');
                    }
                    
                    if (trigger) trigger.classList.add('has-selection');
                    
                    // Close modal
                    closeCustomMeasurementsModal();
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
                    closeCustomMeasurementsModal();
                }
            });
        })();

        // Size Selector for non-Shoes categories (single-step, no format conversion)
        (function() {
            const productInfo = document.querySelector('.product-info-detail');
            if (!productInfo) return;
            
            const category = productInfo.dataset.category;
            if (category === 'Leather Shoes') return;
            
            const variantStock = JSON.parse(productInfo.dataset.variantStock || '[]');
            
            const trigger = document.getElementById('size-selector-trigger');
            const overlay = document.getElementById('size-selector-overlay');
            const modal = document.getElementById('size-selector-modal');
            const closeBtn = document.getElementById('size-selector-close');
            const sizeList = document.getElementById('size-list');
            const sizeListTitle = document.getElementById('size-list-title');
            const selectedSizeDisplay = document.getElementById('selected-size-display');
            const selectedSizeValue = document.getElementById('selected-size-value');
            const hiddenSizeBtn = document.getElementById('hidden-size-btn');
            
            let selectedSize = null;
            
            // Get currently selected color
            function getSelectedColor() {
                const colorBtn = document.querySelector('.color-swatch.active');
                return colorBtn ? colorBtn.dataset.value : null;
            }
            
            // Open modal
            function openSizeSelector() {
                overlay.classList.add('active');
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                renderSizeList();
            }
            
            // Close modal
            function closeSizeSelector() {
                overlay.classList.remove('active');
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            // Render size list (directly from variants, no conversion)
            function renderSizeList() {
                const selectedColor = getSelectedColor();
                sizeList.innerHTML = '';
                
                // Get unique sizes for this category
                const sizes = [...new Set(variantStock.map(v => v.size))];
                
                sizes.forEach(size => {
                    // Aggregate stock across all colors when no color selected
                    let stock = 0;
                    if (selectedColor) {
                        // When color is selected, show stock for that specific color
                        const variant = variantStock.find(v => v.size === size && v.color === selectedColor);
                        stock = variant ? variant.stock : 0;
                    } else {
                        // When no color selected, sum stock across all colors for this size
                        stock = variantStock
                            .filter(v => v.size === size)
                            .reduce((sum, v) => sum + v.stock, 0);
                    }
                    
                    const sizeOption = document.createElement('div');
                    sizeOption.className = 'size-option';
                    sizeOption.dataset.value = size;
                    
                    if (selectedSize === size) {
                        sizeOption.classList.add('active');
                    }
                    
                    // Stock status
                    let stockText = '';
                    if (stock === 0) {
                        sizeOption.classList.add('sold-out');
                        stockText = 'Sold out';
                    } else if (stock <= 2) {
                        sizeOption.classList.add('low-stock');
                        stockText = 'Only ' + stock + ' left';
                    }
                    
                    sizeOption.innerHTML = `
                        <span class="size-option-label">${size}</span>
                        <span class="size-stock-status">${stockText}</span>
                    `;
                    
                    if (stock > 0) {
                        sizeOption.addEventListener('click', function() {
                            selectedSize = this.dataset.value;
                            selectedSizeDisplay.textContent = selectedSize;
                            selectedSizeValue.value = selectedSize;
                            const selectedSizeDisplayValue = document.getElementById('selected-size-display-value');
                            if (selectedSizeDisplayValue) selectedSizeDisplayValue.value = selectedSize;
                            hiddenSizeBtn.dataset.value = selectedSize;
                            hiddenSizeBtn.classList.add('active');
                            
                            // Update active state in list
                            document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('active'));
                            this.classList.add('active');
                            
                            closeSizeSelector();
                        });
                    }
                    
                    sizeList.appendChild(sizeOption);
                });
            }
            
            // Event listeners
            if (trigger) {
                trigger.addEventListener('click', openSizeSelector);
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeSizeSelector);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeSizeSelector);
            }
            
            // Reset size selection when color changes (regardless of modal state)
            document.querySelectorAll('.color-swatch').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    // Reset size selection
                    selectedSize = null;
                    selectedSizeDisplay.textContent = 'Select a size';
                    selectedSizeValue.value = '';
                    hiddenSizeBtn.dataset.value = '';
                    hiddenSizeBtn.classList.remove('active');
                    
                    // Re-render size list if modal is open
                    if (modal.classList.contains('active')) {
                        renderSizeList();
                    }
                });
            });
        })();
    </script>
    </div>
</body>
