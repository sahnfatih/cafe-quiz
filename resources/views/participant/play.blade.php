<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Cafe Quiz · Oyuncu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes bounce-dot   { 0%,100%{transform:translateY(0);opacity:.35} 50%{transform:translateY(-11px);opacity:1} }
        @keyframes countdown-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.18)} }
        @keyframes ping-ring    { 0%{transform:scale(1);opacity:.5} 100%{transform:scale(2.4);opacity:0} }
        @keyframes slide-up     { from{transform:translateY(16px);opacity:0} to{transform:translateY(0);opacity:1} }
        @keyframes shake        { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }
        @keyframes pop-in       { 0%{transform:scale(.88);opacity:0} 60%{transform:scale(1.04)} 100%{transform:scale(1);opacity:1} }
        @keyframes correct-glow { 0%{box-shadow:0 0 0 0 rgba(52,211,153,.7)} 70%{box-shadow:0 0 0 14px rgba(52,211,153,0)} 100%{box-shadow:0 0 0 0 rgba(52,211,153,0)} }

        .bounce-dot   { animation: bounce-dot 1.3s ease-in-out infinite; }
        .timer-urgent { animation: countdown-pulse .5s ease-in-out infinite; }
        .ping-ring    { animation: ping-ring 2s ease-out infinite; }
        .slide-up     { animation: slide-up .35s ease-out both; }
        .pop-in       { animation: pop-in .35s cubic-bezier(.22,.61,.36,1) both; }
        * { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="h-full bg-slate-950 text-white antialiased">

<div class="min-h-screen flex flex-col">

    {{-- ── Header ── --}}
    <header class="shrink-0 bg-slate-900/95 border-b border-slate-800/80 px-4 py-2.5
                   flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-sky-500 to-violet-500
                        flex items-center justify-center text-sm shrink-0">🎮</div>
            <p class="font-bold text-slate-300 text-sm leading-tight truncate max-w-[120px]">
                {{ $session->quiz->title ?? 'Quiz' }}
            </p>
        </div>
        <div class="flex items-center gap-2 min-w-0 justify-end">
            <div class="text-right min-w-0">
                <p class="text-sm font-bold text-white truncate leading-tight max-w-[150px]">{{ $participant->name }}</p>
                @if($participant->team_name)
                    <p class="text-[10px] text-violet-400 truncate leading-tight max-w-[150px]">{{ $participant->team_name }}</p>
                @endif
            </div>
            <div class="shrink-0 h-8 w-8 rounded-xl bg-gradient-to-br from-violet-500/20 to-sky-500/20
                        border border-violet-500/30 flex items-center justify-center text-sm font-bold">
                {{ mb_substr($participant->name, 0, 1) }}
            </div>
        </div>
    </header>

    {{-- ── İçerik ── --}}
    <div class="flex-1 flex flex-col px-4 pt-4 pb-3 min-h-0 gap-3">

        {{-- ══ BEKLEME EKRANI ══ --}}
        <div id="waiting-screen"
             class="{{ $currentQuestion ? 'hidden' : '' }} flex-1 flex flex-col items-center justify-center gap-6 text-center py-6">
            <div class="relative flex items-center justify-center mt-4">
                <div class="ping-ring absolute h-28 w-28 rounded-full bg-sky-500/20"></div>
                <div class="ping-ring absolute h-28 w-28 rounded-full bg-violet-500/15" style="animation-delay:.8s"></div>
                <div class="relative h-24 w-24 rounded-3xl bg-gradient-to-br from-sky-500/20 to-violet-500/20
                            border border-sky-500/25 shadow-xl shadow-sky-900/40
                            flex items-center justify-center text-5xl">🎮</div>
            </div>
            <div class="space-y-2 max-w-[260px]">
                <h2 class="text-xl font-black text-white">Oyun Başlamak Üzere!</h2>
                <p class="text-sm text-slate-400 leading-relaxed">Admin soruları başlattığında ekran otomatik güncellenir</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="bounce-dot h-3 w-3 rounded-full bg-sky-400"    style="animation-delay:.0s"></span>
                <span class="bounce-dot h-3 w-3 rounded-full bg-violet-400" style="animation-delay:.22s"></span>
                <span class="bounce-dot h-3 w-3 rounded-full bg-sky-400"    style="animation-delay:.44s"></span>
            </div>
            <div class="flex items-center gap-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-4 py-1.5 text-xs text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Bağlandınız · Bekleniyor
            </div>
        </div>

        {{-- ══ SORU EKRANI ══ --}}
        <div id="question-card"
             class="{{ $currentQuestion ? '' : 'hidden' }} relative flex-1 flex flex-col gap-3 overflow-hidden">

            {{-- Durum + Timer --}}
            <div class="flex items-center justify-between gap-3">
                <p id="status-line" class="text-sm text-slate-300 font-semibold flex-1 leading-snug">
                    {{ $currentQuestion ? $currentQuestion->points.' puanlık soru' : '' }}
                </p>
                <div id="timer-wrap"
                     class="{{ $session->time_limit > 0 ? '' : 'hidden' }} relative shrink-0"
                     style="height:52px;width:52px">
                    <svg class="absolute inset-0 -rotate-90 w-full h-full" viewBox="0 0 48 48">
                        <circle cx="24" cy="24" r="20" fill="none" stroke="#1e293b" stroke-width="4"/>
                        <circle id="timer-ring" cx="24" cy="24" r="20" fill="none" stroke="#38bdf8"
                                stroke-width="4" stroke-dasharray="125.7" stroke-dashoffset="0"
                                stroke-linecap="round"
                                style="transition: stroke-dashoffset 0.9s linear, stroke 0.3s"/>
                    </svg>
                    <div id="timer-text"
                         class="absolute inset-0 flex items-center justify-center font-black text-sm text-white">
                        {{ $session->time_limit }}
                    </div>
                </div>
            </div>

            {{-- Soru Metni --}}
            <div class="rounded-2xl border border-slate-700/60 bg-slate-900/80 px-4 py-4 min-h-[72px] flex items-center">
                <p id="question-text" class="text-[15px] font-bold leading-snug text-white w-full">
                    {{ $currentQuestion ? $currentQuestion->text : '' }}
                </p>
            </div>

            {{-- Şıklar --}}
            <div id="options-container" class="grid grid-cols-2 gap-2.5 flex-1">
                @foreach(['A'=>['sky','hover:border-sky-500/60 hover:bg-sky-500/5'], 'B'=>['violet','hover:border-violet-500/60 hover:bg-violet-500/5'], 'C'=>['amber','hover:border-amber-500/60 hover:bg-amber-500/5'], 'D'=>['rose','hover:border-rose-500/60 hover:bg-rose-500/5']] as $letter=>[$col,$hov])
                <button type="button" data-option="{{ $letter }}"
                        class="answer-btn group relative flex flex-col rounded-2xl
                               border border-slate-700/80 bg-slate-900/90
                               p-3 text-left gap-2 {{ $hov }}
                               disabled:opacity-40 disabled:cursor-not-allowed
                               active:scale-[.97] transition-all duration-150 min-h-[84px]">
                    <span class="shrink-0 inline-flex items-center justify-center h-7 w-7 rounded-xl
                                 bg-{{ $col }}-500 text-white text-[11px] font-black shadow-md
                                 group-hover:scale-110 transition-transform">{{ $letter }}</span>
                    <span class="option-label text-[13px] text-slate-200 leading-snug flex-1 w-full" data-for="{{ $letter }}">
                        {{ $currentQuestion ? $currentQuestion->{'option_'.strtolower($letter)} : '' }}
                    </span>
                </button>
                @endforeach
            </div>

            {{-- Cevap Alındı Overlay --}}
            <div id="waiting-overlay"
                 class="hidden absolute inset-0 rounded-2xl bg-slate-950/95 backdrop-blur-md
                        flex flex-col items-center justify-center gap-4 text-center px-6 z-20">
                <div class="relative">
                    <div class="h-16 w-16 rounded-full border-4 border-slate-700/50 border-t-sky-400 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xl">⏳</div>
                </div>
                <p class="text-base font-black text-sky-300">✅ Cevabın Alındı!</p>
                <p class="text-xs text-slate-500">Diğer oyuncular cevaplıyor…</p>
                <p id="score-feedback" class="hidden text-sm font-black text-emerald-300 pop-in"></p>
            </div>

            {{-- Süre Doldu Overlay --}}
            <div id="timeout-overlay"
                 class="hidden absolute inset-0 rounded-2xl bg-slate-950/95 backdrop-blur-md
                        flex flex-col items-center justify-center gap-4 text-center px-6 z-20">
                <div class="text-5xl animate-bounce">⏰</div>
                <p class="text-base font-black text-rose-300">Süre Doldu!</p>
                <p class="text-xs text-slate-500">Cevaplar kilitlendi.</p>
            </div>

        </div>

        {{-- ══ BİTİŞ EKRANI ══ --}}
        <div id="finished-screen"
             class="hidden flex-1 flex flex-col items-center justify-center gap-5 text-center py-6">
            <div class="text-6xl pop-in">🎉</div>
            <div class="space-y-2">
                <h2 class="text-xl font-black text-white">Yarışma Bitti!</h2>
                <p id="finished-msg" class="text-sm text-slate-400 leading-relaxed">
                    Sonuçlar büyük ekranda gösteriliyor.
                </p>
            </div>
            <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 px-6 py-3 text-sm font-semibold text-emerald-300">
                🏆 Teşekkürler!
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <footer class="shrink-0 pb-4 px-4">
        <p id="echo-status" class="text-center text-[10px] text-slate-700">Bağlanıyor…</p>
    </footer>

</div>

@php
    $initialQuestion = $currentQuestion ? [
        'id'        => $currentQuestion->id,
        'text'      => $currentQuestion->text,
        'points'    => $currentQuestion->points,
        'option_a'  => $currentQuestion->option_a,
        'option_b'  => $currentQuestion->option_b,
        'option_c'  => $currentQuestion->option_c,
        'option_d'  => $currentQuestion->option_d,
        'media_type'=> $currentQuestion->media_type,
        'image_path'=> $currentQuestion->image_path,
        'video_path'=> $currentQuestion->video_path ?? null,
    ] : null;
@endphp

<script>
/* ── Sabitler ── */
const SESSION_CODE      = @json($session->code);
const MY_PARTICIPANT_ID = @json($participant->id);
const ANSWER_URL        = @json(route('participant.answer', [$session->code, $participant]));
const CSRF_TOKEN        = document.querySelector('meta[name="csrf-token"]').content;
const INITIAL_QUESTION  = @json($initialQuestion);
const JOIN_URL          = @json(route('participant.join', $session->code));
const TIME_LIMIT        = @json($session->time_limit);
const STARTED_AT_MS     = @json($session->current_question_started_at
    ? $session->current_question_started_at->getTimestampMs() : null);

/* ── DOM ── */
const statusEl         = document.getElementById('status-line');
const questionTextEl   = document.getElementById('question-text');
const waitingOverlay   = document.getElementById('waiting-overlay');
const timeoutOverlay   = document.getElementById('timeout-overlay');
const optionsContainer = document.getElementById('options-container');
const echoStatusEl     = document.getElementById('echo-status');
const questionCard     = document.getElementById('question-card');
const waitingScreen    = document.getElementById('waiting-screen');
const finishedScreen   = document.getElementById('finished-screen');
const finishedMsg      = document.getElementById('finished-msg');
const timerWrap        = document.getElementById('timer-wrap');
const timerRing        = document.getElementById('timer-ring');
const timerText        = document.getElementById('timer-text');
const scoreFeedback    = document.getElementById('score-feedback');

/* ── Durum ── */
let currentQuestionId  = INITIAL_QUESTION?.id  ?? null;
let hasAnsweredCurrent = false;
let mySelectedOption   = null;
let timerInterval      = null;
let currentTimeLimit   = TIME_LIMIT;
let currentStartedAtMs = STARTED_AT_MS;

/* ══ Audio ══ */
const AudioCtx = window.AudioContext || window.webkitAudioContext;
let audioCtx = null;
function getAudio() {
    if (!audioCtx) { try { audioCtx = new AudioCtx(); } catch(e) { return null; } }
    if (audioCtx.state === 'suspended') audioCtx.resume();
    return audioCtx;
}
function playTone(freq, type, dur, gain=0.3, delay=0) {
    const ctx=getAudio(); if(!ctx) return;
    const osc=ctx.createOscillator(); const g=ctx.createGain();
    osc.connect(g); g.connect(ctx.destination);
    osc.type=type; osc.frequency.value=freq;
    g.gain.setValueAtTime(gain, ctx.currentTime+delay);
    g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime+delay+dur);
    osc.start(ctx.currentTime+delay); osc.stop(ctx.currentTime+delay+dur+.05);
}
function playTick()    { playTone(880,'sine',.08,.2); }
function playUrgent()  { playTone(1200,'square',.06,.15); }
function playCorrect() { [523,659,784,1047].forEach((f,i)=>playTone(f,'sine',.2,.4,i*.1)); }
function playWrong()   { [400,300,200].forEach((f,i)=>playTone(f,'sawtooth',.15,.3,i*.1)); }
function playFinished(){ [523,659,784,1047,1319].forEach((f,i)=>playTone(f,'sine',.25,.35,i*.08)); }

