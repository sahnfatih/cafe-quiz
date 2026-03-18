<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Sunum Ekranı · Cafe Quiz Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { cursor: none; overflow: hidden; }

        @keyframes timer-pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
        @keyframes option-enter {
            from { transform: translateY(20px) scale(.96); opacity: 0; }
            to   { transform: translateY(0)    scale(1);   opacity: 1; }
        }
        @keyframes score-slide {
            from { transform: translateX(-30px); opacity: 0; }
            to   { transform: translateX(0);     opacity: 1; }
        }
        @keyframes lobby-float {
            0%,100%{transform:translateY(0) rotate(-2deg)} 50%{transform:translateY(-14px) rotate(2deg)}
        }
        @keyframes bg-shift {
            0%,100%{background-position:0% 50%}
            50%{background-position:100% 50%}
        }
        .option-anim { animation: option-enter .4s ease-out both; }
        .score-anim  { animation: score-slide  .4s ease-out both; }
        .lobby-icon  { animation: lobby-float  4s ease-in-out infinite; }
        .timer-urgent-bar { animation: timer-pulse .4s ease-in-out infinite; }

        /* Seçenek label fontları projeksiyona göre büyük */
        #options-area > div { font-size: clamp(1rem, 2.2vw, 1.5rem); }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white">

