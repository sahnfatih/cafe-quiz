{{-- Livewire Board — saf JS yaklaşımı (Alpine çakışması yok) --}}
<div class="h-screen w-screen bg-gradient-to-br from-slate-950 via-slate-900 to-black text-white overflow-hidden relative">

    {{-- ══════════════════════════════════════════════════════════
         BEKLEME EKRANI
    ══════════════════════════════════════════════════════════ --}}
    <div id="board-waiting"
         class="{{ (!$question || $mode === 'pending') ? '' : 'hidden' }}
                absolute inset-0 flex flex-col items-center justify-center gap-10 z-20
                transition-opacity duration-700">

        <div class="relative">
            <div class="absolute inset-0 rounded-full bg-sky-500/20 animate-ping scale-125"></div>
            <div class="relative h-28 w-28 rounded-full bg-sky-500/10 border-2 border-sky-500/40
                        flex items-center justify-center text-6xl shadow-xl shadow-sky-500/10">
                🎮
            </div>
        </div>

        <div class="text-center space-y-2">
            <h1 class="text-5xl font-black tracking-tight">Quiz başlamak üzere!</h1>
            <p class="text-slate-400 text-xl">Katılmak için kodu gir</p>
        </div>

        <div class="text-center">
            <div class="text-slate-500 text-sm uppercase tracking-widest mb-2">Katılım Kodu</div>
            <div class="font-black text-8xl tracking-[0.25em] font-mono text-sky-400">{{ $session->code }}</div>
        </div>

        <div class="flex gap-3" style="--d:0">
            @foreach([0,1,2,3] as $i)
            <div class="h-3 w-3 rounded-full bg-sky-500/60"
                 style="animation: board-bounce 1.4s ease-in-out {{ $i * 0.2 }}s infinite alternate"></div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         AKTİF SORU EKRANI
    ══════════════════════════════════════════════════════════ --}}
    <div id="board-question"
         class="{{ $question && $mode !== 'pending' ? '' : 'hidden' }}
                absolute inset-0 flex flex-col px-12 py-8 gap-6">

        {{-- Üst bar: durum + geri sayım + kod --}}
        <div class="flex items-center justify-between gap-6 shrink-0">
            <div id="b-status" class="text-slate-400 text-xl">
                @if($mode === 'show_results' || $mode === 'finish')
                    🏆 Sonuçlar
                @elseif($question)
                    {{ $question['points'] ?? 0 }} puanlık soru
                @endif
            </div>

            {{-- Geri sayım dairesi --}}
            <div id="b-timer-wrap"
                 class="{{ $timeLimit > 0 ? '' : 'hidden' }}
                        relative h-16 w-16 shrink-0">
                <svg class="absolute inset-0 -rotate-90" viewBox="0 0 60 60">
                    <circle cx="30" cy="30" r="26" fill="none" stroke="#1e293b" stroke-width="5"/>
                    <circle id="b-timer-ring" cx="30" cy="30" r="26" fill="none"
                            stroke="#38bdf8" stroke-width="5"
                            stroke-dasharray="163.4" stroke-dashoffset="0"
                            stroke-linecap="round"
                            style="transition: stroke-dashoffset 0.9s linear, stroke 0.3s"/>
                </svg>
                <div id="b-timer-text"
                     class="absolute inset-0 flex items-center justify-center font-black text-xl text-white">
                    {{ $timeLimit }}
                </div>
            </div>

            <div class="font-mono text-sm text-slate-500">
                Kod: <span class="text-sky-300 text-2xl align-middle">{{ $session->code }}</span>
            </div>
        </div>

        {{-- Sonuçlar --}}
        <div id="b-scoreboard"
             class="{{ ($mode === 'show_results' || $mode === 'finish') ? '' : 'hidden' }}
                    flex-1 flex flex-col items-center justify-center">
            <div class="rounded-3xl border border-emerald-500/60 bg-emerald-600/10 px-12 py-8 min-w-[400px] max-w-xl w-full">
                <h2 class="text-3xl font-bold mb-6">🏆 İlk 3</h2>
                <ol class="space-y-4 text-xl" id="b-top-list">
                    @forelse($topParticipants as $idx => $p)
                    <li class="flex items-center justify-between">
                        <span>{{ $idx === 0 ? '🥇' : ($idx === 1 ? '🥈' : '🥉') }} {{ $p['name'] }}{{ !empty($p['team_name']) ? ' · '.$p['team_name'] : '' }}</span>
                        <span class="text-emerald-300 font-semibold">{{ $p['total_score'] }}p</span>
                    </li>
                    @empty
                    <li class="text-slate-400">Henüz katılımcı yok.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- Soru içeriği --}}
        <div id="b-content"
             class="{{ ($mode === 'show_results' || $mode === 'finish') ? 'hidden' : '' }}
                    flex-1 flex flex-col gap-6 min-h-0">

            <div id="b-question-text"
                 class="text-3xl font-bold leading-snug min-h-[3rem] shrink-0
                        transition-all duration-500">
                {{ $question['text'] ?? '' }}
            </div>

            {{-- Medya --}}
            <div class="flex-1 flex items-center justify-center min-h-0">
                @if($question && ($question['media_type'] ?? 'none') === 'image' && !empty($question['image_path']))
                    <img src="{{ asset('storage/'.($question['image_path'])) }}" alt=""
                         class="max-h-72 rounded-2xl shadow-lg object-contain" wire:key="img">
                @elseif($question && ($question['media_type'] ?? 'none') === 'youtube' && $videoUrl)
                    <div class="w-full aspect-video rounded-2xl overflow-hidden shadow-lg max-h-64" wire:key="yt">
                        <iframe class="w-full h-full" src="{{ $videoUrl }}" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </div>
                @else
                    <div class="text-slate-800 text-6xl select-none" wire:key="no-media">◆</div>
                @endif
            </div>

            {{-- Şıklar — wire:ignore: JS reveal için Livewire'ın dokunmasını engeller --}}
            <div class="grid grid-cols-2 gap-4 shrink-0" wire:ignore>
                @foreach(['A','B','C','D'] as $opt)
                @php
                    $key  = 'option_'.strtolower($opt);
                    $text = $question[$key] ?? null;
                    $cls  = ['A' => 'border-sky-700/60 bg-sky-900/30',
                              'B' => 'border-violet-700/60 bg-violet-900/30',
                              'C' => 'border-amber-700/60 bg-amber-900/30',
                              'D' => 'border-rose-700/60 bg-rose-900/30'];
                    $lbl  = ['A' => 'text-sky-400','B' => 'text-violet-400',
                              'C' => 'text-amber-400','D' => 'text-rose-400'];
                @endphp
                <div id="b-opt-{{ $opt }}"
                     data-default-class="rounded-2xl border-2 {{ $cls[$opt] }} px-6 py-4 text-xl font-semibold transition-all duration-500"
                     class="rounded-2xl border-2 {{ $cls[$opt] }} px-6 py-4 text-xl font-semibold transition-all duration-500
                            {{ $text ? '' : 'invisible' }}">
                    <span class="{{ $lbl[$opt] }} mr-2 text-2xl">{{ $opt }})</span>
                    <span id="b-opt-text-{{ $opt }}">{{ $text ?? '' }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Kilitli uyarısı --}}
        <div id="b-locked-badge"
             class="{{ $answersLocked ? '' : 'hidden' }}
                    absolute bottom-4 left-1/2 -translate-x-1/2 z-30
                    rounded-full bg-rose-500/20 border border-rose-500/50
                    px-6 py-2 text-sm text-rose-200 font-medium">
            🔒 Cevaplar Kilitli
        </div>
    </div>

    <style>
        @keyframes board-bounce {
            from { transform: translateY(0); opacity: 0.4; }
            to   { transform: translateY(-12px); opacity: 1; }
        }
    </style>