/* ══ Timer ══ */
function stopTimer() { if(timerInterval){clearInterval(timerInterval);timerInterval=null;} }
function startTimer(tl, sat) {
    stopTimer();
    currentTimeLimit=tl; currentStartedAtMs=sat;
    if (!tl||tl<=0||!sat) { if(timerWrap) timerWrap.classList.add('hidden'); return; }
    if (timerWrap) timerWrap.classList.remove('hidden');
    const CIRC=125.7;
    const tick=()=>{
        const elapsed=(Date.now()-sat)/1000;
        const rem=Math.max(0,tl-elapsed);
        const secs=Math.ceil(rem);
        if(timerText) timerText.textContent=secs;
        if(timerRing){ timerRing.style.strokeDashoffset=CIRC*(1-rem/tl); timerRing.style.stroke=rem<=5?'#f87171':rem<=10?'#fb923c':'#38bdf8'; }
        if(timerText){ timerText.classList.toggle('timer-urgent',rem<=5); timerText.style.color=rem<=5?'#f87171':rem<=10?'#fb923c':'#fff'; }
        if(rem<=5&&rem>0&&Math.abs(rem-secs)<.2) playUrgent();
        else if(rem<=10&&rem>5&&Math.abs(rem-secs)<.2) playTick();
        if(rem<=0){ stopTimer(); if(!hasAnsweredCurrent) autoLockTimeout(); }
    };
    tick();
    timerInterval=setInterval(tick,250);
}
function autoLockTimeout() {
    if(hasAnsweredCurrent) return;
    hasAnsweredCurrent=true;
    setButtonsDisabled(true);
    waitingOverlay.classList.add('hidden');
    timeoutOverlay.classList.remove('hidden');
    optionsContainer.classList.remove('hidden');
    statusEl.textContent='⏰ Süre Doldu!';
}

