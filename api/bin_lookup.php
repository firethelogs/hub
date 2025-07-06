<?php
// api/bin_lookup.php
// AJAX endpoint for BIN lookup

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/bin_lookup.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['card_number'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Card number is required']);
    exit;
}

$cardNumber = $input['card_number'];

// Validate card number format
if (!validateCardNumber($cardNumber)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid card number format']);
    exit;
}

// Perform BIN lookup
$binData = lookupBIN($cardNumber);

if (!$binData) {
    http_response_code(404);
    echo json_encode(['error' => 'BIN data not found']);
    exit;
}

// Generate card details string
$binData['card_details'] = generateCardDetails($binData);

echo json_encode($binData);
