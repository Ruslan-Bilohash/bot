<?php
header('Content-Type: text/plain; charset=utf-8');

$api_key = 'xai-ваш ключ';

$ch = curl_init('https://api.x.ai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'grok-beta',
        'messages' => [
            ['role' => 'user', 'content' => 'Привет, скажи 12345']
        ],
        'temperature' => 0.5,
        'max_tokens' => 10
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP код: $http_code\n";
echo "cURL ошибка: " . ($error ?: 'нет') . "\n\n";
echo "Ответ сервера:\n" . substr($response, 0, 500) . "\n";