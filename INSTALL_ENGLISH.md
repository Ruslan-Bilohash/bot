# Installation Guide (English)

## 1. Download and add the files

Copy all files to the root directory of your website (via FTP, file manager or Git):

- `chat-widget.js`  
- `bot.php`  
- `get-messages.php`  
- `telegram-webhook.php`  
- `admin.php`  
- `config.php`

## 2. Configure config.php
6. Test the chat

Open your website in incognito/private mode (to simulate a new user).
Click the chat icon (💬) in the bottom right corner.
The bot should greet you first and ask for name, preferred language and contact info.
Send a message — the bot replies instantly.
Check if you receive a notification in Telegram from @BILOHASH_bot.
Go to the admin panel: https://yourdomain.com/admin.php
Password: admin123

7. Common issues and solutions

Chat window flickers or disappears
→ Delete the localStorage key bilohash_chat_session (F12 → Application → Local Storage).
No message in Telegram
→ Verify that chat_id is correct (use getUpdates in browser).
HTTP 500 error on admin.php
→ Check server error log (cPanel → Error Log).
→ Temporarily add ini_set('display_errors', 1); at the top of admin.php to see the error.
Bot speaks wrong language
→ It auto-detects browser language. You can also manually select with flags in the chat (if enabled).

8. Extra tips

To clear all chat logs (for testing): delete all .json files in /conversations.
Change Grok model in config.php for faster or more advanced responses.
Protect admin.php with .htaccess (optional):

Good luck with your chatbot!
Made with ❤️ by Ruslan Bilohash – PHP developer from Drammen, Norway.
Contact: +47 462 55 885 | @bilohash
Open `config.php` and fill in your values:

```php
define('TELEGRAM_TOKEN',     ''); // your token from BotFather
define('YOUR_TELEGRAM_CHAT_ID', 000000000);   // your personal chat_id (get it via getUpdates)
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning'); // or another model
paste page: <script src="/chat-widget.js" defer></script>
