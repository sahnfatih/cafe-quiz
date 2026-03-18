<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Sunum Ekranı · Cafe Quiz Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { cursor: none; overflow: hidden; }

        @keyframes timer-pulse  { 0%,100%{opacity:1} 50%{opacity:.55} }
        @keyframes option-enter { from{transform:translateY(22px) scale(.95);opacity:0} to{transform:none;opacity:1} }
        @keyframes score-slide  { from{transform:translateX(-32px);opacity:0} to{transform:none;opacity:1} }
        @keyframes lobby-float  { 0%,100%{transform:translateY(0) rotate(-2deg)} 50%{transform:translateY(-14px) rotate(2deg)} }
        @keyframes fade-in-sc   { from{opacity:0;transform:scale(.96)} to{opacity:1;transform:none} }
        @keyframes reveal-pulse { 0%{box-shadow:0 0 0 0 rgba(52,211,153,.7)} 70%{box-shadow:0 0 0 22px rgba(52,211,153,0)} 100%{box-shadow:0 0 0 0 rgba(52,211,153,0)} }

        .option-anim    { animation: option-enter .45s cubic-bezier(.22,.61,.36,1) both; }
        .score-anim     { animation: score-slide  .4s ease-out both; }
        .lobby-icon     { animation: lobby-float  4s ease-in-out infinite; }
        .timer-urgent-bar { animation: timer-pulse .4s ease-in-out infinite; }
        .correct-reveal { animation: reveal-pulse .8s ease-out; }
        .fade-in-sc     { animation: fade-in-sc .35s ease-out both; }

        #options-area > div { font-size: clamp(1rem, 2.2vw, 1.5rem); }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white">

@php
    $joinUrl = route('participant.join', $session->code);
    $initialQuestion = $currentQuestion ? [
        'session'  => ['id' => $session->id, 'code' => $session->code, 'status' => $session->status, 'current_question_id' => $currentQuestion->id],
        'question' => [
            'id'            => $currentQuestion->id,
            'text'          => $currentQuestion->text,
            'option_a'      => $currentQuestion->option_a,
            'option_b'      => $currentQuestion->option_b,
            'option_c'      => $currentQuestion->option_c,
            'option_d'      => $currentQuestion->option_d,
            'media_type'    => $currentQuestion->media_type,
            'image_path'    => $currentQuestion->image_path,
            'video_path'    => $currentQuestion->video_path ?? null,
            'youtube_url'   => $currentQuestion->youtube_url,
            'youtube_start' => $currentQuestion->youtube_start,
            'youtube_end'   => $currentQuestion->youtube_end,
            'points'        => $currentQuestion->points,
        ],
        'mode'             => 'initial',
        'top_participants' => [],
        'time_limit'       => $session->time_limit,
        'started_at_ms'    => $session->current_question_started_at
                                ? $session->current_question_started_at->getTimestampMs()
                                : null,
    ] : null;
@endphp

{{-- ── Timer Bar (üst şerit) ── --}}
<div id="timer-bar-wrap" class="fixed top-0 left-0 right-0 z-50 h-1.5 bg-slate-800/60">
    <div id="timer-bar" class="h-full transition-none"
         style="width:100%;background:linear-gradient(to right,#38bdf8,#a78bfa)"></div>
</div>

