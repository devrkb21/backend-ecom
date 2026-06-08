<?php
$baseUrl = 'https://portal.packzy.com/api/v1';
$apiKey = '5rrpjxh6grprira35adafoj5hof2ofhl';
$secretKey = 'iu3izagtpchhidsz4uytt1b5';

$data = [
    'invoice' => 'TEST-123',
    'recipient_name' => 'John Doe',
    'recipient_phone' => '01711111111',
    'recipient_address' => 'Dhaka',
    'cod_amount' => 100,
    'note' => 'Test'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/create_order');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Api-Key: ' . $apiKey,
    'Secret-Key: ' . $secretKey,
    'Content-Type: application/json'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Response:\n";
var_dump($response);