/* ══ Yardımcılar ══ */
function setButtonsDisabled(d) { document.querySelectorAll('.answer-btn').forEach(b=>b.disabled=d); }
function clearAnswerHighlight() {
    document.querySelectorAll('.answer-btn').forEach(b=>{
        b.classList.remove('border-emerald-500','bg-emerald-500/10','border-rose-500','bg-rose-500/10','border-sky-500','bg-sky-500/10','opacity-40');
    });
}
function setOptionTexts(q) {
    const map={A:q.option_a,B:q.option_b,C:q.option_c,D:q.option_d};
    document.querySelectorAll('.option-label').forEach(el=>{ el.textContent=map[el.dataset.for]||''; });
}
/* ── Ekran geçişleri ── */
function showScreen(name) {
    waitingScreen.classList.add('hidden');
    questionCard.classList.add('hidden');
    finishedScreen.classList.add('hidden');
    if(name==='waiting')   waitingScreen.classList.remove('hidden');
    else if(name==='question') questionCard.classList.remove('hidden');
    else if(name==='finished') finishedScreen.classList.remove('hidden');
    stopTimer();
}

function showNewQuestion(q, tl, sat) {
    showScreen('question');
    statusEl.textContent       = q.points + ' puanlık soru';
    questionTextEl.textContent = q.text;
    setOptionTexts(q);
    clearAnswerHighlight();
    setButtonsDisabled(false);
    waitingOverlay.classList.add('hidden');
    timeoutOverlay.classList.add('hidden');
    if(scoreFeedback) scoreFeedback.classList.add('hidden');
    optionsContainer.classList.remove('hidden');
    startTimer(tl ?? currentTimeLimit, sat ?? currentStartedAtMs);
}