{{-- ── Ana Layout ── --}}
<div class="h-screen w-screen flex flex-col pt-1.5">

    {{-- ── Üst Bar ── --}}
    <div class="shrink-0 flex items-center px-8 py-3 border-b border-slate-800/60">
        <div id="status-line" class="text-slate-300 font-bold flex-1"
             style="font-size:clamp(1rem,2vw,1.5rem)">Bekleniyor…</div>
        <div class="flex items-center gap-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3 py-1">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs text-emerald-400 font-medium">Canlı</span>
        </div>
    </div>

    {{-- ── İçerik Alanı ── --}}
    <div class="flex-1 flex overflow-hidden">

        {{-- ── Soru Alanı ── --}}
        <main id="question-area" class="flex-1 flex flex-col px-10 py-6 min-w-0 overflow-hidden">

            {{-- Soru Metni --}}
            <div id="question-text" class="text-white font-black leading-tight mb-5 flex-shrink-0"
                 style="font-size:clamp(1.6rem,3.8vw,3rem);min-height:5rem"></div>

            {{-- Medya --}}
            <div id="media-area" class="mb-5 flex items-center justify-center max-h-60">
                <div id="image-wrapper" class="hidden">
                    <img id="question-image" src="" alt=""
                         class="max-h-56 rounded-2xl shadow-2xl shadow-black/60 object-contain">
                </div>
                <div id="video-wrapper" class="hidden w-full rounded-2xl overflow-hidden shadow-2xl shadow-black/60 max-h-60">
                    <video id="question-video" controls autoplay muted
                           class="w-full max-h-60 rounded-2xl" src=""></video>
                </div>
                <div id="youtube-wrapper" class="hidden w-full aspect-video rounded-2xl overflow-hidden shadow-2xl shadow-black/60 max-h-60">
                    <iframe id="youtube-iframe" class="w-full h-full" src="" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            </div>

            {{-- Şıklar --}}
            <div id="options-area" class="grid grid-cols-2 gap-4 mt-auto">
                <div id="option-wrap-A" class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60 px-5 py-4 flex items-center gap-4 transition-all duration-500" style="animation-delay:.05s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-sky-500 flex items-center justify-center text-white font-black shadow-lg shadow-sky-900/50 text-xl">A</span>
                    <span id="opt-a" class="font-semibold text-white leading-snug flex-1"></span>
                </div>
                <div id="option-wrap-B" class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60 px-5 py-4 flex items-center gap-4 transition-all duration-500" style="animation-delay:.12s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-violet-500 flex items-center justify-center text-white font-black shadow-lg shadow-violet-900/50 text-xl">B</span>
                    <span id="opt-b" class="font-semibold text-white leading-snug flex-1"></span>
                </div>
                <div id="option-wrap-C" class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60 px-5 py-4 flex items-center gap-4 transition-all duration-500" style="animation-delay:.19s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-amber-500 flex items-center justify-center text-white font-black shadow-lg shadow-amber-900/50 text-xl">C</span>
                    <span id="opt-c" class="font-semibold text-white leading-snug flex-1"></span>
                </div>
                <div id="option-wrap-D" class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60 px-5 py-4 flex items-center gap-4 transition-all duration-500" style="animation-delay:.26s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-rose-500 flex items-center justify-center text-white font-black shadow-lg shadow-rose-900/50 text-xl">D</span>
                    <span id="opt-d" class="font-semibold text-white leading-snug flex-1"></span>
                </div>
            </div>

            {{-- Skor tablosu --}}
            <section id="scoreboard" class="hidden flex-1 flex flex-col justify-center">
                <h2 id="scoreboard-title"
                    class="font-black mb-6 text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-amber-300"
                    style="font-size:clamp(1.8rem,4vw,3rem)">🏆 Sonuçlar</h2>
                <ol id="scoreboard-list" class="space-y-3"></ol>
            </section>

        </main>

        {{-- ── QR Panel (lobi) ── --}}
        <aside id="qr-panel"
               class="w-72 shrink-0 border-l border-slate-800/60 bg-slate-950/40
                      flex flex-col items-center justify-center gap-5 px-6 py-8">
            <div class="lobby-icon text-6xl">🎯</div>
            <div class="text-center space-y-1">
                <p class="font-black text-white" style="font-size:clamp(1rem,1.8vw,1.4rem)">Katılmak için tara!</p>
                <p class="text-slate-400 text-sm">Telefonunla QR kodu okut</p>
            </div>
            <div class="rounded-2xl border-2 border-slate-700 bg-white p-3 shadow-2xl shadow-black/60">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($joinUrl) }}"
                     alt="Katılım QR" class="h-60 w-60 block rounded-lg">
            </div>
            <div class="rounded-2xl bg-sky-500/10 border border-sky-500/30 px-5 py-2.5 text-center">
                <p class="text-xs text-slate-500 mb-0.5">Katılım Kodu</p>
                <p class="font-mono font-black text-sky-300 tracking-[0.3em] text-2xl">{{ $session->code }}</p>
            </div>
        </aside>

    </div>
</div>

<canvas id="confetti-canvas" class="fixed inset-0 pointer-events-none z-40"></canvas>

<script>
/* ── Sabitler ── */
const SESSION_CODE   = @json($session->code);
const INITIAL        = @json($initialQuestion);
const STORAGE_BASE   = '{{ asset('storage') }}';

