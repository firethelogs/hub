<?php
// includes/credit_cards.php
// Credit card helper functions and logos

function getCreditCardLogo($cardType) {
    $logoPath = '/assets/card-logos/' . strtolower($cardType) . '.svg';
    if (file_exists(__DIR__ . '/..' . $logoPath)) {
        return '<img src="' . $logoPath . '" alt="' . $cardType . '" class="card-logo-svg" />';
    }
    
    // Fallback to emojis
    $logos = [
        'visa' => '💳',
        'mastercard' => '🔴',
        'amex' => '🟦',
        'discover' => '🟠',
        'jcb' => '🟢',
        'diners' => '⚪'
    ];
    
    return $logos[$cardType] ?? '💳';
}

function getCreditCardName($cardType) {
    $names = [
        'visa' => 'Visa',
        'mastercard' => 'Mastercard',
        'amex' => 'American Express',
        'discover' => 'Discover',
        'jcb' => 'JCB',
        'diners' => 'Diners Club'
    ];
    
    return $names[$cardType] ?? 'Credit Card';
}

function getCreditCardColor($cardType) {
    $colors = [
        'visa' => '#1A1F71',
        'mastercard' => '#EB001B',
        'amex' => '#006FCF',
        'discover' => '#FF6000',
        'jcb' => '#0066CC',
        'diners' => '#0079BE'
    ];
    
    return $colors[$cardType] ?? '#6366f1';
}

function maskCardNumber($cardNumber) {
    if (empty($cardNumber)) return '';
    return '**** **** **** ' . substr($cardNumber, -4);
}

function formatCardExpiry($expiry) {
    if (empty($expiry)) return '';
    return '**/**';
}

function maskCvv($cvv) {
    if (empty($cvv)) return '';
    return '***';
}

function getCardBrandFromNumber($cardNumber) {
    $cardNumber = preg_replace('/[^0-9]/', '', $cardNumber);
    
    if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $cardNumber)) {
        return 'visa';
    } elseif (preg_match('/^5[1-5][0-9]{14}$/', $cardNumber)) {
        return 'mastercard';
    } elseif (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
        return 'amex';
    } elseif (preg_match('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $cardNumber)) {
        return 'discover';
    } elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $cardNumber)) {
        return 'jcb';
    } elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $cardNumber)) {
        return 'diners';
    }
    
    return 'unknown';
}

function getCardDetails($item) {
    $details = [];
    
    if (!empty($item['card_bank'])) {
        $details[] = $item['card_bank'];
    }
    
    if (!empty($item['card_country'])) {
        $details[] = $item['card_country'];
    }
    
    if (!empty($item['card_level'])) {
        $details[] = strtoupper($item['card_level']);
    }
    
    return implode(' • ', $details);
}
