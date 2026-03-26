<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$session = $_GET['session'] ?? '';

if (!$session || !preg_match('/^s_\d+_[a-z0-9]{8,16}$/i', $session)) {
    http_response_code(400);
    echo json_encode(['error' => 'Невірний формат сесії']);
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
    if (!empty($row['content']) && in_array($row['sender'] ?? '', ['client', 'bot'])) {
        $out[] = [
            'sender'  => $row['sender'],
            'content' => $row['content']
        ];
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);