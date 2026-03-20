<?php
// ==================== НАСТРОЙКИ ====================

// Ключ xAI Grok API
define('XAI_API_KEY', 'xai-API ваш ключ');

// Telegram — обов’язково заміни chat_id на свій реальний!
define('TELEGRAM_TOKEN',     'API TELEGRAM');
define('YOUR_TELEGRAM_CHAT_ID', 000000000000);   // ← це твій справжній chat_id

// Модель Grok
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning');

// Папки
define('CONVERSATIONS_DIR', __DIR__ . '/conversations');
define('LOG_DIR',           __DIR__ . '/log');

if (!is_dir(CONVERSATIONS_DIR)) {
    mkdir(CONVERSATIONS_DIR, 0777, true);
    chmod(CONVERSATIONS_DIR, 0777);
}
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
    chmod(LOG_DIR, 0777);
}