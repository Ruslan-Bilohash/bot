# Installasjonsguide på norsk (Bokmål)

## 1. Last ned og legg til filene

Kopier alle filene til rotmappen på nettstedet ditt (f.eks. via FTP eller filbehandler):

- `chat-widget.js`  
- `bot.php`  
- `get-messages.php`  
- `telegram-webhook.php`  
- `admin.php`  
- `config.php`

## 2. Konfigurer config.php

mkdir conversations log
chmod 777 conversations log

{"ok":true,"result":true,"description":"Webhook was set"}

6. Test chatten

Åpne nettstedet i inkognito-modus (privat fane) for å simulere ny bruker.
Klikk på chat-ikonet (💬) nede til høyre.
Boten skal hilse deg først og spørre om navn, språk og kontaktinformasjon.
Skriv en melding — boten svarer umiddelbart.
Sjekk om du får melding i Telegram fra @BILOHASH_bot.
Gå til admin-panelet: https://dittdomene.no/admin.php
Passord: admin123

7. Vanlige problemer og løsninger

Chat-vinduet blinker eller forsvinner
→ Slett localStorage-nøkkelen bilohash_chat_session i nettleseren (F12 → Application → Local Storage).
Ingen melding i Telegram
→ Sjekk at chat_id er riktig (bruk getUpdates i nettleseren).
Feil 500 på admin.php
→ Sjekk serverens error-log (i cPanel → Error Log).
→ Legg midlertidig til ini_set('display_errors', 1); øverst i admin.php for å se feilen.
Boten snakker feil språk
→ Den bruker automatisk språk fra nettleseren din. Du kan også velge manuelt med flagg i chatten (hvis aktivert).

8. Ekstra tips

For å tømme alle chat-logger (test): slett alle .json-filer i mappen /conversations.
Endre Grok-modell i config.php for raskere eller mer avanserte svar.
Beskytt admin.php ekstra med .htaccess (valgfritt):

<Files "admin.php">
    Order Deny,Allow
    Deny from all
    Allow from 127.0.0.1 DITT_IP
</Files>

Lykke til med chatten!
Laget med ❤️ av Ruslan Bilohash – PHP-utvikler fra Drammen, Norge.
Kontakt: +47 462 55 885 | @bilohash
Åpne filen `config.php` og fyll inn dine verdier:

```php
define('TELEGRAM_TOKEN',     '8344613173:AAEY7mnVx5Z4H8LovTmm-uj5s81-PS5--JA'); // din token fra BotFather
define('YOUR_TELEGRAM_CHAT_ID', 5351698956);   // ditt personlige chat_id (finn det med getUpdates)
define('GROK_MODEL', 'grok-4.20-0309-non-reasoning'); // eller en annen modell


