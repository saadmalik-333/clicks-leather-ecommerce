<?php
/**
 * Clicks Leather — Shipping Cost API Endpoint
 * Returns current shipping settings as JSON for the chatbot
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

// Get shipping settings
$shipping_is_free = get_setting($pdo, 'shipping_is_free', 'yes');
$shipping_flat_cost = floatval(get_setting($pdo, 'shipping_flat_cost', '15.00'));

echo json_encode([
    'is_free' => $shipping_is_free === 'yes',
    'cost' => $shipping_flat_cost
]);
