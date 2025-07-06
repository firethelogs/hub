<?php
// ajax/bin_lookup.php
// AJAX endpoint for BIN lookup

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bin_lookup.php';

if (!isset($_POST['card_number'])) {
    echo json_encode(['error' => 'Card number is required']);
    exit;
}

$cardNumber = $_POST['card_number'];
$binData = lookupBIN($cardNumber);

if ($binData['error']) {
    echo json_encode(['error' => $binData['error']]);
} else {
    // Format the response for the frontend
    $response = [
        'success' => true,
        'card_type' => $binData['brand'],
        'bank' => $binData['bank'],
        'country' => $binData['country'],
        'type' => $binData['type'],
        'details' => generateCardDetails($binData)
    ];
    
    echo json_encode($response);
}

function generateCardDetails($binData) {
    $details = [];
    
    if ($binData['bank'] !== 'Unknown Bank') {
        $details[] = $binData['bank'];
    }
    
    if ($binData['country'] !== 'Unknown') {
        $details[] = $binData['country'];
    }
    
    if ($binData['type'] !== 'Unknown' && $binData['type'] !== 'Standard') {
        $details[] = $binData['type'] . ' Level';
    }
    
    return implode(' • ', $details);
}
