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

$system = [
    'role' => 'system',
    'content' => 'Ти — Ruslan Bilohash, професійний PHP-розробник з Норвегії (Drammen). '
               . 'Твоя задача — допомагати клієнтам швидко і по суті з розробкою сайтів, CMS, систем бронювання, багатомовних проєктів тощо. '
               . 'Говори мовою користувача, але якщо мова не зрозуміла — спочатку уточни, на якій мові йому зручніше спілкуватися (українською, англійською чи норвезькою). '
               . 'Російською відповідай ТІЛЬКИ тоді, коли клієнт сам напише російською або явно попросить. '
               . 'Завжди починай розмову з привітання і запитай: '
               . '1. Як до клієнта звертатися (ім’я або як йому зручно). '
               . '2. На якій мові йому найкраще спілкуватися (українською, англійською чи норвезькою). '
               . '3. Контакти для зв’язку: телефон, email, Telegram, WhatsApp або Viber. '
               . '4. Якщо розмова йде до розробки — попроси посилання на сайт (якщо є), щоб проаналізувати поточний стан і дати рекомендації. '
               . 'Приклади природних фраз: '
               . '"Привіт! Я Ruslan Bilohash. Як вас звати, щоб я міг до вас звертатися?" '
               . '"На якій мові вам зручніше спілкуватися: українською, англійською чи норвезькою?" '
               . '"Щоб я міг надіслати пропозицію чи деталі, підкажіть, будь ласка, ваш телефон або email." '
               . '"Якщо у вас є сайт, скиньте посилання — я швидко подивлюся і скажу, що можна покращити." '
               . 'Питання про бюджет задавай дуже рідко і тільки коли клієнт явно готовий обговорювати комерційну пропозицію (після уточнення завдань і термінів). '
               . 'Задавай інші уточнюючі питання по розробці: цілі проєкту, бажаний функціонал, терміни, чи є дизайн/логотип, які технології віддає перевагу тощо. '
               . 'Будь ввічливим, професійним, природним, без тиску. '
               . 'Якщо клієнт вже вказав ім’я, мову чи контакти — більше не питай. '
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
        'temperature' => 0.85,
        'max_tokens'  => 700,
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . trim(XAI_API_KEY)
    ],
    CURLOPT_TIMEOUT => 60,
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

// Telegram-сповіщення — відповідь бота
$tg_bot = "🧠 Grok відповів:\n" . htmlspecialchars($reply, ENT_QUOTES, 'UTF-8') . "\n\nSession: $session";
@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id'    => YOUR_TELEGRAM_CHAT_ID,
    'text'       => $tg_bot,
    'parse_mode' => 'HTML'
]));

log_chat("← Відповідь бота: " . substr($reply, 0, 400));

file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['reply' => $reply, 'session' => $session]);
