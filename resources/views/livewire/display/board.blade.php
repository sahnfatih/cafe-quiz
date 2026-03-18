{{-- Livewire Board â€” tam Ã¶zellikli sunum ekranÄ± --}}
<div class="h-screen w-screen bg-gradient-to-br from-slate-950 via-slate-900 to-black text-white overflow-hidden relative" id="board-root">

    {{-- â•â• BEKLEME EKRANI â•â• --}}
    <div id="board-waiting"
         class="{{ (!$question || in_array($mode, ['pending','lobby'])) ? '' : 'hidden' }}
                absolute inset-0 flex flex-col items-center justify-center gap-8 z-20">

        <div class="relative">
            <div class="absolute inset-0 rounded-full bg-sky-500/20 animate-ping scale-125"></div>
            <div class="relative h-28 w-28 rounded-full bg-sky-500/10 border-2 border-sky-500/40
                        flex items-center justify-center text-6xl shadow-xl shadow-sky-500/10">ğŸ®</div>
        </div>

        <div class="text-center space-y-2">
            <h1 class="text-5xl font-black tracking-tight">Quiz baÅŸlamak Ã¼zere!</h1>
            <p class="text-slate-400 text-xl">KatÄ±lmak iÃ§in kodu gir</p>
        </div>

        <div class="text-center">
            <div class="text-slate-500 text-sm uppercase tracking-widest mb-3">KatÄ±lÄ±m Kodu</div>
            <div class="font-black text-9xl tracking-[0.25em] font-mono text-sky-400">{{ $session->code }}</div>
        </div>

        {{-- QR Kod --}}
        <div class="flex flex-col items-center gap-2">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode(route('participant.join', $session->code)) }}&bgcolor=0f172a&color=38bdf8&qzone=2"
                 alt="QR" class="rounded-xl border-2 border-sky-500/30 opacity-80">
            <p class="text-slate-500 text-sm">{{ route('participant.join', $session->code) }}</p>
        </div>

        <div class="flex gap-3">
            @foreach([0,1,2,3] as $i)
            <div class="h-3 w-3 rounded-full bg-sky-500/60"
                 style="animation: board-bounce 1.4s ease-in-out {{ $i * 0.2 }}s infinite alternate"></div>
            @endforeach
        </div>
    </div>

    {{-- â•â• AKTÄ°F SORU â•â• --}}
    <div id="board-question"
         class="{{ ($question && !in_array($mode, ['pending','lobby','show_results','finish','show_all_results'])) ? '' : 'hidden' }}
                absolute inset-0 flex flex-col px-10 py-6 gap-5">

        {{-- Ãœst bar --}}
        <div class="flex items-center justify-between gap-6 shrink-0">
            <div id="b-status" class="text-slate-400 text-xl font-medium">
                {{ ($question['points'] ?? 0) }} puanlÄ±k soru
            </div>

            <div id="b-timer-wrap"
                 class="{{ $timeLimit > 0 ? '' : 'hidden' }} relative h-20 w-20 shrink-0">
                <svg class="absolute inset-0 -rotate-90" viewBox="0 0 60 60">
                    <circle cx="30" cy="30" r="26" fill="none" stroke="#1e293b" stroke-width="5"/>
                    <circle id="b-timer-ring" cx="30" cy="30" r="26" fill="none"
                            stroke="#38bdf8" stroke-width="5"
                            stroke-dasharray="163.4" stroke-dashoffset="0"
                            stroke-linecap="round"
                            style="transition: stroke-dashoffset 0.9s linear, stroke 0.3s"/>
                </svg>
                <div id="b-timer-text"
                     class="absolute inset-0 flex items-center justify-center font-black text-2xl text-white">
                    {{ $timeLimit }}
                </div>
            </div>

            <div class="font-mono text-slate-600 text-sm">
                cafequiz.com Â· <span class="text-sky-500">{{ $session->code }}</span>
            </div>
        </div>

        {{-- Soru metni --}}
        <div id="b-question-text"
             class="text-4xl font-bold leading-snug shrink-0 transition-all duration-500">
            {{ $question['text'] ?? '' }}
        </div>

        {{-- Medya alanÄ± --}}
        <div class="flex-1 flex items-center justify-center min-h-0 overflow-hidden">
            @if($question && ($question['media_type'] ?? 'none') === 'image' && !empty($question['image_path']))
                <img id="b-media-img"
                     src="{{ asset('storage/'.($question['image_path'])) }}" alt=""
                     class="max-h-full max-w-full rounded-2xl shadow-lg object-contain" wire:key="img-{{ $question['id'] ?? 0 }}">
            @elseif($question && ($question['media_type'] ?? 'none') === 'video' && !empty($question['video_path']))
                <video id="b-media-video"
                       src="{{ asset('storage/'.($question['video_path'])) }}"
                       autoplay muted controls
                       class="max-h-full max-w-full rounded-2xl shadow-lg" wire:key="vid-{{ $question['id'] ?? 0 }}">
                </video>
            @elseif($question && ($question['media_type'] ?? 'none') === 'youtube' && $videoUrl)
                <div class="w-full h-full rounded-2xl overflow-hidden shadow-lg max-h-80 max-w-4xl" wire:key="yt-{{ $question['id'] ?? 0 }}">
                    <iframe class="w-full h-full" src="{{ $videoUrl }}" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            @else
                <div class="text-slate-800 text-8xl select-none" wire:key="no-media">â—†</div>
            @endif
        </div>

        {{-- ÅÄ±klar --}}
        <div class="grid grid-cols-2 gap-4 shrink-0" wire:ignore>
            @foreach(['A','B','C','D'] as $opt)
            @php
                $key  = 'option_'.strtolower($opt);
                $text = $question[$key] ?? null;
                $colors = ['A'=>'border-sky-700/60 bg-sky-900/30','B'=>'border-violet-700/60 bg-violet-900/30','C'=>'border-amber-700/60 bg-amber-900/30','D'=>'border-rose-700/60 bg-rose-900/30'];
                $labels = ['A'=>'text-sky-400','B'=>'text-violet-400','C'=>'text-amber-400','D'=>'text-rose-400'];
            @endphp
            <div id="b-opt-{{ $opt }}"
                 data-default-class="rounded-2xl border-2 {{ $colors[$opt] }} px-6 py-5 text-2xl font-semibold transition-all duration-500"
                 class="rounded-2xl border-2 {{ $colors[$opt] }} px-6 py-5 text-2xl font-semibold transition-all duration-500 {{ $text ? '' : 'invisible' }}">
                <span class="{{ $labels[$opt] }} mr-2 font-black">{{ $opt }})</span>
                <span id="b-opt-text-{{ $opt }}">{{ $text ?? '' }}</span>
            </div>
            @endforeach
        </div>

        {{-- Kilitli badge --}}
        <div id="b-locked-badge"
             class="{{ $answersLocked ? '' : 'hidden' }}
                    absolute bottom-4 left-1/2 -translate-x-1/2
                    rounded-full bg-rose-500/20 border border-rose-500/50
                    px-6 py-2 text-sm text-rose-200 font-medium">
            ğŸ”’ Cevaplar Kilitli
        </div>
    </div>

    {{-- â•â• SKOR (Top 3) â•â• --}}
    <div id="board-scoreboard"
         class="{{ in_array($mode, ['show_results','finish']) ? '' : 'hidden' }}
                absolute inset-0 flex flex-col items-center justify-center gap-8 px-12">

        <h2 class="text-6xl font-black text-center">ğŸ† SÄ±ralama</h2>

        <ol class="space-y-4 w-full max-w-2xl text-2xl" id="b-top-list">
            @forelse($topParticipants as $idx => $p)
            <li class="flex items-center justify-between rounded-2xl border
                        {{ $idx===0 ? 'border-yellow-500/50 bg-yellow-500/10' : ($idx===1 ? 'border-slate-500/50 bg-slate-500/10' : 'border-orange-600/50 bg-orange-600/10') }}
                        px-8 py-5">
                <span class="font-bold">{{ $idx===0 ? 'ğŸ¥‡' : ($idx===1 ? 'ğŸ¥ˆ' : 'ğŸ¥‰') }} {{ $p['name'] }}{{ !empty($p['team_name']) ? ' Â· '.$p['team_name'] : '' }}</span>
                <span class="{{ $idx===0 ? 'text-yellow-300' : ($idx===1 ? 'text-slate-300' : 'text-orange-300') }} font-black">{{ $p['total_score'] }} p</span>
            </li>
            @empty
            <li class="text-slate-400 text-center">HenÃ¼z katÄ±lÄ±mcÄ± yok.</li>
            @endforelse
        </ol>
    </div>

    {{-- â•â• TÃœM SONUÃ‡LAR â•â• --}}
    <div id="board-all-results"
         class="{{ $mode === 'show_all_results' ? '' : 'hidden' }}
                absolute inset-0 flex flex-col items-center px-8 py-6 overflow-hidden">

        <div id="board-confetti" class="pointer-events-none absolute inset-0 z-50 overflow-hidden"></div>

        <h2 class="text-5xl font-black mb-6 shrink-0">ğŸ‰ TÃ¼m SonuÃ§lar</h2>

        <div class="w-full max-w-3xl overflow-y-auto flex-1 space-y-3 pr-1" id="b-all-list">
            @forelse($topParticipants as $idx => $p)
            <div class="flex items-center justify-between rounded-2xl border border-slate-700/50 bg-slate-800/50 px-6 py-4 text-xl">
                <span class="font-bold text-slate-300">
                    #{{ $idx+1 }}
                    <span class="ml-3 text-white">{{ $p['name'] }}</span>
                    @if(!empty($p['team_name']))<span class="text-slate-500 text-base"> Â· {{ $p['team_name'] }}</span>@endif
                </span>
                <span class="font-black text-emerald-400">{{ $p['total_score'] }} p</span>
            </div>
            @empty
            <p class="text-slate-400 text-center">HenÃ¼z katÄ±lÄ±mcÄ± yok.</p>
            @endforelse
        </div>
    </div>

    <style>
        @keyframes board-bounce {
            from { transform: translateY(0); opacity: 0.4; }
            to   { transform: translateY(-12px); opacity: 1; }
        }
        @keyframes confetti-fall {
            0%   { transform: translateY(-20px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }
    </style>
</div>

@push('scripts')
<script>
(function() {
    /* â”€â”€ DOM â”€â”€ */
    const waiting     = document.getElementById('board-waiting');
    const qSection    = document.getElementById('board-question');
    const scoreBoard  = document.getElementById('board-scoreboard');
    const allResults  = document.getElementById('board-all-results');
    const statusEl    = document.getElementById('b-status');
    const qTextEl     = document.getElementById('b-question-text');
    const topListEl   = document.getElementById('b-top-list');
    const allListEl   = document.getElementById('b-all-list');
    const timerWrap   = document.getElementById('b-timer-wrap');
    const timerRing   = document.getElementById('b-timer-ring');
    const timerText   = document.getElementById('b-timer-text');
    const lockBadge   = document.getElementById('b-locked-badge');
    const confettiEl  = document.getElementById('board-confetti');

    /* â”€â”€ State â”€â”€ */
    let timerInterval  = null;
    let currentTimeLim = @json($timeLimit);
    let startedAtMs    = @json($startedAtMs);

    const MEDAL = ['ğŸ¥‡','ğŸ¥ˆ','ğŸ¥‰'];
    const OPT_DEFAULTS = {
        A: 'rounded-2xl border-2 border-sky-700/60 bg-sky-900/30 px-6 py-5 text-2xl font-semibold transition-all duration-500',
        B: 'rounded-2xl border-2 border-violet-700/60 bg-violet-900/30 px-6 py-5 text-2xl font-semibold transition-all duration-500',
        C: 'rounded-2xl border-2 border-amber-700/60 bg-amber-900/30 px-6 py-5 text-2xl font-semibold transition-all duration-500',
        D: 'rounded-2xl border-2 border-rose-700/60 bg-rose-900/30 px-6 py-5 text-2xl font-semibold transition-all duration-500',
    };

    /* â”€â”€ Timer â”€â”€ */
    function stopTimer() { if (timerInterval) { clearInterval(timerInterval); timerInterval = null; } }
    function startTimer(tl, satMs) {
        stopTimer();
        currentTimeLim = tl; startedAtMs = satMs;
        if (!tl || tl <= 0 || !satMs) { if (timerWrap) timerWrap.classList.add('hidden'); return; }
        if (timerWrap) timerWrap.classList.remove('hidden');
        const CIRC = 163.4;
        const tick = () => {
            const rem  = Math.max(0, tl - (Date.now() - satMs) / 1000);
            const secs = Math.ceil(rem);
            if (timerText) timerText.textContent = secs;
            if (timerRing) {
                timerRing.style.strokeDashoffset = CIRC * (1 - rem / tl);
                timerRing.style.stroke = rem <= 5 ? '#f87171' : rem <= 10 ? '#fb923c' : '#38bdf8';
            }
            if (timerText) timerText.style.color = rem <= 5 ? '#f87171' : rem <= 10 ? '#fb923c' : '#fff';
            if (rem <= 0) stopTimer();
        };
        tick();
        timerInterval = setInterval(tick, 250);
    }

    /* â”€â”€ ÅÄ±k renkleri â”€â”€ */
    function resetOptColors() {
        ['A','B','C','D'].forEach(o => {
            const el = document.getElementById('b-opt-' + o);
            if (el) el.className = OPT_DEFAULTS[o];
        });
    }
    function revealAnswer(correctOpt) {
        stopTimer();
        ['A','B','C','D'].forEach(o => {
            const el = document.getElementById('b-opt-' + o);
            if (!el) return;
            if (o === correctOpt) {
                el.className = 'rounded-2xl border-2 border-emerald-400 bg-emerald-500/25 px-6 py-5 text-2xl font-semibold transition-all duration-500 scale-105 shadow-lg shadow-emerald-500/30';
            } else {
                el.className = 'rounded-2xl border-2 border-slate-700/30 bg-slate-900/20 px-6 py-5 text-2xl font-semibold transition-all duration-500 opacity-25';
            }
        });
        if (statusEl) statusEl.textContent = 'âœ… DoÄŸru Cevap: ' + correctOpt;
    }
    function updateOptTexts(q) {
        ['A','B','C','D'].forEach(o => {
            const el   = document.getElementById('b-opt-text-' + o);
            const wrap = document.getElementById('b-opt-' + o);
            const txt  = q['option_' + o.toLowerCase()] || '';
            if (el) el.textContent = txt;
            if (wrap) { wrap.classList.toggle('invisible', !txt); if(txt) wrap.className = OPT_DEFAULTS[o]; }
        });
    }

    /* â”€â”€ Medya gÃ¼ncelle â”€â”€ */
    function updateMedia(q) {
        const imgEl  = document.getElementById('b-media-img');
        const vidEl  = document.getElementById('b-media-video');
        const ytEl   = document.querySelector('#board-question iframe');
        const noEl   = document.querySelector('[wire\\:key="no-media"]');

        const mt = q.media_type || 'none';
        if (imgEl)  imgEl.closest && imgEl.closest('.flex-1') && (imgEl.style.display = mt === 'image' && q.image_path ? '' : 'none');
        if (vidEl)  vidEl.style.display = mt === 'video' && q.video_path ? '' : 'none';
        if (ytEl)   ytEl.parentElement && (ytEl.parentElement.style.display = mt === 'youtube' && q.youtube_url ? '' : 'none');
        if (noEl)   noEl.style.display = (mt === 'none' || mt === '') ? '' : 'none';

        if (mt === 'image' && q.image_path && imgEl) {
            imgEl.src = STORAGE_BASE + '/' + q.image_path;
            imgEl.style.display = '';
        }
        if (mt === 'video' && q.video_path && vidEl) {
            vidEl.src = STORAGE_BASE + '/' + q.video_path;
            vidEl.style.display = '';
            vidEl.play && vidEl.play().catch(()=>{});
        }
    }
    const STORAGE_BASE = '{{ asset("storage") }}';

    /* â”€â”€ Ekranlar â”€â”€ */
    function showScreen(name) {
        if (waiting)    waiting.classList.toggle('hidden', name !== 'waiting');
        if (qSection)   qSection.classList.toggle('hidden', name !== 'question');
        if (scoreBoard) scoreBoard.classList.toggle('hidden', name !== 'scoreboard');
        if (allResults) allResults.classList.toggle('hidden', name !== 'all');
    }

    /* â”€â”€ SÄ±ralama listesi â”€â”€ */
    function renderTopList(top, el, limit) {
        if (!el || !top) return;
        const items = limit ? top.slice(0, limit) : top;
        el.innerHTML = items.map((p, i) => {
            const medal = MEDAL[i] || '#'+(i+1);
            const score_class = i===0 ? 'text-yellow-300' : i===1 ? 'text-slate-300' : i===2 ? 'text-orange-300' : 'text-emerald-400';
            const bg_class = i===0 ? 'border-yellow-500/50 bg-yellow-500/10' : i===1 ? 'border-slate-500/50 bg-slate-500/10' : i===2 ? 'border-orange-600/50 bg-orange-600/10' : 'border-slate-700/50 bg-slate-800/50';
            return `<li class="flex items-center justify-between rounded-2xl border ${bg_class} px-8 py-4 text-${limit?'2xl':'xl'}">
                        <span class="font-bold">${medal} ${p.name}${p.team_name?' Â· <span class="text-slate-400 text-base">'+p.team_name+'</span>':''}</span>
                        <span class="${score_class} font-black">${p.total_score} p</span>
                    </li>`;
        }).join('') || '<li class="text-slate-400 text-center">HenÃ¼z katÄ±lÄ±mcÄ± yok.</li>';
    }

    /* â”€â”€ Konfeti â”€â”€ */
    function launchConfetti() {
        if (!confettiEl) return;
        const colors = ['#38bdf8','#818cf8','#fb923c','#4ade80','#f472b6','#facc15'];
        confettiEl.innerHTML = '';
        for (let i = 0; i < 80; i++) {
            const p = document.createElement('div');
            const color = colors[Math.floor(Math.random() * colors.length)];
            const size  = Math.random() * 10 + 6;
            const left  = Math.random() * 100;
            const delay = Math.random() * 3;
            const dur   = Math.random() * 3 + 3;
            p.style.cssText = `position:absolute;left:${left}%;top:-20px;width:${size}px;height:${size}px;
                background:${color};border-radius:${Math.random()>0.5?'50%':'2px'};
                animation:confetti-fall ${dur}s ease-in ${delay}s forwards;`;
            confettiEl.appendChild(p);
        }
        setTimeout(() => { if(confettiEl) confettiEl.innerHTML=''; }, 8000);
    }

    /* â”€â”€ Ä°lk yÃ¼kleme â”€â”€ */
    @if($question && !in_array($mode, ['pending','lobby','show_results','finish','show_all_results']))
        if (startedAtMs) startTimer(currentTimeLim, startedAtMs);
    @elseif(in_array($mode, ['show_results','finish']))
        showScreen('scoreboard');
    @elseif($mode === 'show_all_results')
        showScreen('all');
        launchConfetti();
    @endif

    /* â”€â”€ Echo â”€â”€ */
    function setupBoardEcho() {
        if (!window.Echo) { setTimeout(setupBoardEcho, 500); return; }

        const componentId = @json($this->getId());

        window.Echo.channel('quiz.session.{{ $session->code }}')
            .listen('.QuizStateUpdated', (e) => {
                const mode = e.mode;
                const q    = e.question;
                const tl   = e.time_limit    ?? currentTimeLim;
                const sat  = e.started_at_ms ?? null;
                const top  = e.top_participants ?? [];

                /* Livewire state gÃ¼ncelle (Blade yeniden render iÃ§in) */
                const comp = window.Livewire?.find(componentId);
                if (comp) comp.call('handleQuizUpdate', e);

                /* Reveal */
                if (mode === 'reveal') {
                    if (q?.correct_option) revealAnswer(q.correct_option);
                    return;
                }

                /* Kilit badge */
                if (lockBadge) lockBadge.classList.toggle('hidden', !e.answers_locked);

                /* Lobby â€” bekleme ekranÄ±na dÃ¶n */
                if (mode === 'lobby' || (!q && !['show_results','finish','show_all_results'].includes(mode))) {
                    stopTimer();
                    showScreen('waiting');
                    return;
                }

                /* TÃ¼m sonuÃ§lar */
                if (mode === 'show_all_results') {
                    stopTimer();
                    showScreen('all');
                    if (allListEl) {
                        const allTop = top.length ? top : (e.all_participants ?? []);
                        const items = allTop.map((p,i) => {
                            const bg = i===0?'border-yellow-500/50 bg-yellow-500/10':i===1?'border-slate-500/50 bg-slate-500/10':i===2?'border-orange-600/50 bg-orange-600/10':'border-slate-700/50 bg-slate-800/50';
                            const sc = i===0?'text-yellow-300':i===1?'text-slate-300':i===2?'text-orange-300':'text-emerald-400';
                            const team = p.team_name ? ` Â· <span class="text-slate-500 text-base">${p.team_name}</span>` : '';
                            return `<div class="flex items-center justify-between rounded-2xl border ${bg} px-6 py-4 text-xl">
                                <span class="font-bold text-slate-300">#${i+1} <span class="text-white">${p.name}</span>${team}</span>
                                <span class="font-black ${sc}">${p.total_score} p</span>
                            </div>`;
                        }).join('') || '<p class="text-slate-400 text-center">HenÃ¼z katÄ±lÄ±mcÄ± yok.</p>';
                        allListEl.innerHTML = items;
                    }
                    setTimeout(launchConfetti, 300);
                    return;
                }

                /* Top 3 / BitiÅŸ */
                if (mode === 'show_results' || mode === 'finish') {
                    stopTimer();
                    showScreen('scoreboard');
                    if (topListEl) renderTopList(top, topListEl, 3);
                    return;
                }

                /* Yeni soru */
                if (q) {
                    showScreen('question');
                    if (qTextEl) qTextEl.textContent = q.text || '';
                    if (statusEl) statusEl.textContent = (q.points||0) + ' puanlÄ±k soru';
                    resetOptColors();
                    updateOptTexts(q);
                    updateMedia(q);
                    startTimer(tl, sat);
                }
            })
            .listen('.AnswersLocked', (e) => {
                if (lockBadge) lockBadge.classList.toggle('hidden', !e.locked);
            });
    }

    window.addEventListener('load', setupBoardEcho);
})();
</script>
@endpush