@php
    $joinUrl = route('participant.join', $session->code);
    $initialQuestion = $currentQuestion ? [
        'session'  => [
            'id'                  => $session->id,
            'code'                => $session->code,
            'status'              => $session->status,
            'current_question_id' => $currentQuestion->id,
        ],
        'question' => [
            'id'            => $currentQuestion->id,
            'text'          => $currentQuestion->text,
            'option_a'      => $currentQuestion->option_a,
            'option_b'      => $currentQuestion->option_b,
            'option_c'      => $currentQuestion->option_c,
            'option_d'      => $currentQuestion->option_d,
            'media_type'    => $currentQuestion->media_type,
            'image_path'    => $currentQuestion->image_path,
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

{{-- ──────────── TIMER BAR (sabit üst) ──────────── --}}
<div id="timer-bar-wrap" class="fixed top-0 left-0 right-0 z-50 h-1.5 bg-slate-800/60">
    <div id="timer-bar" class="h-full transition-none"
         style="width:100%;background:linear-gradient(to right,#38bdf8,#a78bfa)"></div>
</div>

{{-- ──────────── ANA LAYOUT ──────────── --}}
<div class="h-screen w-screen flex flex-col pt-1.5">

    {{-- ── ÜST BAR ── --}}
    <div class="shrink-0 flex items-center justify-between px-8 py-3 border-b border-slate-800/60">
        <div id="status-line" class="text-slate-300 font-bold" style="font-size:clamp(1rem,2vw,1.5rem)">
            Hazırlanıyor…
        </div>
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-slate-800/60 border border-slate-700/60 px-4 py-1.5
                        flex items-center gap-2.5">
                <span class="text-slate-500 text-sm font-medium">Kod</span>
                <span class="font-mono font-black text-sky-300 tracking-[0.2em]"
                      style="font-size:clamp(1.1rem,2.2vw,1.8rem)">{{ $session->code }}</span>
            </div>
        </div>
    </div>

    {{-- ── İÇERİK ALANI ── --}}
    <div class="flex-1 flex overflow-hidden">

        {{-- ── SORU ALANI ── --}}
        <main id="question-area" class="flex-1 flex flex-col px-10 py-6 min-w-0 overflow-hidden">

            {{-- Soru metni --}}
            <div id="question-text"
                 class="text-white font-black leading-tight mb-6 flex-shrink-0"
                 style="font-size:clamp(1.6rem,3.8vw,3rem);min-height:5rem">
            </div>

            {{-- Medya alanı --}}
            <div id="media-area" class="mb-6 flex items-center justify-center max-h-64">
                <div id="image-wrapper" class="hidden">
                    <img id="question-image" src="" alt=""
                         class="max-h-60 rounded-2xl shadow-2xl shadow-black/60 object-contain">
                </div>
                <div id="youtube-wrapper"
                     class="hidden w-full aspect-video rounded-2xl overflow-hidden shadow-2xl shadow-black/60 max-h-64">
                    <iframe id="youtube-iframe" class="w-full h-full" src="" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            </div>

            {{-- Şıklar --}}
            <div id="options-area" class="grid grid-cols-2 gap-4 mt-auto">

                {{-- A --}}
                <div id="option-wrap-A"
                     class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60
                            px-5 py-4 flex items-center gap-4 transition-all duration-500"
                     style="animation-delay:.05s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-sky-500 flex items-center justify-center
                                 text-white font-black shadow-lg shadow-sky-900/50 text-xl">A</span>
                    <span id="opt-a" class="font-semibold text-white leading-snug flex-1"></span>
                </div>

                {{-- B --}}
                <div id="option-wrap-B"
                     class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60
                            px-5 py-4 flex items-center gap-4 transition-all duration-500"
                     style="animation-delay:.12s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-violet-500 flex items-center justify-center
                                 text-white font-black shadow-lg shadow-violet-900/50 text-xl">B</span>
                    <span id="opt-b" class="font-semibold text-white leading-snug flex-1"></span>
                </div>

                {{-- C --}}
                <div id="option-wrap-C"
                     class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60
                            px-5 py-4 flex items-center gap-4 transition-all duration-500"
                     style="animation-delay:.19s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-amber-500 flex items-center justify-center
                                 text-white font-black shadow-lg shadow-amber-900/50 text-xl">C</span>
                    <span id="opt-c" class="font-semibold text-white leading-snug flex-1"></span>
                </div>

                {{-- D --}}
                <div id="option-wrap-D"
                     class="option-anim rounded-2xl border border-slate-700/60 bg-slate-800/60
                            px-5 py-4 flex items-center gap-4 transition-all duration-500"
                     style="animation-delay:.26s">
                    <span class="shrink-0 h-12 w-12 rounded-xl bg-rose-500 flex items-center justify-center
                                 text-white font-black shadow-lg shadow-rose-900/50 text-xl">D</span>
                    <span id="opt-d" class="font-semibold text-white leading-snug flex-1"></span>
                </div>

            </div>

            {{-- Skor tablosu (sonuçlarda) --}}
            <section id="scoreboard"
                     class="hidden flex-1 flex flex-col justify-center">
                <h2 class="font-black mb-6 text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-amber-300"
                    style="font-size:clamp(1.8rem,4vw,3rem)">
                    🏆 Sonuçlar
                </h2>
                <ol id="scoreboard-list" class="space-y-3"></ol>
            </section>

        </main>

        {{-- ── QR KOD PANELİ (lobi) ── --}}
        <aside id="qr-panel"
               class="w-72 shrink-0 border-l border-slate-800/60 bg-slate-950/40
                      flex flex-col items-center justify-center gap-5 px-6 py-8">

            <div class="lobby-icon text-6xl">🎯</div>

            <div class="text-center space-y-1">
                <p class="font-black text-white" style="font-size:clamp(1rem,1.8vw,1.4rem)">Katılmak için tara!</p>
                <p class="text-slate-400 text-sm">Telefanınla QR kodu okut</p>
            </div>

            {{-- QR kodu --}}
            <div class="rounded-2xl border-2 border-slate-700 bg-white p-3 shadow-2xl shadow-black/60">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($joinUrl) }}"
                     alt="Katılım QR"
                     class="h-60 w-60 block rounded-lg">
            </div>

            {{-- Kod badge --}}
            <div class="rounded-2xl bg-sky-500/10 border border-sky-500/30 px-5 py-2.5 text-center">
                <p class="text-xs text-slate-500 mb-0.5">Kod</p>
                <p class="font-mono font-black text-sky-300 tracking-[0.3em] text-2xl">{{ $session->code }}</p>
            </div>

            <div class="text-center text-xs text-slate-600 break-all max-w-full px-2 leading-relaxed">
                {{ $joinUrl }}
            </div>
        </aside>

    </div>
</div>

<canvas id="confetti-canvas" class="fixed inset-0 pointer-events-none z-40"></canvas>

