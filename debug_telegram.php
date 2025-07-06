<?php
// debug_telegram.php - Debug Telegram getUserInfo method
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/telegram.php';

$telegram = getTelegramBot();

// Test with a sample Telegram ID
$testTelegramId = '1066887572'; // Example ID

echo "<h1>Debug Telegram getUserInfo</h1>";
echo "<p>Testing with Telegram ID: {$testTelegramId}</p>";

// Test getUserInfo
$userInfo = $telegram->getUserInfo($testTelegramId);

echo "<h2>getUserInfo Result:</h2>";
echo "<pre>";
var_dump($userInfo);
echo "</pre>";

// Test direct API call
echo "<h2>Direct API Test:</h2>";
$token = '8158833495:AAHzeYw3BEHXhZLDmYLrGYbh51s-LAoF7QA';
$url = "https://api.telegram.org/bot{$token}/getChat?chat_id={$testTelegramId}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: {$httpCode}</p>";
echo "<p>Response: {$response}</p>";

if ($response) {
    $data = json_decode($response, true);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}
?>
