<?php
require_once 'config.php';

// SECURITY HEADERS
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // якщо потрібно з фронту, але краще обмежити

$session = $_GET['session'] ?? '';

if (!$session || !preg_match('/^s_\d+_[a-f0-9]{6,12}$/', $session)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ugyldig session']);
    exit;
}

$file = CONVERSATIONS_DIR . '/' . $session . '.json';

if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

$data = json_decode(file_get_contents($file), true) ?: [];
$out = [];

foreach ($data as $row) {
    if (isset($row['content'])) {
        $out[] = [
            'sender'  => $row['sender'] ?? 'bot',
            'content' => $row['content']
        ];
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);