<!DOCTYPE html>
<html lang="tr" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <title>Cafe Quiz Pro · Oyuncu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes bounce-dot {
            0%, 100% { transform: translateY(0);    opacity: 0.4; }
            50%       { transform: translateY(-8px); opacity: 1; }
        }
        @keyframes countdown-pulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.12); }
        }
        .bounce-dot { animation: bounce-dot 1.2s ease-in-out infinite; }
        .timer-urgent { animation: countdown-pulse 0.6s ease-in-out infinite; }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-50">

<div class="min-h-screen flex items-center justify-center px-4 py-6">
    <div class="w-full max-w-md space-y-4">

        {{-- Header --}}
        <header class="text-center">
            <p class="text-xs text-slate-400 mb-0.5">
                Kod: <span class="font-mono text-sky-400">{{ $session->code }}</span>
            </p>
            <h1 class="text-lg font-semibold">{{ $participant->name }}</h1>
            @if($participant->team_name)
            <p class="text-[11px] text-slate-500 mt-0.5">Takım: <span class="text-sky-300">{{ $participant->team_name }}</span></p>
            @endif
        </header>

        {{-- ─ BEKLEME EKRANI ─ --}}
        <div id="waiting-screen" class="{{ $currentQuestion ? 'hidden' : '' }} rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center space-y-5">
            <div class="relative inline-block">
                <div class="absolute inset-0 rounded-full bg-sky-500/20 animate-ping"></div>
                <div class="relative h-16 w-16 rounded-full bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-3xl">
                    🎮
                </div>
            </div>
            <div class="space-y-1">
                <p class="text-base font-semibold">Quiz başlamak üzere!</p>
                <p class="text-xs text-slate-400">Sorular başladığında burada otomatik görünecek</p>
            </div>
            <div class="flex justify-center gap-2">
                <span class="bounce-dot h-2.5 w-2.5 rounded-full bg-sky-500/60" style="animation-delay:0s"></span>
                <span class="bounce-dot h-2.5 w-2.5 rounded-full bg-sky-500/60" style="animation-delay:0.2s"></span>
                <span class="bounce-dot h-2.5 w-2.5 rounded-full bg-sky-500/60" style="animation-delay:0.4s"></span>
            </div>
        </div>

        {{-- ─ SORU EKRANI ─ --}}
        <main id="question-card"
              class="{{ $currentQuestion ? '' : 'hidden' }} relative rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-4 overflow-hidden transition-all duration-500">

            {{-- Üst: Durum + Geri Sayım --}}
            <div class="flex items-center justify-between gap-3">
                <p id="status-line" class="text-sm text-slate-400 flex-1">
                    {{ $currentQuestion ? $currentQuestion->points.' puanlık soru' : '' }}
                </p>

                {{-- Geri sayım --}}
                <div id="timer-wrap" class="{{ $session->time_limit > 0 ? '' : 'hidden' }} relative h-12 w-12 shrink-0">
                    <svg class="absolute inset-0 -rotate-90" viewBox="0 0 48 48">
                        <circle cx="24" cy="24" r="20" fill="none" stroke="#1e293b" stroke-width="4"/>
                        <circle id="timer-ring" cx="24" cy="24" r="20" fill="none" stroke="#38bdf8" stroke-width="4"
                                stroke-dasharray="125.7" stroke-dashoffset="0" stroke-linecap="round"
                                style="transition: stroke-dashoffset 0.9s linear, stroke 0.3s"/>
                    </svg>
                    <div id="timer-text"
                         class="absolute inset-0 flex items-center justify-center font-black text-sm text-white">
                        {{ $session->time_limit }}
                    </div>
                </div>
            </div>

            {{-- Soru metni --}}
            <p id="question-text" class="text-base font-semibold min-h-[2.5rem]">
                {{ $currentQuestion ? $currentQuestion->text : '' }}
            </p>

            {{-- Şıklar --}}
            <div id="options-container" class="grid grid-cols-2 gap-3">
                @foreach(['A','B','C','D'] as $opt)
                <button type="button" data-option="{{ $opt }}"
                        class="answer-btn rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-3 text-sm font-semibold
                               hover:border-sky-500 disabled:opacity-40 disabled:cursor-not-allowed text-left
                               transition-all duration-300">
                    <span class="text-sky-400">{{ $opt }})</span>
                    <span class="option-label ml-1" data-for="{{ $opt }}">
                        {{ $currentQuestion ? $currentQuestion->{'option_'.strtolower($opt)} : '' }}
                    </span>
                </button>
                @endforeach
            </div>

            {{-- Kilitli overlay --}}
            <div id="waiting-overlay"
                 class="{{ $currentQuestion && false ? '' : 'hidden' }} absolute inset-0 rounded-2xl bg-slate-950/90 backdrop-blur-sm
                        flex flex-col items-center justify-center text-center px-6 z-10">
                <div class="mb-3 h-10 w-10 rounded-full border-2 border-slate-700 border-t-sky-400 animate-spin"></div>
                <div class="text-sm font-semibold text-slate-100 mb-1">✅ Cevabın kilitlendi</div>
                <p class="text-xs text-slate-400">Diğer oyuncular cevaplıyor…<br>Sonraki soruda otomatik güncellenir.</p>
            </div>

            {{-- Süre doldu overlay --}}
            <div id="timeout-overlay"
                 class="hidden absolute inset-0 rounded-2xl bg-slate-950/90 backdrop-blur-sm
                        flex flex-col items-center justify-center text-center px-6 z-10">
                <div class="text-4xl mb-3">⏰</div>
                <div class="text-sm font-semibold text-rose-300 mb-1">Süre Doldu!</div>
                <p class="text-xs text-slate-400">Cevaplar kilitlendi.</p>
            </div>

        </main>

        {{-- Echo bağlantı göstergesi --}}
        <p id="echo-status" class="text-center text-[10px] text-slate-600">Bağlanıyor…</p>

    </div>
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
    ] : null;
