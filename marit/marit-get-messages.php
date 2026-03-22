<?php
require_once 'config.php';

// SECURITY HEADERS
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json; charset=utf-8');

// Дозволяємо з фронтенду (якщо потрібно), але краще обмежити доменом
 header('Access-Control-Allow-Origin: https://bilohash.com');

$session = $_GET['session'] ?? '';

if (!$session || !preg_match('/^s_\d+_[a-z0-9]{8,16}$/i', $session)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ugyldig session format']);
    exit;
}

$file = CONVERSATIONS_DIR . '/' . $session . '.json';

if (!file_exists($file) || !is_readable($file)) {
    echo json_encode([]);
    exit;
}

$data = json_decode(file_get_contents($file), true) ?: [];
$out = [];

foreach ($data as $row) {
    if (!empty($row['content'])) {
        $out[] = [
            'sender'  => $row['sender'] ?? 'bot',
            'content' => $row['content'],
            'time'    => $row['time'] ?? null   // якщо є час — передаємо
        ];
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
