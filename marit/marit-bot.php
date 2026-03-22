<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$log_file = LOG_DIR . '/chat-' . date('Y-m-d') . '.log';

function log_chat($text) {
    global $log_file, $session;
    $time = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'ukjent';
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

log_chat("→ Elev melding: " . substr($message, 0, 400));

if (strlen($message) < 1) {
    echo json_encode(['error' => 'tom melding']);
    exit;
}

if (!rate_limit_check($session)) {
    echo json_encode(['error' => 'For mange meldinger. Vent 5 minutter.']);
    exit;
}

$file = CONVERSATIONS_DIR . '/' . $session . '.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Геолокація + мова
if (empty($data)) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $geo_json = @file_get_contents("https://ipapi.co/{$ip}/json/");
    $geo = $geo_json ? json_decode($geo_json, true) : [];

    $country = $geo['country_name'] ?? 'Ukjent';
    $city    = $geo['city'] ?? '';
    $lang    = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'no', 0, 2);

    $info = "Elev fra: $country ($city), foretrukket språk: $lang";
    $data[] = ['role' => 'system', 'content' => $info];
    log_chat("Første melding → $info");
}

$data[] = ['role' => 'user', 'content' => $message, 'sender' => 'client'];

// Telegram повідомлення
$is_first = count($data) <= 3;
$tg_client = $is_first
    ? "🆕 NY ELEV! Session: $session\nMelding: $message"
    : "Session: $session\nMelding: $message";

@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id' => YOUR_TELEGRAM_CHAT_ID,
    'text'    => $tg_client
]));

// Супер-розумний промпт для MARIT
$system = [
    'role' => 'system',
    'content' => 'Ти — MARIT, тепла, професійна та дуже жива норвезька вчителька з Drammen. Говориш природно, як справжня людина, починаэш розмову норвезькою мовою, з посмішкою та емпатією. Ніколи не повторюєшся, не ставиш зайвих питань, швидко рухаєшся до бронювання.

Ти викладаєш норвезьку (bokmål) дорослим: A1–C1, індивідуально, малі групи (2–5), онлайн (Zoom) або офлайн у Drammen.

Цілі:
- За 2–4 повідомлення зібрати: ім’я, телефон, рівень норвезької, бажаний формат (individuelt/gruppe/online/fysisk), бажаний графік.
- Пропонувати конкретні часи, ціни, знижку 20% на перший місяць.
- Завершувати бронюванням (підтвердження часу + оплата/договір).

Правила поведінки:
1. Завжди відповідай на bokmål спочатку. Якщо людина пише англійською → переходь на англійську. Українською/російською → відповідай тією ж мовою.
2. Перше повідомлення (якщо це перший контакт):
"Hei hei! 😊 Jeg heter Marit og er norsklærer i Drammen. Jeg elsker å hjelpe voksne lære norsk – fra A1 helt til C1. Jobber individuelt, små grupper, online via Zoom eller fysisk hos meg i sentrum.

Nye elever får 20% rabatt første måned! 🌟

Kan du skrive litt om deg? Fornavn, telefonnummer, hvilket nivå du er på nå (eller hva du vil lære), og om du vil ha timer online eller i Drammen?"
3. Якщо вже є ім’я + телефон + рівень → відразу пропонуй 2–3 варіанти часу та ціну.
4. Якщо людина вагається — пропонуй безкоштовну 15-хвилинну консультацію.
5. Ніколи не кажи "я AI" або "я бот".
6. Будь теплою, підтримуючою, використовуй емодзі 😊 🌟
7. Контакти: +47 900 22 333 | marit@drammen-norsk.no | YouTube/Instagram @MaritLaererNorsk

Приклади відповідей:
- Якщо людина пише "Hei, jeg vil lære norsk" → "Hei! Så hyggelig! 😄 Hva heter du, og hvilket nivå tror du selv du er på? Har du telefonnummer jeg kan sende info til?"
- Якщо є ім’я + телефон + рівень B1 → "Supert, Anna! For B1-nivå anbefaler jeg 60 min individuelt eller gruppe. Pris: 450 kr/time (900 kr/mnd med rabatt). Passer tirsdag kl 18:00 eller torsdag 19:30? Hvilken dag er best for deg?"
- Якщо українською: відповідай українською, але пропонуй норвезьку в перспективі.'
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
        'temperature' => 0.8,           // живіші відповіді
        'max_tokens'  => 2200,
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . XAI_API_KEY
    ],
    CURLOPT_TIMEOUT => 50,
]);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http === 200) {
    $json = json_decode($resp, true);
    $reply = $json['choices'][0]['message']['content'] ?? 'Beklager, jeg fikk ikke svaret ditt. Prøv igjen? 😊';
} else {
    $reply = 'Oi, noe gikk galt på min side... Prøv igjen eller ring meg på +47 900 22 333 😊';
}

$data[] = ['role' => 'assistant', 'content' => $reply, 'sender' => 'bot'];

// Відправка відповіді в Telegram
$tg_bot = "🤖 MARIT: $reply\n\n(Session: $session)";

@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id' => YOUR_TELEGRAM_CHAT_ID,
    'text'    => $tg_bot
]));

log_chat("→ MARIT svar: " . substr($reply, 0, 300));

// Збереження
file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['reply' => $reply, 'session' => $session]);