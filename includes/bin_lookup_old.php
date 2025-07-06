<?php
// includes/bin_lookup.php
// BIN lookup functionality using binlist.io API

function lookupBIN($cardNumber) {
    // Clean card number - remove spaces and non-digits
    $cardNumber = preg_replace('/[^0-9]/', '', $cardNumber);
    
    if (strlen($cardNumber) < 6) {
        return ['error' => 'Card number must be at least 6 digits'];
    }
    
    $bin = substr($cardNumber, 0, 6);
    
    // Use binlist.io API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://binlist.io/lookup/'.$bin.'/');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JaxxyCC Store/1.0');
    
    $bindata = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$bindata) {
        return detectCardTypeFromNumber($cardNumber);
    }
    
    $binna = json_decode($bindata, true);
    if (!$binna) {
        return detectCardTypeFromNumber($cardNumber);
    }
    
    $brand = isset($binna['scheme']) ? strtolower($binna['scheme']) : 'unknown';
    $country = isset($binna['country']['name']) ? $binna['country']['name'] : 'Unknown';
    $type = isset($binna['type']) ? ucfirst($binna['type']) : 'Unknown';
    $bank = isset($binna['bank']['name']) ? $binna['bank']['name'] : 'Unknown Bank';
    
    // Map scheme names to our card types
    $brandMap = [
        'visa' => 'visa',
        'mastercard' => 'mastercard',
        'american express' => 'amex',
        'amex' => 'amex',
        'discover' => 'discover',
        'jcb' => 'jcb',
        'diners club' => 'diners',
        'diners' => 'diners'
    ];
    
    $mappedBrand = $brandMap[$brand] ?? $brand;
    
    return [
        'error' => false,
        'brand' => $mappedBrand,
        'type' => $type,
        'bank' => $bank,
        'country' => $country,
        'country_code' => isset($binna['country']['alpha2']) ? $binna['country']['alpha2'] : ''
    ];
}

function fetchBINData($url, $apiName = 'unknown') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JaxxyCC Store BIN Lookup');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && is_array($data)) {
            return formatBINData($data, $apiName);
        }
    }
    
    return false;
}

function formatBINData($data, $apiName = 'unknown') {
    $formatted = [
        'success' => true,
        'card_type' => 'unknown',
        'card_brand' => 'Unknown',
        'bank_name' => 'Unknown Bank',
        'country' => 'Unknown',
        'country_code' => 'XX',
        'card_level' => 'standard',
        'card_category' => 'unknown'
    ];
    
    // Map different API response formats
    if (isset($data['scheme'])) {
        // binlist.net format
        $formatted['card_type'] = strtolower($data['scheme']);
        $formatted['card_brand'] = ucfirst($data['scheme']);
        
        if (isset($data['bank']['name'])) {
            $formatted['bank_name'] = $data['bank']['name'];
        }
        
        if (isset($data['country']['name'])) {
            $formatted['country'] = $data['country']['name'];
        }
        
        if (isset($data['country']['alpha2'])) {
            $formatted['country_code'] = $data['country']['alpha2'];
        }
        
        if (isset($data['type'])) {
            $formatted['card_level'] = strtolower($data['type']);
        }
        
        if (isset($data['brand'])) {
            $formatted['card_category'] = strtolower($data['brand']);
        }
    } elseif (isset($data['brand'])) {
        // Alternative API format
        $formatted['card_type'] = strtolower($data['brand']);
        $formatted['card_brand'] = ucfirst($data['brand']);
        
        if (isset($data['bank'])) {
            $formatted['bank_name'] = $data['bank'];
        }
        
        if (isset($data['country_name'])) {
            $formatted['country'] = $data['country_name'];
        }
        
        if (isset($data['type'])) {
            $formatted['card_level'] = strtolower($data['type']);
        }
    }
    
    return $formatted;
}

function detectCardTypeFromNumber($cardNumber) {
    // Fallback card type detection based on card number patterns
    $cardNumber = preg_replace('/[^0-9]/', '', $cardNumber);
    
    if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $cardNumber)) {
        return [
            'error' => false,
            'brand' => 'visa',
            'type' => 'Standard',
            'bank' => 'Unknown Bank',
            'country' => 'Unknown',
            'country_code' => ''
        ];
    } elseif (preg_match('/^5[1-5][0-9]{14}$/', $cardNumber)) {
        return [
            'error' => false,
            'brand' => 'mastercard',
            'type' => 'Standard',
            'bank' => 'Unknown Bank',
            'country' => 'Unknown',
            'country_code' => ''
        ];
    } elseif (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
        return [
            'error' => false,
            'brand' => 'amex',
            'type' => 'Standard',
            'bank' => 'Unknown Bank',
            'country' => 'Unknown',
            'country_code' => ''
        ];
    } elseif (preg_match('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $cardNumber)) {
        return [
            'error' => false,
            'brand' => 'discover',
            'type' => 'Standard',
            'bank' => 'Unknown Bank',
            'country' => 'Unknown',
            'country_code' => ''
        ];
    } elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $cardNumber)) {
        return [
            'error' => false,
            'brand' => 'jcb',
            'type' => 'Standard',
            'bank' => 'Unknown Bank',
            'country' => 'Unknown',
            'country_code' => ''
        ];
    } elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $cardNumber)) {
        return [
            'error' => false,
            'brand' => 'diners',
            'type' => 'Standard',
            'bank' => 'Unknown Bank',
            'country' => 'Unknown',
            'country_code' => ''
        ];
    }
    
    return [
        'error' => 'Unable to detect card type',
        'brand' => 'unknown',
        'type' => 'Unknown',
        'bank' => 'Unknown Bank',
        'country' => 'Unknown',
        'country_code' => ''
    ];
}
}

function validateCardNumber($cardNumber) {
    // Remove spaces and non-digits
    $number = preg_replace('/[^0-9]/', '', $cardNumber);
    
    // Check if it's a valid length (13-19 digits)
    if (strlen($number) < 13 || strlen($number) > 19) {
        return false;
    }
    
    // Luhn algorithm check
    $sum = 0;
    $alternate = false;
    
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $digit = intval($number[$i]);
        
        if ($alternate) {
            $digit *= 2;
            if ($digit > 9) {
                $digit = ($digit % 10) + 1;
            }
        }
        
        $sum += $digit;
        $alternate = !$alternate;
    }
    
    return ($sum % 10 === 0);
}