@endphp

<script>
/* ══════════════════════════════════════════════════════════
   Sabitler
══════════════════════════════════════════════════════════ */
const SESSION_CODE     = @json($session->code);
const MY_PARTICIPANT_ID= @json($participant->id);
const ANSWER_URL       = @json(route('participant.answer', [$session->code, $participant]));
const CSRF_TOKEN       = document.querySelector('meta[name="csrf-token"]').content;
const INITIAL_QUESTION = @json($initialQuestion);
const JOIN_URL         = @json(route('participant.join', $session->code));
const TIME_LIMIT       = @json($session->time_limit);      // saniye
const STARTED_AT_MS    = @json($session->current_question_started_at
    ? $session->current_question_started_at->getTimestampMs()
    : null);

/* ══════════════════════════════════════════════════════════
   DOM
══════════════════════════════════════════════════════════ */
const statusEl          = document.getElementById('status-line');
const questionTextEl    = document.getElementById('question-text');
const waitingOverlay    = document.getElementById('waiting-overlay');
const timeoutOverlay    = document.getElementById('timeout-overlay');
const optionsContainer  = document.getElementById('options-container');
const echoStatusEl      = document.getElementById('echo-status');
const questionCard      = document.getElementById('question-card');
const waitingScreen     = document.getElementById('waiting-screen');
const timerWrap         = document.getElementById('timer-wrap');
const timerRing         = document.getElementById('timer-ring');
const timerText         = document.getElementById('timer-text');

/* ══════════════════════════════════════════════════════════
   Durum değişkenleri
══════════════════════════════════════════════════════════ */
let currentQuestionId  = INITIAL_QUESTION?.id  ?? null;
let hasAnsweredCurrent = false;
let mySelectedOption   = null;
let timerInterval      = null;
let currentTimeLimit   = TIME_LIMIT;
let currentStartedAtMs = STARTED_AT_MS;

/* ══════════════════════════════════════════════════════════
   🔊 Ses Efektleri — Web Audio API
══════════════════════════════════════════════════════════ */
const AudioCtx = window.AudioContext || window.webkitAudioContext;
let audioCtx = null;

function getAudio() {
    if (!audioCtx) { try { audioCtx = new AudioCtx(); } catch(e) { return null; } }
    if (audioCtx.state === 'suspended') audioCtx.resume();
    return audioCtx;
}

function playTone(freq, type, duration, gain = 0.3, startDelay = 0) {
    const ctx = getAudio(); if (!ctx) return;
    const osc = ctx.createOscillator();
    const g   = ctx.createGain();
    osc.connect(g); g.connect(ctx.destination);
    osc.type      = type;
    osc.frequency.value = freq;
    g.gain.setValueAtTime(gain, ctx.currentTime + startDelay);
    g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + startDelay + duration);
    osc.start(ctx.currentTime + startDelay);
    osc.stop(ctx.currentTime + startDelay + duration + 0.05);
}

function playTick()    { playTone(880, 'sine', 0.08, 0.2); }
function playUrgent()  { playTone(1200, 'square', 0.06, 0.15); }
function playCorrect() {
    [523, 659, 784, 1047].forEach((f, i) => playTone(f, 'sine', 0.2, 0.4, i * 0.1));
}
function playWrong()   {
    [400, 300, 200].forEach((f, i) => playTone(f, 'sawtooth', 0.15, 0.3, i * 0.1));
}
function playFinished() {
    [523,659,784,1047,1319].forEach((f, i) => playTone(f, 'sine', 0.25, 0.35, i * 0.08));
}

