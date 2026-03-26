<?php
require_once 'config.php';
require_once 'settings.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = LOG_DIR . '/chat-' . date('Y-m-d') . '.log';

function log_chat($text) {
    global $log_file, $session;
    $time = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    file_put_contents($log_file, "[$time] [$ip] [sess:$session] $text\n", FILE_APPEND | LOCK_EX);
}

function rate_limit_check($session) {
    $rate_file = CONVERSATIONS_DIR . '/' . $session . '.rate';
    $now = time();
    $requests = file_exists($rate_file) ? json_decode(file_get_contents($rate_file), true) : [];
    $requests = array_filter($requests, fn($t) => $t > $now - 300);
    $requests[] = $now;
    file_put_contents($rate_file, json_encode($requests), LOCK_EX);
    return count($requests) <= 15;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$session = $input['session'] ?? 's_' . time() . '_' . substr(md5(uniqid()), 0, 10);
$message = trim($input['message'] ?? '');

log_chat("→ Клієнт: " . substr($message, 0, 400));

if (strlen($message) < 1) {
    echo json_encode(['error' => 'Порожнє повідомлення']);
    exit;
}

if (!rate_limit_check($session)) {
    echo json_encode(['error' => 'Занадто багато повідомлень. Зачекайте 5 хвилин.']);
    exit;
}

$file = CONVERSATIONS_DIR . '/' . $session . '.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Додаємо системний промпт тільки при першому повідомленні
if (empty($data)) {
    global $SYSTEM_PROMPT;
    $data[] = ['role' => 'system', 'content' => $SYSTEM_PROMPT];
}

$data[] = ['role' => 'user', 'content' => $message, 'sender' => 'client'];

// Відправка в Telegram
$is_first = count($data) <= 3;
$tg_text = $is_first 
    ? "🆕 НОВИЙ КЛІЄНТ!\nSession: $session\nПовідомлення: $message" 
    : "Session: $session\nПовідомлення: $message";

@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id' => YOUR_TELEGRAM_CHAT_ID,
    'text'    => $tg_text
]));

// Підготовка повідомлень для Grok
$messages = [];
foreach ($data as $m) {
    if (!empty($m['content'])) {
        $messages[] = ['role' => $m['role'], 'content' => $m['content']];
    }
}

$ch = curl_init('https://api.x.ai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model'       => GROK_MODEL,
        'messages'    => $messages,
        'temperature' => 0.85,
        'max_tokens'  => 2200,
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . XAI_API_KEY
    ],
    CURLOPT_TIMEOUT => 45,
]);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http === 200) {
    $json = json_decode($resp, true);
    $reply = $json['choices'][0]['message']['content'] ?? 'Вибачте, щось пішло не так... Спробуйте ще раз ❤️';
} else {
    $reply = 'На жаль, зараз виникла технічна проблема. Зателефонуйте мені, будь ласка: +370 641 09990 💆‍♀️';
}

$data[] = ['role' => 'assistant', 'content' => $reply, 'sender' => 'bot'];

// Відправка відповіді в Telegram
@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id' => YOUR_TELEGRAM_CHAT_ID,
    'text'    => "🤖 Тетяна: " . substr($reply, 0, 300) . "\n\n(Session: $session)"
]));

log_chat("→ Тетяна: " . substr($reply, 0, 300));

file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['reply' => $reply, 'session' => $session]);