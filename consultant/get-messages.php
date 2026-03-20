<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$session = $_GET['session'] ?? '';

if (!$session || !preg_match('/^s_\d+_[a-f0-9]{6,12}$/', $session)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad session format']);
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
    if (isset($row['sender']) && isset($row['content'])) {
        $out[] = [
            'sender'  => $row['sender'],
            'content' => $row['content']
        ];
    }
}

echo json_encode($out);