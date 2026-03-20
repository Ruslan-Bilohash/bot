<?php
// === CONFIGURATION FILE ===
// All sensitive keys and settings are defined here.
// Never commit this file with real keys to public repositories!

// Grok xAI API Key (obtain from https://console.x.ai)
define('XAI_API_KEY', 'XAI_API_KEY');

// Telegram Bot Token (obtained from @BotFather in Telegram)
define('TELEGRAM_TOKEN', 'your-telegram-bot-token-here');

// Your personal Telegram chat ID (to receive notifications)
// Get it by messaging your bot and checking https://api.telegram.org/bot<TOKEN>/getUpdates
define('YOUR_TELEGRAM_CHAT_ID', 123456789);

// Grok model (current recommended non-reasoning model as of 2026)
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning');

// Folders for storing conversations and logs
define('CONVERSATIONS_DIR', __DIR__ . '/conversations');
define('LOG_DIR', __DIR__ . '/log');

// Automatically create folders if they don't exist (with safe permissions)
if (!is_dir(CONVERSATIONS_DIR)) {
    mkdir(CONVERSATIONS_DIR, 0700, true);
}
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0700, true);
}

// === SECURITY: Block direct access to this file ===
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Direct access to config.php is forbidden.');
}