<?php
/**
 * Clicks Leather — Customer Account Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Require login
require_login();

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_naam'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';

// Fetch google_id to determine if user signed in via Google
$stmt = $pdo->prepare("SELECT google_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_google_id = $stmt->fetchColumn();
$is_google_user = !empty($user_google_id);

// ============================================================
// POST HANDLERS (Must be before any HTML output)
// ============================================================

// Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $new_name = sanitize_input($_POST['name'] ?? '');
    // Email is read-only and cannot be changed via this form
    
    $errors = [];
    
    if (empty($new_name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET naam = ? WHERE id = ?");
            $stmt->execute([$new_name, $user_id]);
            
            // Update session
            $_SESSION['user_naam'] = $new_name;
            $user_name = $new_name;
            
            set_flash_message('success', 'Profile updated successfully.');
        } catch (Exception $e) {
            set_flash_message('error', 'Error updating profile. Please try again.');
        }
    } else {
        set_flash_message('error', implode(' ', $errors));
    }
    
    redirect(PUBLIC_URL . '/account.php#profile');
}

// Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    // Block password changes for Google users
    if ($is_google_user) {
        set_flash_message('error', 'Password changes aren\'t available for accounts signed in with Google.');
        redirect(PUBLIC_URL . '/account.php#profile');
    }
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    }
    
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match.';
    }
    
    // Verify current password
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT password_hashed FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password_hashed'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }
    
    if (empty($errors)) {
        try {
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hashed = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $user_id]);
            
            set_flash_message('success', 'Password changed successfully.');
        } catch (Exception $e) {
            set_flash_message('error', 'Error changing password. Please try again.');
        }
    } else {
        set_flash_message('error', implode(' ', $errors));
    }
    
    redirect(PUBLIC_URL . '/account.php#profile');
}

// Add Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_address') {
    $address_data = [
        'full_name' => sanitize_input($_POST['full_name'] ?? ''),
        'address_line1' => sanitize_input($_POST['address_line1'] ?? ''),
        'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
        'city' => sanitize_input($_POST['city'] ?? ''),
        'state' => sanitize_input($_POST['state'] ?? ''),
        'country' => sanitize_input($_POST['country'] ?? ''),
        'postal_code' => sanitize_input($_POST['postal_code'] ?? ''),
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
    ];
    
    $errors = [];
    
    if (empty($address_data['full_name'])) $errors[] = 'Full name is required.';
    if (empty($address_data['address_line1'])) $errors[] = 'Address line 1 is required.';
    if (empty($address_data['city'])) $errors[] = 'City is required.';
    if (empty($address_data['state'])) $errors[] = 'State is required.';
    if (empty($address_data['country'])) $errors[] = 'Country is required.';
    if (empty($address_data['postal_code'])) $errors[] = 'Postal code is required.';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // If setting as default, remove default from other addresses
            if ($address_data['is_default']) {
                $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }
            
            // Insert new address
            $stmt = $pdo->prepare("
                INSERT INTO addresses (user_id, full_name, address_line1, address_line2, city, state, country, postal_code, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $address_data['full_name'],
                $address_data['address_line1'],
                $address_data['address_line2'],
                $address_data['city'],
                $address_data['state'],
                $address_data['country'],
                $address_data['postal_code'],
                $address_data['is_default']
            ]);
            
            $pdo->commit();
            set_flash_message('success', 'Address added successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Error adding address. Please try again.');
        }
    } else {
        set_flash_message('error', implode(' ', $errors));
    }
    
    redirect(PUBLIC_URL . '/account.php#addresses');
}

// Edit Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_address') {
    $address_id = intval($_POST['address_id'] ?? 0);
    $address_data = [
        'full_name' => sanitize_input($_POST['full_name'] ?? ''),
        'address_line1' => sanitize_input($_POST['address_line1'] ?? ''),
        'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
        'city' => sanitize_input($_POST['city'] ?? ''),
        'state' => sanitize_input($_POST['state'] ?? ''),
        'country' => sanitize_input($_POST['country'] ?? ''),
        'postal_code' => sanitize_input($_POST['postal_code'] ?? ''),
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
    ];
    
    $errors = [];
    
    if (empty($address_data['full_name'])) $errors[] = 'Full name is required.';
    if (empty($address_data['address_line1'])) $errors[] = 'Address line 1 is required.';
    if (empty($address_data['city'])) $errors[] = 'City is required.';
    if (empty($address_data['state'])) $errors[] = 'State is required.';
    if (empty($address_data['country'])) $errors[] = 'Country is required.';
    if (empty($address_data['postal_code'])) $errors[] = 'Postal code is required.';
    
    // Verify address belongs to user
    $stmt = $pdo->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);
    if (!$stmt->fetch()) {
        $errors[] = 'Address not found.';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // If setting as default, remove default from other addresses
            if ($address_data['is_default']) {
                $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                $stmt->execute([$user_id, $address_id]);
            }
            
            // Update address
            $stmt = $pdo->prepare("
                UPDATE addresses SET 
                    full_name = ?, address_line1 = ?, address_line2 = ?, 
                    city = ?, state = ?, country = ?, postal_code = ?, is_default = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $address_data['full_name'],
                $address_data['address_line1'],
                $address_data['address_line2'],
                $address_data['city'],
                $address_data['state'],
                $address_data['country'],
                $address_data['postal_code'],
                $address_data['is_default'],
                $address_id,
                $user_id
            ]);
            
            $pdo->commit();
            set_flash_message('success', 'Address updated successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Error updating address. Please try again.');
        }
    } else {
        set_flash_message('error', implode(' ', $errors));
    }
    
    redirect(PUBLIC_URL . '/account.php#addresses');
}

// Delete Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_address') {
    $address_id = intval($_POST['address_id'] ?? 0);
    
    // Verify address belongs to user
    $stmt = $pdo->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);
    
    if ($stmt->fetch()) {
        try {
            $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$address_id, $user_id]);
            set_flash_message('success', 'Address deleted successfully.');
        } catch (Exception $e) {
            set_flash_message('error', 'Error deleting address. Please try again.');
        }
    } else {
        set_flash_message('error', 'Address not found.');
    }
    
    redirect(PUBLIC_URL . '/account.php#addresses');
}

// Set Default Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_default_address') {
    $address_id = intval($_POST['address_id'] ?? 0);
    
    // Verify address belongs to user
    $stmt = $pdo->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);
    
    if ($stmt->fetch()) {
        try {
            $pdo->beginTransaction();
            
            // Remove default from all addresses
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Set default on selected address
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$address_id, $user_id]);
            
            $pdo->commit();
            set_flash_message('success', 'Default address updated.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Error updating default address. Please try again.');
        }
    } else {
        set_flash_message('error', 'Address not found.');
    }
    
    redirect(PUBLIC_URL . '/account.php#addresses');
}

// ============================================================
// FETCH DATA
// ============================================================

// Fetch user's orders
$orders_stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's addresses
$addresses_stmt = $pdo->prepare("
    SELECT * FROM addresses 
    WHERE user_id = ? 
    ORDER BY is_default DESC, created_at DESC
");
$addresses_stmt->execute([$user_id]);
$addresses = $addresses_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clicks Leather — My Account">
    <title>My Account — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css">
    <meta property="og:title" content="My Account — Clicks Leather">
    <meta property="og:description" content="Clicks Leather — My Account">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/account.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .account-page {
            padding: 3rem 1rem;
            min-height: calc(100vh - 200px);
        }

        .account-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .account-header h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .account-header p {
            color: var(--text-secondary);
        }

        .account-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .account-sidebar {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            height: fit-content;
        }

        .account-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .account-nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .account-nav-item:hover {
            background: var(--bg-light);
            color: var(--text-primary);
        }

        .account-nav-item.active {
            background: var(--color-primary);
            color: white;
        }

        .account-sidebar-info {
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .account-sidebar-info-item {
            width: 100%;
            margin-bottom: 1rem;
        }

        .info-label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
        }

        .logout-form {
            margin-top: 1rem;
        }

        .account-main {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 2rem;
        }

        .account-section h2 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state svg {
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            transition: border-color 0.2s ease;
        }

        .order-card:hover {
            border-color: var(--color-primary);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        .order-number {
            font-size: 1.1rem;
        }

        .order-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending_payment { background: #fff3cd; color: #856404; }
        .status-pending { background: #e2e3e5; color: #383d41; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-items-count {
            color: var(--text-secondary);
        }

        .order-total {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .profile-form-container,
        .address-form-container {
            background: var(--bg-light);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-form-container h3,
        .address-form-container h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-family: var(--font-body);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .form-group input.input-readonly {
            background-color: var(--bg-light);
            color: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .info-note {
            background-color: var(--bg-light);
            border-left: 3px solid var(--color-primary);
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .form-group.checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group.checkbox-group label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 0;
        }

        .form-group.checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--color-primary-dark);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-full {
            width: 100%;
        }

        .addresses-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .address-card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            position: relative;
            transition: border-color 0.2s ease;
        }

        .address-card:hover {
            border-color: var(--color-primary);
        }

        .address-default {
            border-color: var(--color-primary);
            background: rgba(193, 149, 108, 0.05);
        }

        .default-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--color-primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .address-name {
            margin-bottom: 0.75rem;
            padding-right: 3rem;
        }

        .address-details {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .address-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .account-layout {
                grid-template-columns: 1fr;
            }

            .account-sidebar {
                margin-bottom: 1rem;
            }

            .account-nav {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }

            .account-nav-item {
                white-space: nowrap;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .addresses-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>

    <?= display_flash_message() ?>

    <script src="<?= PUBLIC_URL ?>/js/flash-message.js"></script>

    <!-- Account Page Section -->
    <section class="account-page">
        <div class="container">
            <div class="account-header">
                <h1>My Account</h1>
                <p>Welcome back, <?= htmlspecialchars($user_name) ?>!</p>
            </div>

            <div class="account-layout">
                <!-- Left Sidebar: Account Navigation -->
                <aside class="account-sidebar">
                    <nav class="account-nav">
                        <a href="#orders" class="account-nav-item active">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <span>My Orders</span>
                        </a>
                        <a href="#profile" class="account-nav-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Profile Settings</span>
                        </a>
                        <a href="#addresses" class="account-nav-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Addresses</span>
                        </a>
                    </nav>

                    <div class="account-sidebar-info">
                        <div class="account-sidebar-info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($user_email) ?></span>
                        </div>
                    </div>

                    <form method="POST" action="<?= PUBLIC_URL ?>/logout.php" class="logout-form">
                        <button type="submit" class="btn btn-outline btn-full">Logout</button>
                    </form>
                </aside>

                <!-- Right Side: Account Content -->
                <main class="account-main">
                    <!-- My Orders Section -->
                    <div class="account-section" id="orders">
                        <h2>My Orders</h2>
                        <?php if (empty($orders)): ?>
                            <div class="empty-state">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <h3>No orders yet</h3>
                                <p>You haven't placed any orders yet. Start shopping to see your order history here.</p>
                                <a href="<?= PUBLIC_URL ?>/index.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php 
                                $sequential_number = 1;
                                foreach ($orders as $order): 
                                ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-number">
                                                <strong>Order <?= $sequential_number ?></strong>
                                            </div>
                                            <div class="order-date">
                                                <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                            </div>
                                            <div class="order-status">
                                                <span class="status-badge status-<?= $order['status'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="order-details">
                                            <div class="order-items-count">
                                                <?= $order['item_count'] ?> item<?= $order['item_count'] !== 1 ? 's' : '' ?>
                                            </div>
                                            <div class="order-total">
                                                <?= format_price($order['total_amount']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    $sequential_number++;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Profile Settings Section -->
                    <div class="account-section" id="profile" style="display: none;">
                        <h2>Profile Settings</h2>
                        
                        <!-- Update Name/Email -->
                        <div class="profile-form-container">
                            <h3>Personal Information</h3>
                            <form method="POST" action="<?= PUBLIC_URL ?>/account.php#profile">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" 
                                           value="<?= htmlspecialchars($user_name) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="input-readonly"
                                           value="<?= htmlspecialchars($user_email) ?>" readonly>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="profile-form-container" style="margin-top: 2rem;">
                            <h3>Change Password</h3>
                            <?php if ($is_google_user): ?>
                                <p class="info-note">Password changes aren't available for accounts signed in with Google.</p>
                            <?php endif; ?>
                            <form method="POST" action="<?= PUBLIC_URL ?>/account.php#profile">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="current_password" name="current_password" required <?= $is_google_user ? 'disabled' : '' ?>>
                                        <button type="button" class="password-toggle-icon" aria-label="Show password" <?= $is_google_user ? 'disabled' : '' ?>>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="new_password" name="new_password" required minlength="6" <?= $is_google_user ? 'disabled' : '' ?>>
                                        <button type="button" class="password-toggle-icon" aria-label="Show password" <?= $is_google_user ? 'disabled' : '' ?>>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" <?= $is_google_user ? 'disabled' : '' ?>>
                                        <button type="button" class="password-toggle-icon" aria-label="Show password" <?= $is_google_user ? 'disabled' : '' ?>>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" <?= $is_google_user ? 'disabled' : '' ?>>Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- Addresses Section -->
                    <div class="account-section" id="addresses" style="display: none;">
                        <h2>Addresses</h2>
                        
                        <!-- Add New Address -->
                        <div class="address-form-container">
                            <h3>Add New Address</h3>
                            <form method="POST" action="<?= PUBLIC_URL ?>/account.php#addresses">
                                <input type="hidden" name="action" value="add_address">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="add_full_name">Full Name</label>
                                        <input type="text" id="add_full_name" name="full_name" required placeholder="John Doe">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="add_address_line1">Address Line 1</label>
                                    <input type="text" id="add_address_line1" name="address_line1" required placeholder="House #, Street name">
                                </div>
                                <div class="form-group">
                                    <label for="add_address_line2">Address Line 2 (Optional)</label>
                                    <input type="text" id="add_address_line2" name="address_line2" placeholder="Apartment, floor, etc.">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="add_city">City</label>
                                        <input type="text" id="add_city" name="city" required placeholder="e.g. Karachi">
                                    </div>
                                    <div class="form-group">
                                        <label for="add_state">State/Province</label>
                                        <input type="text" id="add_state" name="state" required placeholder="e.g. Sindh">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="add_country">Country</label>
                                        <input type="text" id="add_country" name="country" required placeholder="e.g. Pakistan">
                                    </div>
                                    <div class="form-group">
                                        <label for="add_postal_code">Postal Code</label>
                                        <input type="text" id="add_postal_code" name="postal_code" required placeholder="e.g. 75500">
                                    </div>
                                </div>
                                <div class="form-group checkbox-group">
                                    <input type="checkbox" name="is_default" id="is_default">
                                    <label for="is_default">Set as default address</label>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Address</button>
                            </form>
                        </div>

                        <!-- Saved Addresses -->
                        <?php if (empty($addresses)): ?>
                            <div class="empty-state" style="margin-top: 2rem;">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <h3>No saved addresses</h3>
                                <p>Add your first address above for faster checkout.</p>
                            </div>
                        <?php else: ?>
                            <div class="addresses-list" style="margin-top: 2rem;">
                                <h3>Saved Addresses</h3>
                                <?php foreach ($addresses as $address): ?>
                                    <div class="address-card <?= $address['is_default'] ? 'address-default' : '' ?>">
                                        <?php if ($address['is_default']): ?>
                                            <span class="default-badge">Default</span>
                                        <?php endif; ?>
                                        <div class="address-name">
                                            <strong><?= htmlspecialchars($address['full_name']) ?></strong>
                                        </div>
                                        <div class="address-details">
                                            <?= htmlspecialchars($address['address_line1']) ?><br>
                                            <?php if ($address['address_line2']): ?>
                                                <?= htmlspecialchars($address['address_line2']) ?><br>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?><br>
                                            <?= htmlspecialchars($address['country']) ?> <?= htmlspecialchars($address['postal_code']) ?>
                                        </div>
                                        <div class="address-actions">
                                            <?php if (!$address['is_default']): ?>
                                                <form method="POST" action="<?= PUBLIC_URL ?>/account.php#addresses" style="display: inline;">
                                                    <input type="hidden" name="action" value="set_default_address">
                                                    <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm">Set Default</button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="editAddress(<?= $address['id'] ?>, '<?= htmlspecialchars($address['full_name']) ?>', '<?= htmlspecialchars($address['address_line1']) ?>', '<?= htmlspecialchars($address['address_line2']) ?>', '<?= htmlspecialchars($address['city']) ?>', '<?= htmlspecialchars($address['state']) ?>', '<?= htmlspecialchars($address['country']) ?>', '<?= htmlspecialchars($address['postal_code']) ?>', <?= $address['is_default'] ?>)">Edit</button>
                                            <form method="POST" action="<?= PUBLIC_URL ?>/account.php#addresses" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_address">
                                                <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-error); border-color: var(--color-error);" onclick="return confirm('Are you sure you want to delete this address?');">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
    </section>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <script>
        // Account Navigation Tab Switching
        document.querySelectorAll('.account-nav-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all nav items
                document.querySelectorAll('.account-nav-item').forEach(function(nav) {
                    nav.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('.account-section').forEach(function(section) {
                    section.style.display = 'none';
                });
                
                // Show target section
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });

        // Handle hash on page load
        window.addEventListener('load', function() {
            const hash = window.location.hash;
            if (hash) {
                const targetNav = document.querySelector('.account-nav-item[href="' + hash + '"]');
                if (targetNav) {
                    targetNav.click();
                }
            }
        });

        // Edit Address Function
        function editAddress(id, fullName, addressLine1, addressLine2, city, state, country, postalCode, isDefault) {
            // Scroll to add form
            document.getElementById('addresses').scrollIntoView({ behavior: 'smooth' });
            
            // Change form to edit mode
            const form = document.querySelector('#addresses form');
            form.action = '<?= PUBLIC_URL ?>/account.php#addresses';
            form.querySelector('input[name="action"]').value = 'edit_address';
            
            // Add address_id field if it doesn't exist
            let addressIdField = form.querySelector('input[name="address_id"]');
            if (!addressIdField) {
                addressIdField = document.createElement('input');
                addressIdField.type = 'hidden';
                addressIdField.name = 'address_id';
                form.insertBefore(addressIdField, form.firstChild);
            }
            addressIdField.value = id;
            
            // Populate form fields
            document.getElementById('add_full_name').value = fullName;
            document.getElementById('add_address_line1').value = addressLine1;
            document.getElementById('add_address_line2').value = addressLine2;
            document.getElementById('add_city').value = city;
            document.getElementById('add_state').value = state;
            document.getElementById('add_country').value = country;
            document.getElementById('add_postal_code').value = postalCode;
            
            // Set checkbox
            const checkbox = form.querySelector('input[name="is_default"]');
            checkbox.checked = isDefault === 1;
            
            // Change button text
            form.querySelector('button[type="submit"]').textContent = 'Update Address';
            
            // Change heading
            form.querySelector('h3').textContent = 'Edit Address';
        }

        // Reset form when clicking "Add New Address" heading
        document.querySelector('#addresses h3').addEventListener('click', function() {
            if (this.textContent === 'Add New Address') return;
            
            const form = document.querySelector('#addresses form');
            form.reset();
            form.querySelector('input[name="action"]').value = 'add_address';
            form.querySelector('button[type="submit"]').textContent = 'Add Address';
            this.textContent = 'Add New Address';
            
            const addressIdField = form.querySelector('input[name="address_id"]');
            if (addressIdField) {
                addressIdField.remove();
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                // Skip if href is just "#"
                if (href === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>

    <script src="<?= PUBLIC_URL ?>/js/password-toggle.js"></script>
</body>
</html>
