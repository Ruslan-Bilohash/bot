<?php
require_once 'config.php';
session_start();

// SECURITY HEADERS
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate-limit login (5 спроб за 10 хв)
function login_rate_limit() {
    $key = 'marit_admin_login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip');
    $file = LOG_DIR . '/' . md5($key) . '.attempts';
    $now = time();
    $attempts = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $attempts = array_filter($attempts, fn($t) => $t > $now - 600);
    if (count($attempts) >= 5) return false;
    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts));
    return true;
}

// Login
if (!isset($_SESSION['marit_admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD && login_rate_limit()) {
        if (isset($_POST['csrf']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'])) {
            $_SESSION['marit_admin_logged_in'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (!isset($_SESSION['marit_admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="no">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Marit Admin</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
            <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full">
                <h1 class="text-2xl font-bold text-teal-700 mb-6 text-center">Marit Admin</h1>
                <form method="post" class="space-y-5">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="password" name="password" placeholder="Passord" required autofocus class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 text-lg">
                    <button type="submit" class="w-full bg-teal-600 text-white py-4 rounded-xl font-semibold hover:bg-teal-700 transition text-lg">Logg inn</button>
                </form>
                <?php if (isset($_POST['password'])) echo '<p class="text-red-600 mt-4 text-center">Feil passord eller for mange forsøk. Vent 10 min.</p>'; ?>
            </div>
        </body>
        </html>
        <?php exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: marit-admin.php');
    exit;
}

// Sessions
$sessions = [];
if (is_dir(CONVERSATIONS_DIR)) {
    $files = glob(CONVERSATIONS_DIR . '/s_*.json');
    foreach ($files as $f) {
        $sess = basename($f, '.json');
        $time = (int)explode('_', $sess)[1];
        $sessions[] = ['id' => $sess, 'time' => $time, 'date' => date('d.m.Y H:i', $time)];
    }
}
usort($sessions, fn($a, $b) => $b['time'] <=> $a['time']);
?>

<!DOCTYPE html>
<html lang="no" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marit – Samtaler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-teal-700 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold">Marit Norsk – Elevsamtaler</h1>
            <a href="?logout=1" class="bg-red-600 hover:bg-red-700 px-5 py-2 rounded-lg text-sm font-medium">Logg ut</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sessions list with search -->
            <div class="lg:col-span-1 bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b bg-teal-50">
                    <input id="session-search" type="text" placeholder="Søk etter session..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div id="sessions-list" class="max-h-[50vh] lg:max-h-[80vh] overflow-y-auto">
                    <?php foreach ($sessions as $s): ?>
                        <button data-session="<?= $s['id'] ?>" class="w-full text-left p-4 hover:bg-teal-50 border-b last:border-none transition session-item">
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($s['id']) ?></div>
                            <div class="text-xs text-gray-500"><?= $s['date'] ?></div>
                        </button>
                    <?php endforeach; ?>
                    <?php if (empty($sessions)): ?>
                        <p class="p-8 text-gray-500 text-center">Ingen samtaler ennå</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat area -->
            <div class="lg:col-span-3 bg-white rounded-xl shadow flex flex-col h-[70vh] lg:h-[85vh]">
                <div id="chat-header" class="p-5 bg-teal-600 text-white font-semibold">
                    Velg en samtale til venstre
                </div>
                <div id="messages" class="flex-1 p-6 overflow-y-auto bg-gray-50 space-y-4"></div>
                <div class="p-5 border-t bg-white">
                    <form id="reply-form" class="flex gap-3">
                        <input type="hidden" id="reply-session">
                        <input id="reply-text" type="text" placeholder="Skriv svar til eleven..." class="flex-1 px-5 py-4 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <button type="submit" class="bg-teal-600 text-white px-8 py-4 rounded-xl font-medium hover:bg-teal-700">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Live search
        document.getElementById('session-search').addEventListener('input', e => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.session-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(term) ? 'block' : 'none';
            });
        });

        async function loadChat(session) {
            document.getElementById('chat-header').textContent = `Samtale: ${session}`;
            document.getElementById('reply-session').value = session;

            const res = await fetch(`/marit/marit-get-messages.php?session=${session}`);
            if (!res.ok) return;
            const data = await res.json();

            const msgDiv = document.getElementById('messages');
            msgDiv.innerHTML = '';

            data.forEach(m => {
                const bubble = document.createElement('div');
                bubble.className = `p-4 rounded-2xl max-w-[80%] ${m.sender === 'client' ? 'bg-teal-600 text-white ml-auto' : m.sender === 'you' ? 'bg-teal-200 text-gray-900' : 'bg-gray-200 text-gray-900'}`;
                bubble.textContent = m.content;
                msgDiv.appendChild(bubble);
            });
            msgDiv.scrollTop = msgDiv.scrollHeight;
        }

        document.querySelectorAll('.session-item').forEach(btn => {
            btn.addEventListener('click', () => loadChat(btn.dataset.session));
        });

        document.getElementById('reply-form').addEventListener('submit', async e => {
            e.preventDefault();
            const session = document.getElementById('reply-session').value;
            const text = document.getElementById('reply-text').value.trim();
            if (!text || !session) return alert('Skriv noe først');

            await fetch('/marit/marit-telegram-webhook.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message: `reply:${session} ${text}`})
            });

            document.getElementById('reply-text').value = '';
            loadChat(session);
        });
    </script>
</body>
</html>