<?php
/**
 * Clicks Leather — Contact Page
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session expiry BEFORE any HTML output (only if user was logged in)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    require_active_session();
}

// Form handling
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $question_type = trim($_POST['question_type'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($name)) {
        $error = 'Name is required.';
    } elseif (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($question_type)) {
        $error = 'Please select a question type.';
    } elseif (empty($message)) {
        $error = 'Message is required.';
    } else {
        // Insert into database
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, question_type, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $question_type, $message]);
            $success = true;
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Clicks Leather — Send us a message and we'll get back to you as soon as possible.">
    <title>Contact Us — Clicks Leather</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="<?= PUBLIC_URL ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= PUBLIC_URL ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= PUBLIC_URL ?>/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= PUBLIC_URL ?>/apple-touch-icon.png">
    <meta property="og:title" content="Contact Us — Clicks Leather">
    <meta property="og:description" content="Contact Clicks Leather — Send us a message and we'll get back to you as soon as possible.">
    <meta property="og:image" content="<?= PUBLIC_URL ?>/img/logo/clicks_leather_logo_dark_transparent.png">
    <meta property="og:url" content="<?= PUBLIC_URL ?>/contact.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        .contact-page {
            padding: 0;
            min-height: calc(100vh - 200px);
            max-width: 100%;
            overflow-x: hidden;
        }

        .contact-hero {
            position: relative;
            width: 100%;
            height: 455px;
            background: url('<?= PUBLIC_URL ?>/img/contact/contact.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 3rem;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .contact-hero h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: 0.02em;
            position: relative;
            z-index: 2;
        }

        .contact-hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .contact-container {
            max-width: 700px;
            margin: 0 auto 4rem;
            padding: 0 0rem;
        }

        .contact-intro {
            text-align: center;
            margin-bottom: 3rem;
        }

        .contact-intro p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .contact-form {
            background: var(--bg-card);
            padding: 3rem;
            border-radius: var(--radius-sm);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #E6DFD5;
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 1rem;
            color: var(--text-primary);
            background: white;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(140, 92, 56, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-group .required {
            color: var(--color-primary);
        }

        .submit-btn {
            background: var(--color-primary);
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background var(--transition-fast);
        }

        .submit-btn:hover {
            background: #6b4423;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(140, 92, 56, 0.3);
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .contact-hero {
                height: 300px;
            }

            .contact-form {
                padding: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include PUBLIC_PATH . '/includes/header.php'; ?>
    <div class="page-wrapper">

    <main class="contact-page">
        <div class="contact-hero">
            <h1>Contact Us</h1>
            <p>Have a question? Send us a message and we'll get back to you as soon as possible.</p>
        </div>

        <div class="contact-container">
            <div class="contact-intro">
                <p>We'd love to hear from you. Fill out the form below and our team will get back to you within 24-48 hours.</p>
            </div>

            <?php if ($success): ?>
                <div class="success-message">
                    Thank you for your message! We'll get back to you soon.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="contact-form" method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number (optional)</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="question_type">Type of Question <span class="required">*</span></label>
                        <select id="question_type" name="question_type" required>
                            <option value="">Select a question type</option>
                            <option value="Order Status" <?= (($_POST['question_type'] ?? '') === 'Order Status' ? 'selected' : '') ?>>Order Status</option>
                            <option value="Product Inquiry" <?= (($_POST['question_type'] ?? '') === 'Product Inquiry' ? 'selected' : '') ?>>Product Inquiry</option>
                            <option value="Customization/Engraving" <?= (($_POST['question_type'] ?? '') === 'Customization/Engraving' ? 'selected' : '') ?>>Customization/Engraving</option>
                            <option value="Return/Refund" <?= (($_POST['question_type'] ?? '') === 'Return/Refund' ? 'selected' : '') ?>>Return/Refund</option>
                            <option value="Other" <?= (($_POST['question_type'] ?? '') === 'Other' ? 'selected' : '') ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message">Message <span class="required">*</span></label>
                    <textarea id="message" name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
    </main>

    <?php include PUBLIC_PATH . '/includes/footer.php'; ?>

    <!-- Cart Drawer -->
    <?php include PUBLIC_PATH . '/includes/cart-drawer.php'; ?>

    <!-- Cart JavaScript -->
    <script src="<?= PUBLIC_URL ?>/js/cart.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/header-scroll.js"></script>
    </div>
</body>
</html>
