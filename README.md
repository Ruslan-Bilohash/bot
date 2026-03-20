# 💬 Bilohash AI Consultant Chatbot

Розумний чат-бот-консультант для сайтів на базі **Grok xAI** з інтеграцією Telegram, адмін-панеллю та автоматичним збором контактів клієнта.

## Мови / Languages

| Мова          | Прапор                  | Опис проєкту |
|---------------|-------------------------|--------------|
| **English**   | 🇬🇧🇺🇸🇨🇦               | Modern AI Consultant Chatbot |
| **Norsk**     | 🇳🇴                     | Moderne AI-konsulent chatbot |
| **Українська**| 🇺🇦                     | Розумний AI-чатбот консультант |
| **Русский**   | 🇷🇺                     | Умный AI-чатбот консультант |

---

### 🇬🇧 **English**  
**Modern AI Consultant Chatbot** for websites powered by Grok xAI.  

**Key features:**  
- Grok xAI backend (most truthful & fastest AI in 2026)  
- Instant Telegram notifications to you  
- Reply from Telegram or built-in Admin Panel  
- Automatically asks for name + contacts (phone, email, Telegram, WhatsApp, Viber)  
- Beautiful floating widget (no flickering)  
- Full responsive admin panel with chat history  
- Browser language auto-detection  

**Ideal for:** freelancers, developers, agencies, consultants.

---

### 🇳🇴 **Norsk**  
**Moderne AI-konsulent chatbot** drevet av Grok xAI.  

**Hovedfunksjoner:**  
- Grok xAI-motor  
- Øyeblikkelige varsler i Telegram  
- Svar direkte fra Telegram eller admin-panel  
- Spør automatisk om navn og kontaktinfo  
- Pen flytende widget uten blinking  
- Full responsiv admin-panel med chat-historikk  

---

### 🇺🇦 **Українська**  
**Розумний AI-чатбот консультант** для сайтів на базі Grok xAI.  

**Основні можливості:**  
- Бекенд Grok xAI — найправдивіший ШІ 2026 року  
- Миттєві сповіщення у ваш Telegram  
- Відповіді прямо з Telegram або зручної адмін-панелі  
- Автоматично запитує ім’я + контакти (телефон, email, Telegram, WhatsApp, Viber)  
- Красивий плаваючий віджет без миготіння  
- Повноцінна адаптивна адмін-панель з історією чатів  
- Автовизначення мови браузера  

**Ідеально підходить** для фрілансерів, розробників, агентств та консультантів.

---

### 🇷🇺 **Русский**  
**Умный AI-чатбот консультант** для сайтов на базе Grok xAI.  

**Основные возможности:**  
- Бэкенд Grok xAI — самый правдивый ИИ 2026 года  
- Мгновенные уведомления в ваш Telegram  
- Ответы прямо из Telegram или удобной админ-панели  
- Автоматически спрашивает имя + контакты (телефон, email, Telegram, WhatsApp, Viber)  
- Красивый плавающий виджет без мерцания  
- Полноценная адаптивная админ-панель с историей чатов  
- Автоопределение языка браузера  

---

## 🚀 Інструкція з налаштування (українською)

### 1. Завантажте файли
Скопіюйте всі файли в корінь сайту:

- `chat-widget.js`  
- `bot.php`  
- `get-messages.php`  
- `telegram-webhook.php`  
- `admin.php`  
- `config.php`

### 2. Налаштуйте config.php

Відкрийте `config.php` і заповніть:

```php
define('TELEGRAM_TOKEN',     '8344613173:AAEY7mnVx5Z4H8LovTmm-uj5s81-PS5--JA');
define('YOUR_TELEGRAM_CHAT_ID', 5351698956);   // ваш особистий chat_id з getUpdates
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning');