/* ══════════════════════════════════════════════════════════
   ⏱ Geri Sayım
══════════════════════════════════════════════════════════ */
function stopTimer() {
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
}

function startTimer(timeLimit, startedAtMs) {
    stopTimer();
    currentTimeLimit   = timeLimit;
    currentStartedAtMs = startedAtMs;

    if (!timeLimit || timeLimit <= 0 || !startedAtMs) {
        if (timerWrap) timerWrap.classList.add('hidden');
        return;
    }
    if (timerWrap) timerWrap.classList.remove('hidden');

    const CIRCUMFERENCE = 125.7; // 2π × 20

    const tick = () => {
        const elapsed = (Date.now() - startedAtMs) / 1000;
        const rem     = Math.max(0, timeLimit - elapsed);
        const secs    = Math.ceil(rem);

        if (timerText) timerText.textContent = secs;
        if (timerRing) {
            timerRing.style.strokeDashoffset = CIRCUMFERENCE * (1 - rem / timeLimit);
            timerRing.style.stroke = rem <= 5 ? '#f87171' : rem <= 10 ? '#fb923c' : '#38bdf8';
        }
        if (timerText) {
            timerText.classList.toggle('timer-urgent', rem <= 5);
            timerText.style.color = rem <= 5 ? '#f87171' : rem <= 10 ? '#fb923c' : '#fff';
        }

        /* Bip sesi */
        if (rem <= 5 && rem > 0 && Math.abs(rem - secs) < 0.2) playUrgent();
        else if (rem <= 10 && rem > 5 && Math.abs(rem - secs) < 0.2) playTick();

        if (rem <= 0) {
            stopTimer();
            if (!hasAnsweredCurrent) autoLockTimeout();
        }
    };

    tick();
    timerInterval = setInterval(tick, 250);
}

function autoLockTimeout() {
    if (hasAnsweredCurrent) return;
    hasAnsweredCurrent = true;
    setButtonsDisabled(true);
    waitingOverlay.classList.add('hidden');
    timeoutOverlay.classList.remove('hidden');
    optionsContainer.classList.remove('hidden');
    statusEl.textContent = '⏰ Süre Doldu!';
}

/* ══════════════════════════════════════════════════════════
   Yardımcı fonksiyonlar
══════════════════════════════════════════════════════════ */
function setButtonsDisabled(disabled) {
    document.querySelectorAll('.answer-btn').forEach(btn => btn.disabled = disabled);
}

function clearAnswerHighlight() {
    document.querySelectorAll('.answer-btn').forEach(btn => {
        btn.classList.remove(
            'border-emerald-500','bg-emerald-500/10',
            'border-rose-500','bg-rose-500/10',
            'border-sky-500','bg-sky-500/10',
            'opacity-40'
        );
    });
}

function setOptionTexts(q) {
    const map = { A: q.option_a, B: q.option_b, C: q.option_c, D: q.option_d };
    document.querySelectorAll('.option-label').forEach(el => {
        el.textContent = map[el.dataset.for] || '';
    });
}

function showWaitingScreen() {
    questionCard.classList.add('hidden');
    waitingScreen.classList.remove('hidden');
    stopTimer();
}

function showQuestionCard() {
    waitingScreen.classList.add('hidden');
    questionCard.classList.remove('hidden');
}

function showNewQuestion(q, timeLimit, startedAtMs) {
    showQuestionCard();
    statusEl.textContent       = q.points + ' puanlık soru';
    questionTextEl.textContent = q.text;
    setOptionTexts(q);
    clearAnswerHighlight();
    setButtonsDisabled(false);
    waitingOverlay.classList.add('hidden');
    timeoutOverlay.classList.add('hidden');
    optionsContainer.classList.remove('hidden');
    startTimer(timeLimit ?? currentTimeLimit, startedAtMs ?? currentStartedAtMs);
}

function showWaiting() {
    optionsContainer.classList.add('hidden');
    waitingOverlay.classList.remove('hidden');
    timeoutOverlay.classList.add('hidden');
}

function showFinished(msg) {
    showQuestionCard();
    stopTimer();
    statusEl.textContent       = msg;
    questionTextEl.textContent = '';
    setButtonsDisabled(true);
    clearAnswerHighlight();
    waitingOverlay.classList.add('hidden');
    timeoutOverlay.classList.add('hidden');
    optionsContainer.classList.remove('hidden');
    if (timerWrap) timerWrap.classList.add('hidden');
}

