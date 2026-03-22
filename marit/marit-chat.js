// marit-chat.js — MARIT Norwegian Teacher (fully responsive, secure)
(function () {
    'use strict';

    const API_URL     = '/marit/marit-bot.php';
    const HISTORY_URL = '/marit/marit-get-messages.php';
    const SESSION_KEY = 'marit_chat_session_v2';

    let session = localStorage.getItem(SESSION_KEY);
    if (!session) {
        session = 's_' + Date.now() + '_' + Math.random().toString(36).substring(2, 12);
        localStorage.setItem(SESSION_KEY, session);
    }

    const container = document.createElement('div');
    container.id = 'marit-chat';
    container.style.cssText = `
        position:fixed; bottom:20px; right:20px; width:380px; max-width:94vw; height:560px;
        background:#ffffff; border-radius:20px; box-shadow:0 20px 40px -10px rgba(0,0,0,0.2);
        display:none; flex-direction:column; overflow:hidden; z-index:9999;
        font-family:system-ui, sans-serif;
    `;

    const header = document.createElement('div');
    header.style.cssText = `
        background: linear-gradient(135deg, #0369a1, #0ea5e9);
        color:white; padding:14px 18px; display:flex; align-items:center; justify-content:space-between;
    `;
    header.innerHTML = `
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:44px;height:44px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;box-shadow:0 3px 10px rgba(0,0,0,0.15);">👩‍🏫</div>
            <div>
                <div style="font-weight:700;font-size:17px;">MARIT – Norsk lærer</div>
                <div style="font-size:12px;opacity:0.95;">Drammen • online / fysisk</div>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button id="refresh" title="Oppdater" style="background:rgba(255,255,255,0.25);color:white;border:none;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:16px;">↻</button>
            <button id="close" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">×</button>
        </div>
    `;

    const messages = document.createElement('div');
    messages.id = 'messages';
    messages.style.cssText = `flex:1; padding:16px; overflow-y:auto; background:#f9fafb; display:flex; flex-direction:column; gap:10px;`;

    const inputArea = document.createElement('div');
    inputArea.style.cssText = `padding:14px 18px; border-top:1px solid #e5e7eb; background:white; display:flex; gap:10px;`;
    inputArea.innerHTML = `
        <input id="input" type="text" placeholder="Skriv melding..." 
               style="flex:1; padding:12px 18px; border:1px solid #d1d5db; border-radius:999px; outline:none; font-size:15px;">
        <button id="send" style="background:#0369a1; color:white; border:none; border-radius:999px; width:48px; height:48px; cursor:pointer;font-size:22px;">→</button>
    `;

    container.append(header, messages, inputArea);
    document.body.appendChild(container);

    const openBtn = document.createElement('button');
    openBtn.style.cssText = `
        position:fixed; bottom:28px; right:28px; width:66px; height:66px;
        background:linear-gradient(135deg, #0369a1, #0ea5e9); color:white; border:none; border-radius:50%;
        font-size:30px; cursor:pointer; box-shadow:0 10px 25px rgba(3,105,161,0.35);
        z-index:9998;
    `;
    openBtn.innerHTML = '📚';
    document.body.appendChild(openBtn);

    function addMsg(text, from) {
        const div = document.createElement('div');
        div.style.cssText = `
            max-width:86%; padding:12px 18px; border-radius:18px; line-height:1.45; font-size:14.5px;
            ${from === 'client' ? 'align-self:flex-end; background:#0369a1; color:white; border-bottom-right-radius:6px;' :
              from === 'you'    ? 'align-self:flex-start; background:#60a5fa; color:white; border-bottom-left-radius:6px;' :
                                  'align-self:flex-start; background:#e5e7eb; color:#111827; border-bottom-left-radius:6px;'}
        `;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    async function loadHistory() {
        try {
            const r = await fetch(`${HISTORY_URL}?session=${session}`);
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
                setTimeout(loadHistory, 800);
            }
        } catch (e) {
            addMsg('Tilkoblingsfeil. Prøv igjen.', 'bot');
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