/* ── Timer Bar ── */
let _timerInterval = null;
function startTimerBar(tl, sat) {
    stopTimerBar();
    const bar = document.getElementById('timer-bar');
    if (!bar || !tl || !sat) {
        if (bar) { bar.style.width = '100%'; bar.style.background = 'linear-gradient(to right,#38bdf8,#a78bfa)'; }
        return;
    }
    const tick = () => {
        const rem = Math.max(0, tl - (Date.now() - sat) / 1000);
        bar.style.width = (rem / tl * 100) + '%';
        if      (rem <= 5)  { bar.style.background = '#f87171'; bar.classList.add('timer-urgent-bar'); }
        else if (rem <= 10) { bar.style.background = '#fb923c'; bar.classList.remove('timer-urgent-bar'); }
        else                { bar.style.background = 'linear-gradient(to right,#38bdf8,#a78bfa)'; bar.classList.remove('timer-urgent-bar'); }
        if (rem <= 0) stopTimerBar();
    };
    tick();
    _timerInterval = setInterval(tick, 150);
}
function stopTimerBar() {
    clearInterval(_timerInterval); _timerInterval = null;
    const bar = document.getElementById('timer-bar');
    if (bar) bar.classList.remove('timer-urgent-bar');
}
function resetTimerBar() {
    stopTimerBar();
    const bar = document.getElementById('timer-bar');
    if (bar) { bar.style.width = '100%'; bar.style.background = 'linear-gradient(to right,#38bdf8,#a78bfa)'; }
}

/* ── Renk sıfırla ── */
function resetOptionColors() {
    ['A','B','C','D'].forEach(opt => {
        const el = document.getElementById('option-wrap-' + opt);
        if (el) el.className = 'rounded-2xl border border-slate-700/60 bg-slate-800/60 px-5 py-4 flex items-center gap-4 transition-all duration-500';
    });
}

/* ── Doğru vurgula ── */
function revealCorrectOption(correct) {
    ['A','B','C','D'].forEach(opt => {
        const el = document.getElementById('option-wrap-' + opt);
        if (!el) return;
        if (opt === correct)
            el.className = 'correct-reveal rounded-2xl border-2 border-emerald-400 bg-emerald-500/20 px-5 py-4 flex items-center gap-4 scale-[1.03] transition-all duration-500 shadow-xl shadow-emerald-500/30';
        else
            el.className = 'rounded-2xl border border-slate-700/20 bg-slate-900/30 px-5 py-4 flex items-center gap-4 opacity-25 transition-all duration-500';
    });
}

/* ── Medya temizle ── */
function clearMedia() {
    document.getElementById('image-wrapper')?.classList.add('hidden');
    document.getElementById('video-wrapper')?.classList.add('hidden');
    document.getElementById('youtube-wrapper')?.classList.add('hidden');
    const vid = document.getElementById('question-video');
    const ytf = document.getElementById('youtube-iframe');
    if (vid) { vid.pause(); vid.src = ''; }
    if (ytf)  ytf.src = '';
}

/* ── QR Panel göster/gizle ── */
function showQR()  { document.getElementById('qr-panel')?.classList.remove('hidden'); }
function hideQR()  { document.getElementById('qr-panel')?.classList.add('hidden'); }

/* ── Scoreboard göster/gizle ── */
function showScoreboard()  { document.getElementById('scoreboard')?.classList.remove('hidden'); }
function hideScoreboard()  { document.getElementById('scoreboard')?.classList.add('hidden'); }
function showOptions()     { document.getElementById('options-area')?.classList.remove('hidden'); }
function hideOptions()     { document.getElementById('options-area')?.classList.add('hidden'); }

/* ── Konfeti ── */
function launchConfetti() {
    const canvas = document.getElementById('confetti-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    const colors = ['#22c55e','#eab308','#f97316','#38bdf8','#a855f7','#ec4899','#f43f5e','#fbbf24'];
    const pieces = Array.from({ length: 250 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height - canvas.height,
        w: 4 + Math.random() * 9, h: 4 + Math.random() * 9,
        c: colors[Math.floor(Math.random() * colors.length)],
        vx: (Math.random() - .5) * 3, vy: 2 + Math.random() * 4,
        r: Math.random() * Math.PI * 2, vr: (Math.random() - .5) * .15,
    }));
    const end = Date.now() + 6000;
    (function frame() {
        if (Date.now() > end) { ctx.clearRect(0, 0, canvas.width, canvas.height); return; }
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pieces.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.r += p.vr;
            if (p.y > canvas.height) { p.y = -10; p.x = Math.random() * canvas.width; }
            ctx.save(); ctx.translate(p.x + p.w/2, p.y + p.h/2); ctx.rotate(p.r);
            ctx.fillStyle = p.c; ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h); ctx.restore();
        });
        requestAnimationFrame(frame);
    })();
}