function showWaiting() {
    optionsContainer.classList.add('hidden');
    waitingOverlay.classList.remove('hidden');
    timeoutOverlay.classList.add('hidden');
}

/* ── İlk yükleme ── */
if (INITIAL_QUESTION) {
    showNewQuestion(INITIAL_QUESTION, TIME_LIMIT, STARTED_AT_MS);
} else {
    setButtonsDisabled(true);
}

/* ── Cevap butonları ── */
document.querySelectorAll('.answer-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!currentQuestionId || hasAnsweredCurrent) return;
        getAudio();
        const sel = btn.dataset.option;
        mySelectedOption = sel;
        clearAnswerHighlight();
        btn.classList.add('border-sky-500','bg-sky-500/10');
        setButtonsDisabled(true);
        hasAnsweredCurrent = true;
        stopTimer();
        showWaiting();
        try {
            const res = await fetch(ANSWER_URL, {
                method:'POST',
                headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({ selected_option:sel, client_sent_at:Date.now() }),
            });
            const data = await res.json().catch(()=>({}));
            if (data.ok===false) {
                hasAnsweredCurrent=false; mySelectedOption=null;
                clearAnswerHighlight(); setButtonsDisabled(false);
                waitingOverlay.classList.add('hidden');
                statusEl.textContent=data.message||'Cevap gönderilemedi.';
            }
        } catch(err) { console.error(err); }
    });
});

