<?php
require_once 'config.php';

// === SECURITY HEADERS (recommended by CodeCanyon & modern standards) ===
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https:; style-src \'self\' \'unsafe-inline\' https:; img-src \'self\' data: https:; font-src \'self\' https:; connect-src \'self\' https:;');
header('Content-Type: application/json; charset=utf-8');

// === Block direct access ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// === Safe input parsing ===
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

// === Validate session format (prevent path traversal & injection) ===
$session = $input['session'] ?? null;
if (!$session || !preg_match('/^s_[0-9]+_[a-f0-9]{6,12}$/', $session)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session format']);
    exit;
}

$message = trim($input['message'] ?? '');
if (strlen($message) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is empty']);
    exit;
}

// === Logging (sanitized) ===
$log_file = LOG_DIR . '/chat-' . date('Y-m-d') . '.log';
$time = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$safe_msg = substr(str_replace(["\r", "\n"], ' ', $message), 0, 400);
file_put_contents($log_file, "[$time] [$ip] [sess:$session] → $safe_msg\n", FILE_APPEND | LOCK_EX);

// === Load or create conversation ===
$file = CONVERSATIONS_DIR . '/' . $session . '.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// === Add geolocation & language only on first message ===
if (empty($data)) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $geo_json = @file_get_contents("https://ipapi.co/{$ip}/json/");
    $geo = $geo_json ? json_decode($geo_json, true) : [];

    $country = $geo['country_name'] ?? 'Unknown';
    $city    = $geo['city'] ?? '';
    $lang    = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);

    $info = "Visitor: $country ($city), browser language: $lang";
    $data[] = ['role' => 'system', 'content' => $info];
    file_put_contents($log_file, "[$time] [$ip] [sess:$session] First request → $info\n", FILE_APPEND);
}

// === Add user message ===
$data[] = ['role' => 'user', 'content' => $message, 'sender' => 'client'];

// === Send notification to Telegram ===
$is_first = count($data) <= 3;
$country_str = isset($country) ? " ($country $city)" : '';
$tg_text = $is_first
    ? "🆕 New visitor on bilohash.com$country_str\nSession: $session\nMessage: $message"
    : "bilohash.com | $session\n$message";

@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?" . http_build_query([
    'chat_id'    => YOUR_TELEGRAM_CHAT_ID,
    'text'       => $tg_text,
    'parse_mode' => 'HTML'
]));

file_put_contents($log_file, "[$time] [$ip] [sess:$session] → Sent to Telegram\n", FILE_APPEND);

// === Prepare messages for Grok ===
$system_prompt = [
    'role' => 'system',
    'content' => 'You are CONSULTANT AI, a professional PHP developer from Drammen, Norway. '
               . 'Help clients quickly and to the point with website development, custom CMS, booking systems, multilingual projects, etc. '
               . 'Speak in the user\'s language (detect automatically). '
               . 'Always start with a greeting and ask how to address them (name or preferred name). '
               . 'Then politely ask for contact details: phone, email, Telegram, WhatsApp or Viber. '
               . 'If the conversation leads to development - ask for the website URL to analyze it. '
               . 'Be polite, professional, ask clarifying questions. '
               . 'Never say you are an AI or bot.'
];

$messages = [$system_prompt];
foreach ($data as $m) {
    if ($m['sender'] !== 'you') {
        $messages[] = ['role' => $m['role'], 'content' => $m['content']];
    }
}

// === Call Grok API ===
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
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http === 200) {
    $json = json_decode($resp, true);
    $reply = $json['choices'][0]['message']['content'] ?? 'Sorry, I cannot respond right now.';
} else {
    $reply = 'Sorry, something went wrong. Try again later or message me on Telegram.';
}

$data[] = ['role' => 'assistant', 'content' => $reply, 'sender' => 'bot'];

file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));

log_chat("← Reply: " . substr($reply, 0, 400));

echo json_encode(['reply' => $reply, 'session' => $session]);