/* ── Sonuç listesi oluştur ── */
function renderScoreboard(top, isAll) {
    const scoreList  = document.getElementById('scoreboard-list');
    const scoreTitle = document.getElementById('scoreboard-title');
    if (!scoreList) return;

    if (scoreTitle) scoreTitle.textContent = isAll ? '🏆 Tüm Sonuçlar' : '🏆 Sonuçlar';

    scoreList.innerHTML = '';
    scoreList.className = (isAll && top.length > 3) ? 'grid grid-cols-2 gap-2' : 'space-y-3';

    const medals = ['🥇','🥈','🥉'];
    const bgCls  = [
        'bg-gradient-to-r from-yellow-500/20 to-amber-500/10 border-yellow-500/40',
        'bg-gradient-to-r from-slate-500/20 to-slate-400/10 border-slate-500/40',
        'bg-gradient-to-r from-orange-700/20 to-orange-600/10 border-orange-700/40',
    ];

    top.forEach((p, i) => {
        const li = document.createElement('li');
        li.className = 'score-anim rounded-2xl border px-5 py-3 flex items-center justify-between ' + (bgCls[i] || 'bg-slate-800/40 border-slate-700/50');
        li.style.animationDelay = Math.min(i * 0.07, 1.4) + 's';
        const nSz  = isAll ? 'font-size:clamp(.85rem,1.6vw,1.2rem)' : 'font-size:clamp(1rem,2.2vw,1.5rem)';
        const sSz  = isAll ? 'font-size:clamp(.9rem,1.8vw,1.3rem)' : 'font-size:clamp(1.1rem,2.5vw,1.8rem)';
        const icon = medals[i]
            ? '<span style="font-size:clamp(1.2rem,' + (isAll ? '1.8' : '3') + 'vw,' + (isAll ? '1.5' : '2.2') + 'rem)">' + medals[i] + '</span>'
            : '<span class="font-black text-slate-500" style="' + nSz + '">' + (i + 1) + '.</span>';
        li.innerHTML =
            '<div class="flex items-center gap-2 min-w-0 flex-1">' +
                icon +
                '<div class="min-w-0">' +
                    '<span class="font-black text-white block truncate" style="' + nSz + '">' + p.name + '</span>' +
                    (p.team_name ? '<span class="text-violet-400 block truncate" style="font-size:clamp(.7rem,1.2vw,.9rem)">' + p.team_name + '</span>' : '') +
                '</div>' +
            '</div>' +
            '<div class="text-right shrink-0 ml-3">' +
                '<span class="font-black ' + (i < 3 ? 'text-emerald-300' : 'text-sky-300') + ' block" style="' + sSz + '">' + p.total_score + 'p</span>' +
                (p.total_speed_bonus > 0 ? '<span class="text-amber-400 block" style="font-size:clamp(.6rem,1vw,.8rem)">+' + p.total_speed_bonus + ' hız</span>' : '') +
            '</div>';
        scoreList.appendChild(li);
    });
}

