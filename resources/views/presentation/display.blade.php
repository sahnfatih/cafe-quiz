<!DOCTYPE html>
<html lang="tr" class="h-full bg-black">
<head>
    <meta charset="UTF-8">
    <title>Sunum Ekranı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { cursor: none; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-black text-white">
@php
    $joinUrl = route('participant.join', $session->code);
    $initialQuestion = $currentQuestion ? [
        'session' => [
            'id' => $session->id,
            'code' => $session->code,
            'status' => $session->status,
            'current_question_id' => $currentQuestion->id,
        ],
        'question' => [
            'id' => $currentQuestion->id,
            'text' => $currentQuestion->text,
            'option_a' => $currentQuestion->option_a,
            'option_b' => $currentQuestion->option_b,
            'option_c' => $currentQuestion->option_c,
            'option_d' => $currentQuestion->option_d,
            'media_type' => $currentQuestion->media_type,
            'image_path' => $currentQuestion->image_path,
            'youtube_url' => $currentQuestion->youtube_url,
            'youtube_start' => $currentQuestion->youtube_start,
            'youtube_end' => $currentQuestion->youtube_end,
            'points' => $currentQuestion->points,
        ],
        'mode' => 'initial',
        'top_participants' => [],
    ] : null;
@endphp
<div class="h-screen w-screen flex items-center justify-center px-8">
    <div class="max-w-6xl w-full grid gap-8 grid-cols-[3fr,1.4fr] items-stretch">
        <main id="question-area"
              class="rounded-3xl border border-slate-800/80 bg-slate-900/80 px-10 py-8 shadow-2xl shadow-black/70 flex flex-col">
            <div class="flex items-baseline justify-between gap-4 mb-4">
                <div id="status-line" class="text-slate-400 text-xl">
                    Hazırlanıyor…
                </div>
                <div class="font-mono text-sm text-slate-400">
                    Kod: <span class="text-sky-300 text-xl align-middle">{{ $session->code }}</span>
                </div>
            </div>
            <div id="question-text" class="text-3xl font-semibold leading-snug min-h-[4rem]">
            </div>

            <div id="media-area" class="mt-6 min-h-[260px] flex items-center justify-center">
                <div id="image-wrapper" class="hidden max-h-72">
                    <img id="question-image" src="" alt="" class="max-h-72 rounded-2xl shadow-lg shadow-black/60 object-contain">
                </div>
                <div id="youtube-wrapper" class="hidden w-full aspect-video rounded-2xl overflow-hidden shadow-lg shadow-black/60">
                    <iframe id="youtube-iframe" class="w-full h-full" src="" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            </div>

            <div id="options-area" class="mt-8 grid grid-cols-2 gap-4 text-xl font-semibold">
                <div id="option-wrap-A" class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 transition-all duration-500">
                    <span class="text-sky-400 mr-2">A)</span><span id="opt-a"></span>
                </div>
                <div id="option-wrap-B" class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 transition-all duration-500">
                    <span class="text-sky-400 mr-2">B)</span><span id="opt-b"></span>
                </div>
                <div id="option-wrap-C" class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 transition-all duration-500">
                    <span class="text-sky-400 mr-2">C)</span><span id="opt-c"></span>
                </div>
                <div id="option-wrap-D" class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 transition-all duration-500">
                    <span class="text-sky-400 mr-2">D)</span><span id="opt-d"></span>
                </div>
            </div>

            <section id="scoreboard"
                     class="mt-8 hidden rounded-3xl border border-emerald-500/60 bg-emerald-600/10 px-8 py-6">
                <h2 class="text-2xl font-semibold mb-4 flex items-center gap-3">
                    🏆 İlk 3
                </h2>
                <ol id="scoreboard-list" class="space-y-2 text-lg">
                </ol>
            </section>
        </main>

        <aside id="qr-panel" class="rounded-3xl border border-slate-800/80 bg-slate-950/80 px-6 py-6 shadow-2xl shadow-black/80 flex flex-col items-center justify-center">
            <div class="text-sm text-slate-300 mb-3 text-center">
                Telefonunuzla tarayıp yarışmaya katılın
            </div>
            <div class="rounded-2xl border border-slate-700 bg-slate-900/80 p-3 mb-4">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data={{ urlencode($joinUrl) }}"
                    alt="Katılım QR"
                    class="h-64 w-64"
                >
            </div>
            <div class="font-mono text-xs text-sky-300 text-center break-all max-w-xs">
                {{ $joinUrl }}
            </div>
        </aside>
    </div>
</div>

<canvas id="confetti-canvas" class="fixed inset-0 pointer-events-none"></canvas>

