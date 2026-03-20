// chat-widget.js — без помилки при першому відкритті
(function () {
    const API_URL     = '/bot.php';
    const HISTORY_URL = '/get-messages.php';
    const SESSION_KEY = 'bilohash_chat_session';

    let session = localStorage.getItem(SESSION_KEY);
    if (!session) {
        session = 's_' + Date.now() + '_' + Math.random().toString(36).substring(2, 12);
        localStorage.setItem(SESSION_KEY, session);
    }

    const container = document.createElement('div');
    container.id = 'bilohash-chat';
    container.style.cssText = `
        position:fixed; bottom:24px; right:24px; width:390px; height:580px;
        background:#ffffff; border-radius:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);
        display:none; flex-direction:column; overflow:hidden; z-index:9999;
        font-family:system-ui, -apple-system, sans-serif;
    `;

    const header = document.createElement('div');
    header.style.cssText = `
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color:white; padding:16px 20px; display:flex; align-items:center; justify-content:space-between;
    `;
    header.innerHTML = `
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px;height:48px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">👨‍💻</div>
            <div>
                <div style="font-weight:700;font-size:18px;">Ruslan Bilohash</div>
                <div style="font-size:13px;opacity:0.95;">PHP Developer • онлайн</div>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button id="refresh" title="Оновити чат" style="background:rgba(255,255,255,0.3);color:white;border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;font-size:18px;">↻</button>
            <button id="close" style="background:none;border:none;color:white;font-size:26px;cursor:pointer;">×</button>
        </div>
    `;

    const messages = document.createElement('div');
    messages.id = 'messages';
    messages.style.cssText = `flex:1; padding:20px; overflow-y:auto; background:#f8fafc; display:flex; flex-direction:column; gap:12px;`;

    const inputArea = document.createElement('div');
    inputArea.style.cssText = `padding:16px 20px; border-top:1px solid #e2e8f0; background:white; display:flex; gap:12px;`;
    inputArea.innerHTML = `
        <input id="input" type="text" placeholder="Напишіть повідомлення..." 
               style="flex:1; padding:14px 20px; border:1px solid #cbd5e1; border-radius:999px; outline:none; font-size:16px;">
        <button id="send" style="background:#3b82f6; color:white; border:none; border-radius:999px; width:52px; height:52px; cursor:pointer;font-size:24px;">→</button>
    `;

    container.append(header, messages, inputArea);
    document.body.appendChild(container);

    const openBtn = document.createElement('button');
    openBtn.style.cssText = `
        position:fixed; bottom:32px; right:32px; width:72px; height:72px;
        background:linear-gradient(135deg, #3b82f6, #1e40af); color:white; border:none; border-radius:50%;
        font-size:34px; cursor:pointer; box-shadow:0 12px 32px rgba(59,130,246,0.4);
        z-index:9998;
    `;
    openBtn.innerHTML = '💬';
    document.body.appendChild(openBtn);

    function addMsg(text, from) {
        const div = document.createElement('div');
        div.style.cssText = `
            max-width:88%; padding:14px 20px; border-radius:22px; line-height:1.5; font-size:15px;
            ${from === 'client' ? 'align-self:flex-end; background:#3b82f6; color:white; border-bottom-right-radius:8px;' :
              from === 'you'    ? 'align-self:flex-start; background:#10b981; color:white; border-bottom-left-radius:8px;' :
                                  'align-self:flex-start; background:#e2e8f0; color:#1e293b; border-bottom-left-radius:8px;'}
        `;
        div.innerHTML = text.replace(/\n/g, '<br>');
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    async function loadHistory() {
        try {
            const r = await fetch(`${HISTORY_URL}?session=${session}`);
            if (!r.ok) {
                // Якщо файл ще не існує — це нормально при першому відкритті
                if (r.status === 404 || r.status === 400) {
                    // Просто не додаємо помилку, бо історії ще немає
                    return;
                }
                addMsg('Не вдалося завантажити історію…', 'bot');
                return;
            }
            const history = await r.json();
            messages.innerHTML = '';
            history.forEach(m => addMsg(m.content, m.sender));
        } catch (e) {
            // Тихо ігноруємо помилку при першому завантаженні
            console.log('Історія ще не створена або помилка мережі');
        }
    }

    async function send() {
        const input = document.getElementById('input');
        const text = input.value.trim();
        if (!text) return;

        addMsg(text, 'client');
        input.value = '';

        try {
            const r = await fetch(API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({session, message: text})
            });
            const data = await r.json();
            if (data.reply) {
                addMsg(data.reply, 'bot');
                setTimeout(loadHistory, 1200);
            }
        } catch (e) {
            addMsg('Помилка з\'єднання…', 'bot');
        }
    }

    openBtn.onclick = () => {
        container.style.display = 'flex';
        openBtn.style.display = 'none';
        loadHistory(); // завантажуємо історію
        // Якщо історії ще немає — бот сам надішле привітання через bot.php
    };

    document.getElementById('close').onclick = () => {
        container.style.display = 'none';
        openBtn.style.display = 'block';
    };

    document.getElementById('refresh').onclick = loadHistory;

    document.getElementById('send').onclick = send;
    document.getElementById('input').onkeypress = e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            send();
        }
    };

    // Привітання при першому відкритті (якщо історії немає)
    setTimeout(() => {
        if (messages.children.length === 0 && container.style.display === 'flex') {
            addMsg('Добрий день! Я Ruslan — ваш помічник з веб-розробки. Як вас звати, щоб я міг до вас звертатися? 😊', 'bot');
        }
    }, 600);
})();
