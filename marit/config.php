<?php
// === CONFIGURATION FILE — MARIT NORWEGIAN TEACHER (Drammen, Norge) ===
// All sensitive keys and settings are defined here.
// Never commit this file with real keys!

// Grok xAI API Key
define('XAI_API_KEY', 'тут токен грока');

// Telegram Bot Token
define('TELEGRAM_TOKEN', 'тут токен телеграму');

// Your Telegram chat ID
define('YOUR_TELEGRAM_CHAT_ID', 5351698956);

// Grok model
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning');

// ЗМІНИ ЦЕЙ ПАРОЛЬ НА СВОЙ СИЛЬНИЙ (використовуй 16+ символів, цифри, символи)
define('ADMIN_PASSWORD', '12345');

// CSRF secret
define('CSRF_SECRET', 'marit_csrf_very_long_secret_string_2026_change_this');

// Folders
define('CONVERSATIONS_DIR', __DIR__ . '/conversations');
define('LOG_DIR', __DIR__ . '/log');

if (!is_dir(CONVERSATIONS_DIR)) mkdir(CONVERSATIONS_DIR, 0700, true);
if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0700, true);

// Захист від прямого доступу
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Direct access forbidden.');
}