function showKicked() {
    document.body.innerHTML = `
        <div class="min-h-screen flex items-center justify-center bg-slate-950 text-white px-6">
            <div class="text-center space-y-4">
                <div class="text-5xl">🚫</div>
                <h1 class="text-xl font-bold">Oturumdan çıkarıldınız</h1>
                <p class="text-slate-400 text-sm">Admin tarafından oturumdan çıkarıldınız.</p>
                <a href="${JOIN_URL}" class="inline-block mt-4 rounded-xl bg-sky-500 px-5 py-2 text-sm font-medium text-white hover:bg-sky-400">
                    Tekrar Katıl
                </a>
            </div>
        </div>`;
}

/* ══════════════════════════════════════════════════════════
   Sayfa ilk yüklenişi
══════════════════════════════════════════════════════════ */
if (INITIAL_QUESTION) {
    showNewQuestion(INITIAL_QUESTION, TIME_LIMIT, STARTED_AT_MS);
} else {
    setButtonsDisabled(true);
}

/* ══════════════════════════════════════════════════════════
   Cevap butonu → AJAX
══════════════════════════════════════════════════════════ */
document.querySelectorAll('.answer-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!currentQuestionId || hasAnsweredCurrent) return;
        getAudio(); // audio context'i aktive et (user gesture)

        const selectedOption = btn.dataset.option;
        mySelectedOption     = selectedOption;

        clearAnswerHighlight();
        btn.classList.add('border-sky-500','bg-sky-500/10');
        setButtonsDisabled(true);
        hasAnsweredCurrent = true;
        stopTimer();
        showWaiting();

        try {
            const res = await fetch(ANSWER_URL, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ selected_option: selectedOption, client_sent_at: Date.now() }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok === false) {
                /* Sunucu reddi (süre, kilit vb.) */
                hasAnsweredCurrent = false;
                mySelectedOption   = null;
                clearAnswerHighlight();
                setButtonsDisabled(false);
                waitingOverlay.classList.add('hidden');
                statusEl.textContent = data.message || 'Cevap gönderilemedi.';
            }
        } catch (err) {
            console.error('Cevap gönderilemedi:', err);
        }
    });
});

/* ══════════════════════════════════════════════════════════
   Echo — window.load ile garantili başlatma
══════════════════════════════════════════════════════════ */
window.addEventListener('load', function () {
    if (!window.Echo) {
        if (echoStatusEl) { echoStatusEl.textContent = '🔴 Bağlantı kurulamadı'; }
        return;
    }

    if (echoStatusEl) {
        echoStatusEl.textContent = '🟢 Canlı bağlantı aktif';
        echoStatusEl.className   = 'text-center text-[10px] text-emerald-600';
    }

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
                        if (opt === correct)           btn.classList.add('border-emerald-500','bg-emerald-500/10');
                        else if (opt === mySelectedOption) btn.classList.add('border-rose-500','bg-rose-500/10','opacity-40');
                        else                           btn.classList.add('opacity-40');
                    });
                    if (mySelectedOption) {
                        const isCorrect = mySelectedOption === correct;
                        statusEl.textContent = isCorrect ? '✅ Doğru cevap verdiniz!' : `❌ Yanlış! Doğru: ${correct}`;
                        if (isCorrect) playCorrect(); else playWrong();
                    } else {
                        statusEl.textContent = `💡 Doğru cevap: ${correct}`;
                    }
                    waitingOverlay.classList.add('hidden');
                    timeoutOverlay.classList.add('hidden');
                    optionsContainer.classList.remove('hidden');
                    showQuestionCard();
                }
                return;
            }

            /* Bitti */
            if (mode === 'show_results' || mode === 'finish') {
                playFinished();
                showFinished('🎉 Yarışma bitti! Sonuçlar büyük ekranda gösteriliyor.');
                return;
            }

            /* Soru yok */
            if (!q) { showWaitingScreen(); return; }

            /* Yeni soru */
            if (q.id !== currentQuestionId) {
                currentQuestionId  = q.id;
                hasAnsweredCurrent = false;
                mySelectedOption   = null;
            }
            showNewQuestion(q, tl, sat);
        })

        /* Cevaplar kilitlendi */
        .listen('.AnswersLocked', (e) => {
            if (e.locked && !hasAnsweredCurrent) {
                setButtonsDisabled(true);
                stopTimer();
                statusEl.textContent = '🔒 Cevaplar kilitlendi';
            } else if (!e.locked && !hasAnsweredCurrent) {
                setButtonsDisabled(false);
                statusEl.textContent = currentTimeLimit
                    ? (currentTimeLimit + ' saniyelik soru')
                    : 'Cevabını seç';
                startTimer(currentTimeLimit, currentStartedAtMs);
            }
        })

        /* Katılımcı atıldı */
        .listen('.ParticipantKicked', (e) => {
            if (e.participant_id === MY_PARTICIPANT_ID) {
                showKicked();
            }
        });
});
</script>
</body>
</html>
