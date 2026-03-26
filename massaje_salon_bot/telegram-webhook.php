<?php
require_once 'config.php';
require_once 'settings.php';

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$update = json_decode(file_get_contents('php://input'), true);

if (!$update || !isset($update['message']['chat']['id'])) {
    exit;
}

$chat_id = $update['message']['chat']['id'];
$text    = trim($update['message']['text'] ?? '');

if ($chat_id != YOUR_TELEGRAM_CHAT_ID) exit;

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
            'text'    => "✅ Відповідь відправлена клієнту (session $session)"
        ]));
    }
}