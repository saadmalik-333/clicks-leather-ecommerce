<?php
/**
 * Clicks Leather — Admin Messages Page
 */
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Verify admin access BEFORE POST handling
require_admin();

$page_title = 'Messages';

// Handle resolve action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve' && isset($_POST['message_id'])) {
    $message_id = (int)$_POST['message_id'];
    $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'Resolved' WHERE id = ?");
    $stmt->execute([$message_id]);
    header("Location: messages.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Get view parameter (detail view)
$view_message_id = isset($_GET['view']) ? intval($_GET['view']) : null;

// Fetch all messages, newest first
$result = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $result->fetchAll();

// If viewing specific message details
$message_detail = null;
if ($view_message_id) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$view_message_id]);
    $message_detail = $stmt->fetch();
}
?>

<?php if ($view_message_id && $message_detail): ?>
    <!-- Message Detail View -->
    <div class="dashboard-section">
        <div class="section-header">
            <a href="<?= ADMIN_URL ?>/messages.php" class="btn btn-outline btn-sm">← Back to Messages</a>
            <h3 class="section-title">Message #<?= $message_detail['id'] ?></h3>
        </div>

        <div class="order-detail-grid">
            <!-- Message Info -->
            <div class="order-detail-card">
                <h4 class="card-title">Message Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Message ID:</span>
                    <span class="detail-value">#<?= $message_detail['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?= strtolower($message_detail['status']) ?>">
                            <?= htmlspecialchars($message_detail['status']) ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?= date('M d, Y g:i A', strtotime($message_detail['created_at'])) ?></span>
                </div>
            </div>

            <!-- Sender Info -->
            <div class="order-detail-card">
                <h4 class="card-title">Sender Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($message_detail['name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($message_detail['email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($message_detail['phone'] ?? 'Not provided') ?></span>
                </div>
            </div>

            <!-- Message Details -->
            <div class="order-detail-card">
                <h4 class="card-title">Message Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Type of Question:</span>
                    <span class="detail-value"><?= htmlspecialchars($message_detail['question_type']) ?></span>
                </div>
                <div class="detail-row" style="flex-direction: column; align-items: flex-start;">
                    <span class="detail-label">Message:</span>
                    <span class="detail-value" style="margin-top: 0.5rem; white-space: pre-wrap; line-height: 1.6;"><?= htmlspecialchars($message_detail['message']) ?></span>
                </div>
            </div>
        </div>

        <!-- Mark Resolved -->
        <?php if ($message_detail['status'] === 'Pending'): ?>
        <div class="order-detail-card" style="margin-top: 1.5rem;">
            <h4 class="card-title">Mark as Resolved</h4>
            <form method="POST">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="message_id" value="<?= $message_detail['id'] ?>">
                <button type="submit" class="btn btn-primary">Mark as Resolved</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Messages List View -->
    <div class="dashboard-section">
        <h3 class="section-title">Messages</h3>
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <p>No messages found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="messages-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="80">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= htmlspecialchars($message['name']) ?></td>
                                <td><?= htmlspecialchars($message['email']) ?></td>
                                <td><?= htmlspecialchars($message['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($message['question_type']) ?></td>
                                <td>
                                    <?php if ($message['status'] === 'Pending'): ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="status-badge status-resolved">Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($message['created_at'])) ?></td>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/messages.php?view=<?= $message['id'] ?>" class="btn btn-outline btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-resolved {
        background: #d4edda;
        color: #155724;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: background var(--transition-fast);
    }

    .btn-primary:hover {
        background: #6b4423;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
