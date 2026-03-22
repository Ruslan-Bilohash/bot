<?php
// marit-admin.php — покращена адмінка для читання чатів (2026 версія)

require_once 'config.php';
session_start();

// ─── SECURITY HEADERS (посилені) ────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');

// Rate limiting логіну — 5 спроб / 10 хв
function login_rate_limit(): bool {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key  = 'marit_admin_login_' . md5($ip);
    $file = LOG_DIR . '/' . $key . '.attempts';
    $now  = time();

    $attempts = file_exists($file) ? json_decode(@file_get_contents($file), true) ?? [] : [];
    $attempts = array_filter($attempts, fn($t) => $t > $now - 600);

    if (count($attempts) >= 5) return false;

    $attempts[] = $now;
    @file_put_contents($file, json_encode($attempts), LOCK_EX);
    return true;
}

// ─── LOGIN ──────────────────────────────────────────────────────────────────
if (!isset($_SESSION['marit_admin_logged_in']) || !$_SESSION['marit_admin_logged_in']) {
    $login_error = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
        if (login_rate_limit() &&
            isset($_POST['csrf']) &&
            hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf']) &&
            $_POST['password'] === ADMIN_PASSWORD) {

            $_SESSION['marit_admin_logged_in'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = true;
        }
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<!DOCTYPE html>
<html lang="no" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marit Admin – Logg inn</title>
    <!-- Тимчасово CDN, але нижче інструкція як замінити -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-md">
        <h1 class="text-3xl font-bold text-teal-700 mb-8 text-center">Marit Admin</h1>

        <form method="post" class="space-y-6">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="password" name="password" placeholder="Passord" required autofocus
                   class="w-full px-5 py-4 border border-gray-300 rounded-xl text-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent transition">
            <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white py-4 rounded-xl font-semibold text-lg transition duration-200">
                Logg inn
            </button>
        </form>

        <?php if ($login_error): ?>
            <p class="mt-6 text-red-600 text-center font-medium">
                Feil passord eller for mange forsøk.<br>Vent 10 minutter og prøv igjen.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
    exit;
}

// ─── LOGOUT ─────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

// ─── ЗБІР СЕСІЙ ──────────────────────────────────────────────────────────────
$sessions = [];
if (is_dir(CONVERSATIONS_DIR)) {
    $files = glob(CONVERSATIONS_DIR . '/s_*.json');
    foreach ($files as $file) {
        $base = basename($file, '.json');
        if (preg_match('/^s_(\d+)_[a-z0-9]+$/', $base, $m)) {
            $ts = (int)$m[1];
            $sessions[] = [
                'id'   => $base,
                'time' => $ts,
                'date' => date('d.m.Y H:i', $ts)
            ];
        }
    }
}
usort($sessions, fn($a, $b) => $b['time'] <=> $a['time']);
?>

<!DOCTYPE html>
<html lang="no" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marit – Elevsamtaler (lesemodus)</title>
    <!-- Заміни на локальний файл після Tailwind CLI (див. інструкцію нижче) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen antialiased">

<header class="bg-teal-700 text-white shadow sticky top-0 z-20">
    <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-xl md:text-2xl font-bold">Marit Norsk – Samtaler</h1>
        <a href="?logout=1" class="bg-red-600 hover:bg-red-700 px-5 py-2 rounded-lg text-sm md:text-base font-medium transition">
            Logg ut
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Список сесій -->
        <section class="lg:col-span-1 bg-white rounded-xl shadow-lg overflow-hidden flex flex-col h-[65vh] sm:h-[75vh] lg:h-[85vh]">
            <div class="p-4 border-b bg-teal-50">
                <input id="search" type="search" placeholder="Søk etter session, dato..." 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin divide-y divide-gray-100">
                <?php if (empty($sessions)): ?>
                    <p class="p-10 text-center text-gray-500 italic">Ingen samtaler ennå...</p>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                    <button data-session="<?= htmlspecialchars($s['id']) ?>"
                            class="session-btn w-full text-left px-5 py-4 hover:bg-teal-50 transition flex flex-col gap-1">
                        <div class="font-medium text-gray-900 truncate"><?= htmlspecialchars($s['id']) ?></div>
                        <div class="text-sm text-gray-600"><?= $s['date'] ?></div>
                    </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Чат (тільки читання) -->
        <section class="lg:col-span-3 bg-white rounded-xl shadow-lg flex flex-col h-[65vh] sm:h-[75vh] lg:h-[85vh] overflow-hidden">
            <div id="header" class="p-5 bg-teal-600 text-white font-semibold text-lg md:text-xl">
                Velg en samtale til venstre
            </div>
            <div id="messages" class="flex-1 p-5 md:p-6 overflow-y-auto bg-gray-50 space-y-4 scrollbar-thin"></div>
            <footer class="p-4 border-t bg-white text-center text-sm text-gray-500">
                Lesemodus • Svar sendes via Telegram
            </footer>
        </section>

    </div>
</main>

<script>
// Живий пошук
document.getElementById('search')?.addEventListener('input', e => {
    const val = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.session-btn').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});

// Завантаження чату
async function loadChat(session) {
    const header = document.getElementById('header');
    const msgDiv = document.getElementById('messages');

    header.textContent = `Samtale: ${session}`;
    msgDiv.innerHTML = '<div class="text-center py-12 text-gray-400">Laster...</div>';

    try {
        const res = await fetch(`/marit/marit-get-messages.php?session=${encodeURIComponent(session)}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        msgDiv.innerHTML = data.length === 0 
            ? '<p class="text-center py-12 text-gray-500 italic">Ingen meldinger ennå</p>'
            : '';

        data.forEach(m => {
            const isClient = m.sender === 'client';
            const bubble = document.createElement('div');
            bubble.className = `p-4 rounded-2xl max-w-[85%] md:max-w-[75%] shadow break-words ${
                isClient ? 'bg-teal-600 text-white ml-auto' : 'bg-teal-100 text-gray-900'
            }`;

            bubble.textContent = m.content; // textContent = безпечний від XSS

            if (m.time) {
                const time = document.createElement('div');
                time.className = `text-xs mt-1 opacity-70 ${isClient ? 'text-right' : 'text-left'}`;
                time.textContent = new Date(m.time * 1000).toLocaleString('no-NO', {
                    hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit'
                });
                bubble.appendChild(time);
            }

            msgDiv.appendChild(bubble);
        });

        msgDiv.scrollTop = msgDiv.scrollHeight;

        // Виділення активної сесії
        document.querySelectorAll('.session-btn').forEach(b => {
            b.classList.toggle('bg-teal-100', b.dataset.session === session);
            b.classList.toggle('border-l-4', b.dataset.session === session);
            b.classList.toggle('border-teal-600', b.dataset.session === session);
        });
    } catch (err) {
        msgDiv.innerHTML = `<p class="text-red-600 text-center py-12">Feil: ${err.message}</p>`;
    }
}

// Прив'язка кліків
document.querySelectorAll('.session-btn').forEach(btn => {
    btn.addEventListener('click', () => loadChat(btn.dataset.session));
});
</script>
</body>
</html>
