<?php
// config.php — Основні налаштування та безпека

// API ключі
define('XAI_API_KEY', 'xai-');
define('TELEGRAM_TOKEN', 'TELEGRAM_TOKEN');
define('YOUR_TELEGRAM_CHAT_ID', YOUR_TELEGRAM_CHAT_ID);
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning');

// Папки
define('CONVERSATIONS_DIR', __DIR__ . '/conversations');
define('LOG_DIR', __DIR__ . '/log');

// Створення папок
if (!is_dir(CONVERSATIONS_DIR)) mkdir(CONVERSATIONS_DIR, 0700, true);
if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0700, true);

// Безпека
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Direct access forbidden.');
}