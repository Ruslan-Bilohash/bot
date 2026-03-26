// chat.js — Чат для Tatjana Massage Studio (Body & Face)
(function () {
    'use strict';

    const API_URL = '/chat/bot.php';
    const HISTORY_URL = '/chat/get-messages.php';
    const SESSION_KEY = 'tatjana_chat_session_v2';

    let session = localStorage.getItem(SESSION_KEY);
    if (!session) {
        session = 's_' + Date.now() + '_' + Math.random().toString(36).substring(2, 12);
        localStorage.setItem(SESSION_KEY, session);
    }

    const container = document.createElement('div');
    container.id = 'tatjana-chat';
    container.style.cssText = `
        position:fixed; bottom:20px; right:20px; width:400px; max-width:94vw; height:620px;
        background:#ffffff; border-radius:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);
        display:none; flex-direction:column; overflow:hidden; z-index:9999;
        font-family:system-ui, -apple-system, sans-serif;
    `;

    const header = document.createElement('div');
    header.style.cssText = `
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color:white; padding:16px 20px; display:flex; align-items:center; justify-content:space-between;
    `;
    header.innerHTML = `
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px;height:48px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 4px 12px rgba(0,0,0,0.2);">💆‍♀️</div>
            <div>
                <div style="font-weight:700;font-size:18px;">Тетяна • Массаж</div>
                <div style="font-size:13px;opacity:0.95;">Body & Face Studio • Vilnius</div>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button id="refresh" title="Оновити" style="background:rgba(255,255,255,0.25);color:white;border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:18px;">↻</button>
            <button id="close" style="background:none;border:none;color:white;font-size:28px;cursor:pointer;line-height:1;">×</button>
        </div>
    `;

    const messages = document.createElement('div');
    messages.id = 'messages';
    messages.style.cssText = `flex:1; padding:20px; overflow-y:auto; background:#f8fafc; display:flex; flex-direction:column; gap:12px;`;

    const inputArea = document.createElement('div');
    inputArea.style.cssText = `padding:16px 20px; border-top:1px solid #e2e8f0; background:white; display:flex; gap:12px;`;
    inputArea.innerHTML = `
        <input id="input" type="text" placeholder="Напишіть повідомлення..." 
               style="flex:1; padding:14px 20px; border:1px solid #cbd5e1; border-radius:9999px; outline:none; font-size:15.5px;">
        <button id="send" style="background:#4f46e5; color:white; border:none; border-radius:9999px; width:52px; height:52px; cursor:pointer;font-size:24px;">→</button>
    `;

    container.append(header, messages, inputArea);
    document.body.appendChild(container);

    const openBtn = document.createElement('button');
    openBtn.style.cssText = `
        position:fixed; bottom:30px; right:30px; width:70px; height:70px;
        background:linear-gradient(135deg, #4f46e5, #7c3aed); color:white; border:none; border-radius:50%;
        font-size:32px; cursor:pointer; box-shadow:0 15px 30px rgba(79,70,229,0.4);
        z-index:9998; transition:all 0.3s ease;
    `;
    openBtn.innerHTML = '💆‍♀️';
    document.body.appendChild(openBtn);

    function addMsg(text, from) {
        const div = document.createElement('div');
        div.style.cssText = `
            max-width:88%; padding:13px 19px; border-radius:20px; line-height:1.5; font-size:15px;
            ${from === 'client' ? 'align-self:flex-end; background:#4f46e5; color:white; border-bottom-right-radius:4px;' :
              from === 'you'    ? 'align-self:flex-start; background:#6366f1; color:white; border-bottom-left-radius:4px;' :
                                  'align-self:flex-start; background:#f1f5f9; color:#1e2937; border-bottom-left-radius:4px;'}
        `;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    async function loadHistory() {
        try {
            const r = await fetch(`${HISTORY_URL}?session=${encodeURIComponent(session)}`);
            if (!r.ok) return;
            const history = await r.json();
            messages.innerHTML = '';
            history.forEach(m => addMsg(m.content, m.sender));
        } catch (e) {}
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
                setTimeout(loadHistory, 700);
            }
        } catch (e) {
            addMsg('Вибачте, проблема зі зв\'язком. Спробуйте ще раз.', 'bot');
        }
    }

    openBtn.onclick = () => {
        container.style.display = 'flex';
        openBtn.style.display = 'none';
        loadHistory();
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
})();