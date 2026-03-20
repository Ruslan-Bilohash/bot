<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// ЛОГИРОВАНИЕ
$log_file = LOG_DIR . '/chat-' . date('Y-m-d') . '.log';

function log_chat($text) {
    global $log_file, $session;
    $time = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';
    file_put_contents($log_file, "[$time] [$ip] [sess:$session] $text\n", FILE_APPEND | LOCK_EX);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$session = $input['session'] ?? 's_' . time() . '_' . substr(md5(uniqid()), 0, 10);
$message = trim($input['message'] ?? '');

log_chat("→ Запит: " . substr($message, 0, 400));

if (strlen($message) < 1) {
    log_chat("Помилка: пусте повідомлення");
    echo json_encode(['error' => 'empty message']);
    exit;
}

$file = CONVERSATIONS_DIR . '/' . $session . '.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Країна та мова — тільки перший запит
if (empty($data)) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $geo_json = @file_get_contents("https://ipapi.co/{$ip}/json/");
    $geo = $geo_json ? json_decode($geo_json, true) : [];

    $country = $geo['country_name'] ?? 'Неизвестно';
    $city    = $geo['city'] ?? '';
    $lang    = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'uk', 0, 2);

    $info = "Клиент: $country ($city), мова браузера: $lang";
    $data[] = ['role' => 'system', 'content' => $info];
    log_chat("Перший запит → $info");
}

$data[] = ['role' => 'user', 'content' => $message, 'sender' => 'client'];

// Telegram-сповіщення
$is_first = count($data) <= 3;
$country_str = isset($country) ? " ($country $city)" : '';
$tg_text = $is_first
    ? "🆕 Новий клієнт bilohash.com$country_str\nSession: $session\nПовідомлення: $message"
    : "bilohash.com | $session\n$message";

@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id'    => YOUR_TELEGRAM_CHAT_ID,
    'text'       => $tg_text,
    'parse_mode' => 'HTML'
]));

log_chat("→ Надіслано в Telegram");

// Запит до Grok — оновлений промпт з обов’язковим питанням імені та контактів
$system = [
    'role' => 'system',
    'content' => 'Ти — Ruslan Bilohash, професійний PHP-розробник з Норвегії (Drammen). '
               . 'Твоя задача — допомагати клієнтам швидко і по суті з розробкою сайтів, CMS, систем бронювання, багатомовних проєктів тощо. '
               . 'Говори мовою користувача (українською, російською, англійською, норвезькою, литовською, шведською, польською, німецькою ). '
               . 'Завжди починай розмову з привітання і запитай, як до клієнта звертатися (ім’я або як йому зручно). '
               . 'У першому або другому повідомленні обов’язково ввічливо попроси контакти для зв’язку: телефон, email, Telegram, WhatsApp або Viber. '
               . 'Приклади запитань: '
               . '"Щоб я міг звертатися до вас по імені, підкажіть, будь ласка, як вас звати?" '
               . '"Щоб надіслати точну пропозицію чи обговорити деталі, залиште, будь ласка, ваш телефон або email." '
               . '"Як вам зручніше спілкуватися далі: Telegram, WhatsApp, Viber чи дзвінок?" '
               . 'Роби це природно, ввічливо, без тиску — тільки коли є сенс (наприклад, при обговоренні замовлення, ціни, термінів). '
               . 'Якщо клієнт вже вказав ім’я чи контакти — більше не питай. '
               . 'Будь впевненим, професійним, задавай уточнюючі питання. '
               . 'Ніколи не говори, що ти штучний інтелект чи бот.'
];

$messages = [$system];
foreach ($data as $m) {
    if ($m['sender'] !== 'you') {
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
        'temperature' => 0.75,
        'max_tokens'  => 2048,
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
    $reply = $json['choices'][0]['message']['content'] ?? 'Вибачте, зараз не можу відповісти.';
} else {
    $reply = 'Вибачте, сталася помилка. Спробуйте ще раз або напишіть мені в Telegram.';
}

$data[] = ['role' => 'assistant', 'content' => $reply, 'sender' => 'bot'];

file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));

echo json_encode(['reply' => $reply, 'session' => $session]);