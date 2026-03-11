<!DOCTYPE html>
<html lang="tr" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <title>Cafe Quiz Pro · Oyuncu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-slate-50">
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md space-y-6">
        <header class="text-center">
            <p class="text-xs text-slate-400 mb-1">Oturum: <span class="font-mono text-sky-400">{{ $session->code }}</span></p>
            <h1 class="text-xl font-semibold">{{ $participant->name }}</h1>
        </header>

        <main class="relative rounded-2xl border border-slate-800 bg-slate-900/70 p-6 space-y-4">
            <p id="status-line" class="text-sm text-slate-400">
                Sunucu soruyu başlattığında burada görünecek.
            </p>
            <p id="question-text" class="text-base font-medium min-h-[3rem]"></p>

            <div id="options-container">
                <form id="answer-form" method="post"
                      action="{{ route('participant.answer', [$session->code, $participant]) }}"
                      class="grid grid-cols-2 gap-3">
                    @csrf
                    <input type="hidden" name="selected_option" id="selected_option">
                    <input type="hidden" name="client_sent_at" id="client_sent_at">

                    @foreach(['A','B','C','D'] as $opt)
                        <button type="button"
                                data-option="{{ $opt }}"
                                class="answer-btn rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-3 text-sm font-semibold hover:border-sky-500 disabled:opacity-40 disabled:cursor-not-allowed">
                            {{ $opt }}
                        </button>
                    @endforeach
                </form>
            </div>

            {{-- Cevap gönderildikten sonra, admin yeni soruya geçene kadar tam sayfa bekleme katmanı --}}
            <div id="waiting-overlay"
                 class="hidden absolute inset-0 rounded-2xl bg-slate-950/90 backdrop-blur flex flex-col items-center justify-center text-center px-6">
                <div class="mb-4 h-10 w-10 rounded-full border-2 border-slate-700 border-t-sky-400 animate-spin"></div>
                <div class="text-sm font-medium text-slate-100 mb-1">
                    Cevabın kilitlendi
                </div>
                <p class="text-xs text-slate-400 max-w-xs">
                    Diğer oyuncuların cevapları bekleniyor. Sunucu bir sonraki soruyu başlattığında ekran otomatik olarak güncellenecek.
                </p>
            </div>
        </main>
    </div>
</div>

@php
    $initialQuestion = $currentQuestion ? [
        'id' => $currentQuestion->id,
        'text' => $currentQuestion->text,
        'points' => $currentQuestion->points,
    ] : null;
@endphp

<script>
    const sessionCode = @json($session->code);
    const initialQuestion = @json($initialQuestion);

    const statusEl = document.getElementById('status-line');
    const questionTextEl = document.getElementById('question-text');
    const answerForm = document.getElementById('answer-form');
    const selectedInput = document.getElementById('selected_option');
    const clientSentAt = document.getElementById('client_sent_at');
    const waitingOverlay = document.getElementById('waiting-overlay');
    const optionsContainer = document.getElementById('options-container');

    let currentQuestionId = initialQuestion ? initialQuestion.id : null;
    let hasAnsweredCurrent = false;

    function setButtonsDisabled(disabled) {
        document.querySelectorAll('.answer-btn').forEach(btn => {
            btn.disabled = disabled;
        });
    }

    function clearAnswerHighlight() {
        document.querySelectorAll('.answer-btn').forEach(btn => {
            btn.classList.remove('border-emerald-500', 'bg-emerald-500/10');
        });
    }

    // Sayfa ilk açıldığında hali hazırda aktif bir soru varsa onu göster
    if (initialQuestion) {
        statusEl.textContent = `${initialQuestion.points} puanlık soru`;
        questionTextEl.textContent = initialQuestion.text;
        setButtonsDisabled(false);
        hasAnsweredCurrent = false;
    } else {
        setButtonsDisabled(true);
    }

    if (window.Echo) {
        window.Echo.channel('quiz.session.' + sessionCode)
            .listen('.QuizStateUpdated', (e) => {
                const q = e.question;
                const mode = e.mode;
                if (mode === 'show_results' || mode === 'finish') {
                    statusEl.textContent = 'Yarışma bitti. Sonuçlar büyük ekranda gösteriliyor.';
                    questionTextEl.textContent = '';
                    setButtonsDisabled(true);
                    clearAnswerHighlight();
                    if (waitingOverlay) waitingOverlay.classList.add('hidden');
                    if (optionsContainer) optionsContainer.classList.remove('hidden');
                    return;
                }
                if (!q) {
                    statusEl.textContent = 'Birazdan yeni soru gelecek…';
                    questionTextEl.textContent = '';
                    setButtonsDisabled(true);
                    clearAnswerHighlight();
                    if (waitingOverlay) waitingOverlay.classList.add('hidden');
                    if (optionsContainer) optionsContainer.classList.remove('hidden');
                    return;
                }

                // Yeni soru geldiyse, kullanıcı için tekrar cevap verebilir hale getir
                if (!currentQuestionId || (q.id && q.id !== currentQuestionId)) {
                    currentQuestionId = q.id;
                    hasAnsweredCurrent = false;
                    if (optionsContainer) optionsContainer.classList.remove('hidden');
                    if (waitingOverlay) waitingOverlay.classList.add('hidden');
                    clearAnswerHighlight();
                    setButtonsDisabled(false);
                }

                statusEl.textContent = `${q.points} puanlık soru`;
                questionTextEl.textContent = q.text;
            });
    }

    document.querySelectorAll('.answer-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!currentQuestionId) {
                // Güvenlik için: soru ID'si yoksa cevap almıyoruz
                return;
            }

            selectedInput.value = btn.dataset.option;
            clientSentAt.value = Date.now();

            clearAnswerHighlight();
            btn.classList.add('border-emerald-500', 'bg-emerald-500/10');

            setButtonsDisabled(true);
            hasAnsweredCurrent = true;
            if (waitingOverlay) waitingOverlay.classList.remove('hidden');
            if (optionsContainer) optionsContainer.classList.add('hidden');
            answerForm.submit();
        });
    });
</script>
</body>
</html>