/* ══════════════════════════════════════════════════════════
   ANA GÜNCELLEME FONKSİYONU
══════════════════════════════════════════════════════════ */
function updateDisplay(payload) {
    const q    = payload.question;
    const mode = payload.mode;
    const top  = payload.top_participants || [];
    const tl   = payload.time_limit   || 0;
    const sat  = payload.started_at_ms || null;

    const statusEl = document.getElementById('status-line');

    /* ─── REVEAL ─── */
    if (mode === 'reveal') {
        stopTimerBar();
        if (q && q.correct_option) {
            statusEl.textContent = '💡 Doğru Cevap: ' + q.correct_option;
            revealCorrectOption(q.correct_option);
        }
        return;
    }

    /* ─── LOBBY / BEKLEME ─── */
    if (mode === 'lobby') {
        stopTimerBar();
        resetTimerBar();
        statusEl.textContent = '📱 QR kodu okutarak katılın!';
        document.getElementById('question-text').textContent = '';
        ['a','b','c','d'].forEach(x => { const el = document.getElementById('opt-' + x); if (el) el.textContent = ''; });
        clearMedia();
        hideScoreboard();
        showOptions();
        resetOptionColors();
        showQR();
        return;
    }

    /* ─── SONUÇLAR (top 3) ─── */
    if (mode === 'show_results' || mode === 'finish') {
        stopTimerBar();
        statusEl.textContent = '🏆 Sonuçlar';
        clearMedia();
        resetOptionColors();
        hideOptions();
        hideQR();
        renderScoreboard(top, false);
        showScoreboard();
        launchConfetti();
        return;
    }

    /* ─── TÜM SONUÇLAR ─── */
    if (mode === 'show_all_results') {
        stopTimerBar();
        statusEl.textContent = '🏆 Tüm Sonuçlar';
        clearMedia();
        resetOptionColors();
        hideOptions();
        hideQR();
        renderScoreboard(top, true);
        showScoreboard();
        launchConfetti();
        return;
    }

    /* ─── YENİ SORU (veya soru yok → lobi) ─── */
    if (!q) {
        /* Soru yok → lobi gibi davran */
        stopTimerBar();
        resetTimerBar();
        statusEl.textContent = '📱 QR kodu okutarak katılın!';
        document.getElementById('question-text').textContent = '';
        clearMedia();
        hideScoreboard();
        showOptions();
        resetOptionColors();
        showQR();
        return;
    }

    /* Yeni soru */
    hideQR();
    hideScoreboard();
    showOptions();
    resetOptionColors();

    statusEl.textContent = q.points + ' puanlık soru';
    document.getElementById('question-text').textContent = q.text;
    const optA = document.getElementById('opt-a');
    const optB = document.getElementById('opt-b');
    const optC = document.getElementById('opt-c');
    const optD = document.getElementById('opt-d');
    if (optA) optA.textContent = q.option_a || '';
    if (optB) optB.textContent = q.option_b || '';
    if (optC) optC.textContent = q.option_c || '';
    if (optD) optD.textContent = q.option_d || '';

    /* Şık animasyonu yenile */
    ['A','B','C','D'].forEach((l, i) => {
        const el = document.getElementById('option-wrap-' + l);
        if (!el) return;
        el.classList.remove('option-anim');
        void el.offsetWidth;
        el.classList.add('option-anim');
        el.style.animationDelay = (i * 0.07) + 's';
    });

    /* Medya */
    clearMedia();
    if (q.media_type === 'image' && q.image_path) {
        const img = document.getElementById('question-image');
        const iw  = document.getElementById('image-wrapper');
        if (img) img.src = STORAGE_BASE + '/' + q.image_path;
        if (iw)  iw.classList.remove('hidden');
    } else if (q.media_type === 'video' && q.video_path) {
        const vid = document.getElementById('question-video');
        const vw  = document.getElementById('video-wrapper');
        if (vid) { vid.src = STORAGE_BASE + '/' + q.video_path; vid.play().catch(() => {}); }
        if (vw)  vw.classList.remove('hidden');
    } else if (q.media_type === 'youtube' && q.youtube_url) {
        try {
            const url     = new URL(q.youtube_url);
            const videoId = url.searchParams.get('v') || url.pathname.split('/').pop();
            const start   = q.youtube_start || 0;
            const end     = q.youtube_end   || '';
            let src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&start=' + start;
            if (end) src += '&end=' + end;
            const ytf = document.getElementById('youtube-iframe');
            const ytw = document.getElementById('youtube-wrapper');
            if (ytf) ytf.src = src;
            if (ytw) ytw.classList.remove('hidden');
        } catch(e) {}
    }

    /* Timer */
    if (tl && sat) startTimerBar(tl, sat);
    else { stopTimerBar(); const bar = document.getElementById('timer-bar'); if (bar) bar.style.width = '0%'; }
}

/* ── Başlangıç ── */
if (INITIAL) {
    updateDisplay(INITIAL);
} else {
    updateDisplay({ mode: 'lobby', question: null, top_participants: [], time_limit: 0, started_at_ms: null });
}

/* ── Echo ── */
function waitForEcho(tries) {
    if (window.Echo) {
        window.Echo.channel('quiz.session.' + SESSION_CODE)
            .listen('.QuizStateUpdated', function(e) {
                updateDisplay(e);
            });
        return;
    }
    if (tries > 0) setTimeout(function() { waitForEcho(tries - 1); }, 150);
}
waitForEcho(80);
</script>
</body>
</html>