</div>

@push('scripts')
<script>
(function() {
    /* ── DOM ── */
    const waiting   = document.getElementById('board-waiting');
    const qSection  = document.getElementById('board-question');
    const statusEl  = document.getElementById('b-status');
    const qTextEl   = document.getElementById('b-question-text');
    const scoreEl   = document.getElementById('b-scoreboard');
    const contentEl = document.getElementById('b-content');
    const topListEl = document.getElementById('b-top-list');
    const timerWrap = document.getElementById('b-timer-wrap');
    const timerRing = document.getElementById('b-timer-ring');
    const timerText = document.getElementById('b-timer-text');
    const lockBadge = document.getElementById('b-locked-badge');

    /* ── State ── */
    let timerInterval  = null;
    let currentTimeLim = @json($timeLimit);
    let startedAtMs    = @json($startedAtMs);

    const OPT_DEFAULTS = {
        A: 'rounded-2xl border-2 border-sky-700/60 bg-sky-900/30 px-6 py-4 text-xl font-semibold transition-all duration-500',
        B: 'rounded-2xl border-2 border-violet-700/60 bg-violet-900/30 px-6 py-4 text-xl font-semibold transition-all duration-500',
        C: 'rounded-2xl border-2 border-amber-700/60 bg-amber-900/30 px-6 py-4 text-xl font-semibold transition-all duration-500',
        D: 'rounded-2xl border-2 border-rose-700/60 bg-rose-900/30 px-6 py-4 text-xl font-semibold transition-all duration-500',
    };

    /* ── Geri sayım ── */
    function stopTimer() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
    }

    function startTimer(tl, satMs) {
        stopTimer();
        currentTimeLim = tl;
        startedAtMs    = satMs;
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

    /* ── Şık renkleri ── */
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
                el.className = 'rounded-2xl border-2 border-emerald-400 bg-emerald-500/25 px-6 py-4 text-xl font-semibold transition-all duration-500 scale-105 shadow-lg shadow-emerald-500/30';
            } else {
                el.className = 'rounded-2xl border-2 border-slate-700/30 bg-slate-900/20 px-6 py-4 text-xl font-semibold transition-all duration-500 opacity-25';
            }
        });
        if (statusEl) statusEl.textContent = '💡 Doğru Cevap: ' + correctOpt;
    }

    /* ── Şık metinlerini güncelle ── */
    function updateOptTexts(q) {
        ['A','B','C','D'].forEach(o => {
            const el   = document.getElementById('b-opt-text-' + o);
            const wrap = document.getElementById('b-opt-' + o);
            const txt  = q['option_' + o.toLowerCase()] || '';
            if (el) el.textContent = txt;
            if (wrap) wrap.classList.toggle('invisible', !txt);
        });
    }

    /* ── Soru göster ── */
    function showQuestion(q, mode, tl, satMs) {
        if (!q) { showWaiting(); return; }
        if (waiting)   waiting.classList.add('hidden');
        if (qSection)  qSection.classList.remove('hidden');

        const isResults = mode === 'show_results' || mode === 'finish';
        if (scoreEl)  scoreEl.classList.toggle('hidden', !isResults);
        if (contentEl) contentEl.classList.toggle('hidden', isResults);

        if (isResults) {
            stopTimer();
            if (timerWrap) timerWrap.classList.add('hidden');
            if (statusEl) statusEl.textContent = '🏆 Sonuçlar';
        } else {
            resetOptColors();
            updateOptTexts(q);
            if (qTextEl) qTextEl.textContent = q.text || '';
            if (statusEl) statusEl.textContent = (q.points || 0) + ' puanlık soru';
            startTimer(tl, satMs);
        }
    }

    function showWaiting() {
        if (waiting)  waiting.classList.remove('hidden');
        if (qSection) qSection.classList.add('hidden');
        stopTimer();
    }

    /* ── İlk yükleme ── */
    @if($question && $mode !== 'pending')
    startTimer(currentTimeLim, startedAtMs);
    @endif

    /* ── Echo ── */
    window.addEventListener('load', function () {
        if (!window.Echo) return;

        const componentId = @json($this->getId());

        window.Echo.channel('quiz.session.{{ $session->code }}')
            .listen('.QuizStateUpdated', (e) => {
                /* Livewire state'ini güncelle (skor gösterimi için) */
                const comp = window.Livewire?.find(componentId);
                if (comp) comp.call('handleQuizUpdate', e);

                const mode = e.mode;
                const q    = e.question;
                const tl   = e.time_limit     ?? currentTimeLim;
                const sat  = e.started_at_ms  ?? null;

                /* Reveal */
                if (mode === 'reveal') {
                    if (q?.correct_option) revealAnswer(q.correct_option);
                    return;
                }

                /* Kilitli badge */
                const locked = e.answers_locked ?? false;
                if (lockBadge) lockBadge.classList.toggle('hidden', !locked);

                /* Bitiş */
                if (mode === 'show_results' || mode === 'finish') {
                    stopTimer();
                    if (timerWrap) timerWrap.classList.add('hidden');
                    setTimeout(() => {
                        /* Livewire re-render'ı bekle sonra scoreboard'u göster */
                        if (scoreEl)  scoreEl.classList.remove('hidden');
                        if (contentEl) contentEl.classList.add('hidden');
                        if (statusEl) statusEl.textContent = '🏆 Sonuçlar';
                        if (qSection) qSection.classList.remove('hidden');
                        if (waiting)  waiting.classList.add('hidden');
                    }, 300);
                    return;
                }

                /* Yeni soru ya da bekle */
                if (!q) { showWaiting(); return; }
                showQuestion(q, mode, tl, sat);
            })
            .listen('.AnswersLocked', (e) => {
                if (lockBadge) lockBadge.classList.toggle('hidden', !e.locked);
            });
    });
})();
</script>
@endpush