<script>
    const sessionCode = @json($session->code);
    const initialPayload = @json($initialQuestion);

    /* Şık wrapperlarının renklerini sıfırla */
    function resetOptionColors() {
        ['A','B','C','D'].forEach(opt => {
            const el = document.getElementById('option-wrap-' + opt);
            if (!el) return;
            el.className = 'rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 transition-all duration-500';
        });
    }

    /* Doğru cevabı büyük ekranda renklendir */
    function revealCorrectOption(correctOpt) {
        ['A','B','C','D'].forEach(opt => {
            const el = document.getElementById('option-wrap-' + opt);
            if (!el) return;
            if (opt === correctOpt) {
                el.className = 'rounded-2xl border-2 border-emerald-400 bg-emerald-500/20 px-4 py-3 scale-105 transition-all duration-500 shadow-lg shadow-emerald-500/30';
            } else {
                el.className = 'rounded-2xl border border-slate-700/50 bg-slate-900/40 px-4 py-3 opacity-40 transition-all duration-500';
            }
        });
    }

    function updateQuestion(payload) {
        const q    = payload.question;
        const mode = payload.mode;
        const top  = payload.top_participants || [];

        const status        = document.getElementById('status-line');
        const textEl        = document.getElementById('question-text');
        const optA          = document.getElementById('opt-a');
        const optB          = document.getElementById('opt-b');
        const optC          = document.getElementById('opt-c');
        const optD          = document.getElementById('opt-d');
        const imgWrapper    = document.getElementById('image-wrapper');
        const img           = document.getElementById('question-image');
        const ytWrapper     = document.getElementById('youtube-wrapper');
        const ytIframe      = document.getElementById('youtube-iframe');
        const scoreboard    = document.getElementById('scoreboard');
        const scoreboardList= document.getElementById('scoreboard-list');
        const qrPanel       = document.getElementById('qr-panel');

        /* ── Doğru cevabı göster (reveal) ── */
        if (mode === 'reveal') {
            if (q && q.correct_option) {
                status.textContent = '💡 Doğru Cevap: ' + q.correct_option;
                revealCorrectOption(q.correct_option);
            }
            return;
        }

        /* ── Sonuçları göster ── */
        if (mode === 'show_results' || mode === 'finish') {
            status.textContent = '🏆 Sonuçlar';
            resetOptionColors();
            scoreboard.classList.remove('hidden');
            scoreboardList.innerHTML = '';
            top.forEach((p, i) => {
                const li = document.createElement('li');
                const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
                li.textContent = `${medal} ${p.name} – ${p.total_score} puan (+${p.total_speed_bonus} hız bonusu)`;
                scoreboardList.appendChild(li);
            });
            if (qrPanel) qrPanel.classList.add('hidden');
            launchConfetti();
            return;
        }

        scoreboard.classList.add('hidden');
        scoreboardList.innerHTML = '';
        resetOptionColors();

        if (!q) {
            status.textContent = 'Hazırlanıyor… QR kodu tarayıp yarışmaya katılabilirsiniz.';
            textEl.textContent = '';
            optA.textContent = optB.textContent = optC.textContent = optD.textContent = '';
            imgWrapper.classList.add('hidden');
            ytWrapper.classList.add('hidden');
            ytIframe.src = '';
            if (qrPanel) qrPanel.classList.remove('hidden');
            return;
        }

        status.textContent = `${q.points} puanlık soru`;
        textEl.textContent  = q.text;
        optA.textContent    = q.option_a || '';
        optB.textContent    = q.option_b || '';
        optC.textContent    = q.option_c || '';
        optD.textContent    = q.option_d || '';

        imgWrapper.classList.add('hidden');
        ytWrapper.classList.add('hidden');
        ytIframe.src = '';

        if (q.media_type === 'image' && q.image_path) {
            imgWrapper.classList.remove('hidden');
            img.src = `{{ asset('storage') }}/${q.image_path}`;
        } else if (q.media_type === 'youtube' && q.youtube_url) {
            ytWrapper.classList.remove('hidden');
            const url     = new URL(q.youtube_url);
            const videoId = url.searchParams.get('v') || url.pathname.split('/').pop();
            const start   = q.youtube_start || 0;
            const end     = q.youtube_end   || '';
            let src = `https://www.youtube.com/embed/${videoId}?autoplay=1&start=${start}`;
            if (end) src += `&end=${end}`;
            ytIframe.src = src;
        }

        if (qrPanel) qrPanel.classList.add('hidden');
    }

    function launchConfetti() {
        const duration = 4 * 1000;
        const end = Date.now() + duration;
        const colors = ['#22c55e', '#eab308', '#f97316', '#38bdf8', '#a855f7'];

        (function frame() {
            const progress = (end - Date.now()) / duration;
            if (progress <= 0) return;
            const canvas = document.getElementById('confetti-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < 150; i++) {
                const x = Math.random() * canvas.width;
                const y = Math.random() * canvas.height;
                const size = 4 + Math.random() * 6;
                ctx.fillStyle = colors[Math.floor(Math.random() * colors.length)];
                ctx.fillRect(x, y, size, size);
            }
            requestAnimationFrame(frame);
        })();
    }

    if (initialPayload) {
        updateQuestion(initialPayload);
    }

    function waitForEcho(tries) {
        if (window.Echo) {
            window.Echo.channel('quiz.session.' + sessionCode)
                .listen('.QuizStateUpdated', (e) => {
                    updateQuestion(e);
                });
            return;
        }
        if (tries <= 0) return;
        setTimeout(() => waitForEcho(tries - 1), 100);
    }
    waitForEcho(80);
</script>
</body>
</html>