<script>
    const sessionCode    = @json($session->code);
    const initialPayload = @json($initialQuestion);

    /* ── Timer Bar ── */
    let _timerBarInterval = null;

    function startTimerBar(timeLimit, startedAtMs) {
        stopTimerBar();
        const bar = document.getElementById('timer-bar');
        if (!bar || !timeLimit || !startedAtMs) {
            if (bar) { bar.style.width = '100%'; bar.style.background = 'linear-gradient(to right,#38bdf8,#a78bfa)'; }
            return;
        }
        const tick = () => {
            const elapsed = (Date.now() - startedAtMs) / 1000;
            const rem     = Math.max(0, timeLimit - elapsed);
            const pct     = (rem / timeLimit) * 100;
            bar.style.width = pct + '%';
            if (rem <= 5)       { bar.style.background = '#f87171'; bar.classList.add('timer-urgent-bar'); }
            else if (rem <= 10) { bar.style.background = '#fb923c'; bar.classList.remove('timer-urgent-bar'); }
            else                { bar.style.background = 'linear-gradient(to right,#38bdf8,#a78bfa)'; bar.classList.remove('timer-urgent-bar'); }
            if (rem <= 0) stopTimerBar();
        };
        tick();
        _timerBarInterval = setInterval(tick, 150);
    }

    function stopTimerBar() {
        if (_timerBarInterval) { clearInterval(_timerBarInterval); _timerBarInterval = null; }
        const bar = document.getElementById('timer-bar');
        if (bar) { bar.classList.remove('timer-urgent-bar'); }
    }

    /* ── Option renk sıfırla ── */
    function resetOptionColors() {
        ['A','B','C','D'].forEach(opt => {
            const el = document.getElementById('option-wrap-' + opt);
            if (!el) return;
            el.className = 'rounded-2xl border border-slate-700/60 bg-slate-800/60 px-5 py-4 flex items-center gap-4 transition-all duration-500';
        });
    }

    /* ── Doğru cevabı vurgula ── */
    function revealCorrectOption(correctOpt) {
        ['A','B','C','D'].forEach(opt => {
            const el = document.getElementById('option-wrap-' + opt);
            if (!el) return;
            if (opt === correctOpt) {
                el.className = 'rounded-2xl border-2 border-emerald-400 bg-emerald-500/20 px-5 py-4 flex items-center gap-4 scale-105 transition-all duration-500 shadow-xl shadow-emerald-500/25';
            } else {
                el.className = 'rounded-2xl border border-slate-700/30 bg-slate-900/40 px-5 py-4 flex items-center gap-4 opacity-30 transition-all duration-500';
            }
        });
    }

    /* ── Ana güncelleme fonksiyonu ── */
    function updateQuestion(payload) {
        const q    = payload.question;
        const mode = payload.mode;
        const top  = payload.top_participants || [];
        const tl   = payload.time_limit   ?? 0;
        const sat  = payload.started_at_ms ?? null;

        const status         = document.getElementById('status-line');
        const textEl         = document.getElementById('question-text');
        const optA           = document.getElementById('opt-a');
        const optB           = document.getElementById('opt-b');
        const optC           = document.getElementById('opt-c');
        const optD           = document.getElementById('opt-d');
        const imgWrapper     = document.getElementById('image-wrapper');
        const img            = document.getElementById('question-image');
        const ytWrapper      = document.getElementById('youtube-wrapper');
        const ytIframe       = document.getElementById('youtube-iframe');
        const scoreboard     = document.getElementById('scoreboard');
        const scoreboardList = document.getElementById('scoreboard-list');
        const qrPanel        = document.getElementById('qr-panel');
        const optionsArea    = document.getElementById('options-area');

        /* ── Reveal ── */
        if (mode === 'reveal') {
            stopTimerBar();
            if (q && q.correct_option) {
                status.textContent = '💡 Doğru Cevap: ' + q.correct_option;
                revealCorrectOption(q.correct_option);
            }
            return;
        }

        /* ── Sonuçlar ── */
        if (mode === 'show_results' || mode === 'finish') {
            stopTimerBar();
            status.textContent = '🏆 Sonuçlar';
            resetOptionColors();
            if (optionsArea) optionsArea.classList.add('hidden');
            scoreboard.classList.remove('hidden');
            scoreboardList.innerHTML = '';
            const medals = ['🥇','🥈','🥉'];
            const bgCls  = [
                'bg-gradient-to-r from-yellow-500/20 to-amber-500/10 border-yellow-500/40',
                'bg-gradient-to-r from-slate-500/20 to-slate-400/10 border-slate-500/40',
                'bg-gradient-to-r from-orange-700/20 to-orange-600/10 border-orange-700/40',
            ];
            top.forEach((p, i) => {
                const li = document.createElement('li');
                li.className = `score-anim rounded-2xl border px-6 py-4 flex items-center justify-between ${bgCls[i] || 'bg-slate-800/40 border-slate-700/40'}`;
                li.style.animationDelay = (i * 0.12) + 's';
                li.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span style="font-size:clamp(1.4rem,3vw,2.2rem)">${medals[i] || (i+1+'.')}</span>
                        <span class="font-black text-white" style="font-size:clamp(1rem,2.2vw,1.5rem)">${p.name}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-black text-emerald-300" style="font-size:clamp(1.1rem,2.5vw,1.8rem)">${p.total_score} puan</span>
                        ${p.total_speed_bonus > 0 ? `<span class="block text-xs text-sky-400 font-medium">+${p.total_speed_bonus} hız bonusu</span>` : ''}
                    </div>`;
                scoreboardList.appendChild(li);
            });
            if (qrPanel) qrPanel.classList.add('hidden');
            launchConfetti();
            return;
        }

        /* ── Skor ve options sıfırla ── */
        scoreboard.classList.add('hidden');
        if (optionsArea) optionsArea.classList.remove('hidden');
        scoreboardList.innerHTML = '';
        resetOptionColors();

        /* ── Lobi (soru yok) ── */
        if (!q) {
            stopTimerBar();
            status.textContent = 'QR kodu tarayıp yarışmaya katılabilirsiniz…';
            textEl.textContent = '';
            optA.textContent = optB.textContent = optC.textContent = optD.textContent = '';
            imgWrapper.classList.add('hidden');
            ytWrapper.classList.add('hidden');
            ytIframe.src = '';
            if (qrPanel) qrPanel.classList.remove('hidden');
            return;
        }

        /* ── Yeni soru ── */
        if (qrPanel) qrPanel.classList.add('hidden');

        status.textContent  = `${q.points} puanlık soru`;
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
            try {
                const url     = new URL(q.youtube_url);
                const videoId = url.searchParams.get('v') || url.pathname.split('/').pop();
                const start   = q.youtube_start || 0;
                const end     = q.youtube_end   || '';
                let src = `https://www.youtube.com/embed/${videoId}?autoplay=1&start=${start}`;
                if (end) src += `&end=${end}`;
                ytIframe.src = src;
            } catch(e) {}
        }

        /* Timer bar başlat */
        if (tl && sat) {
            startTimerBar(tl, sat);
        } else {
            stopTimerBar();
            const bar = document.getElementById('timer-bar');
            if (bar) { bar.style.width = '0%'; }
        }
    }

    /* ── Konfeti ── */
    function launchConfetti() {
        const canvas = document.getElementById('confetti-canvas');
        const ctx    = canvas.getContext('2d');
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;

        const colors  = ['#22c55e','#eab308','#f97316','#38bdf8','#a855f7','#ec4899','#f43f5e'];
        const pieces  = Array.from({ length: 200 }, () => ({
            x:  Math.random() * canvas.width,
            y:  Math.random() * canvas.height - canvas.height,
            w:  4 + Math.random() * 8,
            h:  4 + Math.random() * 8,
            c:  colors[Math.floor(Math.random() * colors.length)],
            vx: (Math.random() - .5) * 3,
            vy: 2 + Math.random() * 4,
            r:  Math.random() * Math.PI * 2,
            vr: (Math.random() - .5) * .15,
        }));

        const end = Date.now() + 5000;
        (function frame() {
            if (Date.now() > end) { ctx.clearRect(0, 0, canvas.width, canvas.height); return; }
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach(p => {
                p.x += p.vx; p.y += p.vy; p.r += p.vr;
                if (p.y > canvas.height) { p.y = -10; p.x = Math.random() * canvas.width; }
                ctx.save();
                ctx.translate(p.x + p.w/2, p.y + p.h/2);
                ctx.rotate(p.r);
                ctx.fillStyle = p.c;
                ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
                ctx.restore();
            });
            requestAnimationFrame(frame);
        })();
    }

    /* ── Başlangıç ── */
    if (initialPayload) {
        updateQuestion(initialPayload);
    }

    /* ── Echo bağlantısı ── */
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
