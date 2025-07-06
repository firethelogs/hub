<?php
// debug_bin_lookup.php
// Debug script to test BIN lookup functionality

echo "🔧 BIN Lookup Debug Script\n";
echo "=========================\n\n";

// Test 1: Include the BIN lookup file
echo "1. Testing BIN lookup include...\n";
try {
    require_once 'includes/bin_lookup.php';
    echo "✅ BIN lookup functions loaded successfully\n\n";
} catch (Exception $e) {
    echo "❌ Error loading BIN lookup: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Test the lookupBIN function
echo "2. Testing lookupBIN function...\n";
$testCard = '4532123456789012';
try {
    $result = lookupBIN($testCard);
    echo "✅ BIN lookup function works\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "❌ Error in BIN lookup: " . $e->getMessage() . "\n\n";
}

// Test 3: Test the AJAX endpoint
echo "3. Testing AJAX endpoint...\n";
if (file_exists('ajax/bin_lookup.php')) {
    echo "✅ AJAX endpoint file exists\n";
    
    // Simulate a POST request
    $_POST['card_number'] = $testCard;
    
    ob_start();
    include 'ajax/bin_lookup.php';
    $output = ob_get_clean();
    
    echo "AJAX output: " . $output . "\n\n";
} else {
    echo "❌ AJAX endpoint file missing\n\n";
}

// Test 4: Check admin store page
echo "4. Checking admin store page...\n";
if (file_exists('admin/store.php')) {
    echo "✅ Admin store page exists\n";
    
    // Check if it includes the BIN lookup
    $content = file_get_contents('admin/store.php');
    if (strpos($content, 'bin_lookup.php') !== false) {
        echo "✅ Admin page includes BIN lookup\n";
    } else {
        echo "❌ Admin page missing BIN lookup include\n";
    }
    
    if (strpos($content, 'lookupBIN') !== false) {
        echo "✅ Admin page has lookupBIN function calls\n";
    } else {
        echo "❌ Admin page missing lookupBIN function calls\n";
    }
} else {
    echo "❌ Admin store page missing\n";
}

echo "\n🎯 Debug complete!\n";
?>
