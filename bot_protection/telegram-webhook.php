<?php
require_once 'config.php';

// === SECURITY HEADERS ===
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Only accept POST from Telegram
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$update = json_decode(file_get_contents('php://input'), true);

if (!$update || !isset($update['message']['chat']['id'])) {
    http_response_code(400);
    exit;
}

$chat_id = $update['message']['chat']['id'];
$text    = trim($update['message']['text'] ?? '');

if ($chat_id != YOUR_TELEGRAM_CHAT_ID) {
    exit;
}

if (preg_match('/^reply:(s_\d+_[a-f0-9]{6,12})\s+(.+)/is', $text, $m)) {
    $session = $m[1];
    $reply   = trim($m[2]);

    $file = CONVERSATIONS_DIR . '/' . $session . '.json';

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: [];
        $data[] = [
            'role'    => 'assistant',
            'content' => $reply,
            'sender'  => 'you'
        ];
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));

        @file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
            'chat_id' => YOUR_TELEGRAM_CHAT_ID,
            'text'    => "✅ Reply sent to client (session $session)"
        ]));
    }
}