/* ══ Echo ══ */
window.addEventListener('load', () => {
    if (!window.Echo) { if(echoStatusEl) echoStatusEl.textContent='🔴 Bağlantı kurulamadı'; return; }
    if (echoStatusEl) { echoStatusEl.textContent='🟢 Canlı'; echoStatusEl.className='text-center text-[10px] text-emerald-600'; }

    window.Echo.channel('quiz.session.' + SESSION_CODE)

    /* Quiz durumu güncellendi */
    .listen('.QuizStateUpdated', (e) => {
        const q    = e.question;
        const mode = e.mode;
        const tl   = e.time_limit   ?? currentTimeLimit;
        const sat  = e.started_at_ms ?? null;

        /* Reveal */
        if (mode === 'reveal') {
            if (q?.correct_option) {
                const correct = q.correct_option;
                stopTimer();
                clearAnswerHighlight();
                document.querySelectorAll('.answer-btn').forEach(btn => {
                    const opt = btn.dataset.option;
                    if (opt===correct)               btn.classList.add('border-emerald-500','bg-emerald-500/10');
                    else if(opt===mySelectedOption)  btn.classList.add('border-rose-500','bg-rose-500/10','opacity-40');
                    else                             btn.classList.add('opacity-40');
                });
                if (mySelectedOption) {
                    const ok = mySelectedOption===correct;
                    statusEl.textContent = ok ? '✅ Doğru cevap verdiniz!' : `❌ Yanlış! Doğru: ${correct}`;
                    if(ok) {
                        playCorrect();
                        // Puan feedback göster
                        if(scoreFeedback && q.points) {
                            scoreFeedback.textContent = '🎯 +' + q.points + ' puan kazandınız!';
                            scoreFeedback.classList.remove('hidden');
                        }
                    } else {
                        playWrong();
                        if(scoreFeedback) scoreFeedback.classList.add('hidden');
                    }
                } else {
                    statusEl.textContent = `💡 Doğru cevap: ${correct}`;
                    if(scoreFeedback) scoreFeedback.classList.add('hidden');
                }
                waitingOverlay.classList.add('hidden');
                timeoutOverlay.classList.add('hidden');
                optionsContainer.classList.remove('hidden');
                showScreen('question');
            }
            return;
        }

        /* Lobby — bekleme ekranına dön */
        if (mode === 'lobby' || (!q && mode !== 'show_results' && mode !== 'finish' && mode !== 'show_all_results')) {
            currentQuestionId  = null;
            hasAnsweredCurrent = false;
            mySelectedOption   = null;
            showScreen('waiting');
            return;
        }

        /* Bitti */
        if (mode === 'show_results' || mode === 'finish' || mode === 'show_all_results') {
            playFinished();
            showScreen('finished');
            if(finishedMsg) finishedMsg.textContent = 'Sonuçlar büyük ekranda gösteriliyor. Tebrikler! 🎉';
            return;
        }

        /* Yeni soru */
        if (q && q.id !== currentQuestionId) {
            currentQuestionId  = q.id;
            hasAnsweredCurrent = false;
            mySelectedOption   = null;
        }
        showNewQuestion(q, tl, sat);
    })

    /* Cevaplar kilitlendi */
    .listen('.AnswersLocked', (e) => {
        if (e.locked && !hasAnsweredCurrent) {
            setButtonsDisabled(true); stopTimer();
            statusEl.textContent = '🔒 Cevaplar kilitlendi';
        } else if (!e.locked && !hasAnsweredCurrent) {
            setButtonsDisabled(false);
            statusEl.textContent = currentTimeLimit ? `${currentTimeLimit} saniyelik soru` : 'Cevabını seç';
            startTimer(currentTimeLimit, currentStartedAtMs);
        }
    })

    /* Atıldı */
    .listen('.ParticipantKicked', (e) => {
        if (e.participant_id === MY_PARTICIPANT_ID) {
            document.body.innerHTML=`
                <div class="min-h-screen flex items-center justify-center bg-slate-950 text-white px-6">
                    <div class="text-center space-y-5">
                        <div class="text-6xl">🚫</div>
                        <h1 class="text-xl font-black">Oturumdan Çıkarıldınız</h1>
                        <p class="text-slate-400 text-sm">Admin tarafından çıkarıldınız.</p>
                        <a href="${JOIN_URL}" class="inline-flex items-center gap-2 mt-4 rounded-2xl
                            bg-gradient-to-r from-sky-500 to-violet-500 px-6 py-3 text-sm font-bold text-white">
                            Tekrar Katıl 🚀
                        </a>
                    </div>
                </div>`;
        }
    });
});
</script>
</body>
</html>
