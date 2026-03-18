@extends('layouts.app')

@section('content')
@php
    /** @var \App\Models\PresentationSession $session */
    /** @var \App\Models\Question|null $currentQuestion */
    /** @var array $initialCounts */
    /** @var int $initialTotal */
    // İlk açılışta cevap dağılımı
    $initialCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
    if ($currentQuestion) {
        \App\Models\Answer::where('presentation_session_id', $session->id)
            ->where('question_id', $currentQuestion->id)
            ->selectRaw('selected_option, count(*) as cnt')
            ->groupBy('selected_option')
            ->get()
            ->each(function ($row) use (&$initialCounts) {
                $initialCounts[$row->selected_option] = (int) $row->cnt;
            });
    }
    $initialTotal = array_sum($initialCounts);
@endphp

<div class="grid gap-4 lg:grid-cols-[2fr,3fr] mb-4">
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg space-y-4">
        <h1 class="text-sm font-semibold">Oturum</h1>

        <div class="flex items-center gap-4">
            <div class="shrink-0 rounded-2xl border border-slate-700 bg-slate-950/80 p-2">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode(route('participant.join', $session->code)) }}"
                    alt="QR"
                    class="h-32 w-32"
                >
            </div>

            <div class="space-y-2 text-xs text-slate-300 min-w-0 flex-1">
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText(@json(route('participant.join', $session->code)))"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-left font-mono text-[10px] text-sky-300 hover:border-sky-500 truncate"
                    title="Kopyala"
                >
                    {{ route('participant.join', $session->code) }}
                </button>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('remote.qr', $session->admin_token) }}" target="_blank"
                       class="flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-[10px] hover:border-sky-500">
                        Tam Ekran QR
                    </a>
                    <a href="{{ route('display.show', $session->code) }}" target="_blank"
                       class="flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-[10px] hover:border-violet-500">
                        Sunum Ekranı
                    </a>
                </div>

                <div class="flex gap-4 text-[11px] text-slate-400 pt-1">
                    <span>Katılımcı: <span id="participant-count" class="text-sky-300 font-semibold">{{ $session->participants->count() }}</span></span>
                    <span>Soru: <span class="text-sky-300 font-semibold">{{ $session->quiz->questions->count() }}</span></span>
                    <span>Kod: <span class="font-mono text-sky-300 font-semibold">{{ $session->code }}</span></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-1 border-t border-slate-800/60">
            <span id="echo-dot" class="h-2 w-2 rounded-full bg-slate-600 shrink-0"></span>
            <span id="echo-label" class="text-[10px] text-slate-500">Bağlanıyor…</span>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg space-y-4">
        <header class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold mb-0.5">Akışı Yönet</h2>
                <p class="text-[11px] text-slate-400">{{ $session->quiz->title }} · <span class="font-mono text-sky-400">{{ $session->code }}</span></p>
            </div>
            <div id="active-question-badge" class="{{ $currentQuestion ? '' : 'invisible' }} text-right text-[11px] text-slate-400 shrink-0">
                <div>Aktif soru</div>
                <div id="active-question-label" class="text-sky-300 font-semibold">
                    {{ $currentQuestion ? '#'.$currentQuestion->position.' · '.$currentQuestion->points.'p' : '' }}
                </div>
            </div>
        </header>

        <div class="grid grid-cols-4 gap-2">
            <button data-mode="prev"
                    class="ctrl-btn rounded-xl border border-slate-700 bg-slate-900/80 px-2 py-3 text-xs font-medium hover:border-sky-500 active:scale-95 transition-transform">
                ← Önceki
            </button>
            <button data-mode="next"
                    class="ctrl-btn rounded-xl bg-sky-500 px-2 py-3 text-xs font-semibold text-white hover:bg-sky-400 active:scale-95 transition-transform">
                Sıradaki →
            </button>
            <button data-mode="reveal"
                    class="ctrl-btn rounded-xl border border-amber-500/60 bg-amber-500/10 px-2 py-3 text-xs font-medium text-amber-200 hover:bg-amber-500/20 active:scale-95 transition-transform">
                Cevabı Göster
            </button>
            <button data-mode="show_results"
                    class="ctrl-btn rounded-xl border border-emerald-500/60 bg-emerald-500/10 px-2 py-3 text-xs font-medium text-emerald-200 hover:bg-emerald-500/20 active:scale-95 transition-transform">
                Sonuçlar
            </button>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-1">
            <div class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2">
                <span class="text-[10px] text-slate-400 shrink-0">Süre:</span>
                <input id="timer-input" type="number" min="0" max="600" value="{{ $session->time_limit }}"
                       class="w-14 bg-transparent text-xs text-slate-200 outline-none font-mono text-right">
                <span class="text-[10px] text-slate-500">sn</span>
                <button id="timer-save-btn"
                        class="ml-auto text-[10px] text-sky-400 hover:text-sky-300 font-medium">
                    Kaydet
                </button>
            </div>

            <button id="lock-btn"
                    class="rounded-xl border px-3 py-2 text-xs font-medium transition-colors active:scale-95
                           {{ $session->answers_locked
                               ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-200'
                               : 'border-rose-500/60 bg-rose-500/10 text-rose-200' }}">
                <span id="lock-label">{{ $session->answers_locked ? 'Cevapları Aç' : 'Cevapları Kilitle' }}</span>
            </button>

            <a href="{{ route('remote.export', $session->admin_token) }}"
               class="flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-[10px] text-slate-300 hover:border-emerald-500 hover:text-emerald-300 transition-colors">
                CSV İndir
            </a>
            <a href="{{ route('remote.analytics', $session->admin_token) }}"
               class="flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-[10px] text-slate-300 hover:border-violet-500 hover:text-violet-300 transition-colors">
                Analitik
            </a>
        </div>

        <div id="ctrl-loading" class="hidden text-center text-[10px] text-sky-400">
            İşleniyor…
        </div>

        <div class="pt-3 border-t border-slate-800/70">
            <div class="grid grid-cols-2 gap-3 max-h-44 overflow-y-auto pr-1">
                <div class="space-y-1" id="question-list">
                    @foreach($session->quiz->questions as $q)
                        <button data-mode="goto" data-question-id="{{ $q->id }}"
                                class="ctrl-btn question-item w-full text-left rounded-lg border px-2 py-1.5 text-[10px]
                                       {{ $currentQuestion && $currentQuestion->id === $q->id
                                            ? 'border-sky-500 bg-sky-500/10 text-sky-100'
                                            : 'border-slate-800 bg-slate-900/70 text-slate-300 hover:border-sky-500' }}">
                            <span class="font-semibold">#{{ $q->position }}</span>
                            <span class="text-slate-400 ml-1">({{ $q->points }}p)</span>
                            <span class="ml-1">{{ \Illuminate\Support\Str::limit($q->text, 32) }}</span>
                        </button>
                    @endforeach
                </div>

                <div>
                    <div class="text-[10px] text-slate-400 mb-1">Katılımcılar</div>
                    <ul id="participant-list" class="space-y-1 text-[10px] text-slate-300">
                        @foreach($session->participants as $p)
                            <li data-id="{{ $p->id }}"
                                class="flex items-center justify-between rounded-md bg-slate-900/70 px-2 py-1 group">
                                <div class="min-w-0 flex-1">
                                    <span class="truncate block">{{ $p->name }}</span>
                                    @if($p->team_name)
                                        <span class="text-[9px] text-slate-500 truncate block">{{ $p->team_name }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 ml-2 shrink-0">
                                    <span class="font-mono text-slate-500 score-display">{{ $p->total_score }}p</span>
                                    <button data-participant-id="{{ $p->id }}"
                                            class="kick-btn hidden group-hover:flex items-center justify-center
                                                   h-4 w-4 rounded text-[9px] text-rose-400 hover:bg-rose-500/20 transition-colors"
                                            title="Oturumdan çıkar">x</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg space-y-4">
        <header class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">Anlık Cevaplar</h2>
            <span class="text-[11px] text-slate-400">Toplam: <span id="total-answers-count" class="text-sky-300 font-semibold">{{ $initialTotal }}</span></span>
        </header>

        @foreach([
            'A' => ['sky-500',    'sky-400'],
            'B' => ['violet-500', 'violet-400'],
            'C' => ['amber-500',  'amber-400'],
            'D' => ['rose-500',   'rose-400'],
        ] as $opt => [$barColor, $labelColor])
            @php
                $n   = $initialCounts[$opt] ?? 0;
                $pct = $initialTotal > 0 ? round($n / $initialTotal * 100) : 0;
            @endphp
            <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-{{ $labelColor }} w-5">{{ $opt }}</span>
                    <div class="flex items-center gap-2">
                        <span id="pct-{{ $opt }}" class="text-slate-500 text-[10px] w-8 text-right">{{ $pct }}%</span>
                        <span id="count-{{ $opt }}" class="text-slate-300 font-semibold w-4 text-right">{{ $n }}</span>
                    </div>
                </div>
                <div class="w-full rounded-full bg-slate-800 h-3 overflow-hidden">
                    <div id="bar-{{ $opt }}" class="h-full bg-{{ $barColor }} transition-all duration-500 ease-out" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg space-y-3">
        <h2 class="text-sm font-semibold">Canlı Sıralama</h2>
        <ol id="live-scoreboard" class="space-y-1 text-[11px]">
            @forelse($session->participants()->orderByDesc('total_score')->orderByDesc('total_speed_bonus')->take(10)->get() as $rank => $p)
                <li data-id="{{ $p->id }}"
                    class="flex items-center gap-2 rounded-lg px-3 py-1.5
                           {{ $rank === 0 ? 'bg-yellow-500/10 border border-yellow-500/30' : ($rank === 1 ? 'bg-slate-700/40' : 'bg-slate-900/70') }}">
                    <span class="w-5 text-center font-bold {{ $rank === 0 ? 'text-yellow-400' : ($rank === 1 ? 'text-slate-300' : 'text-slate-500') }}">{{ $rank + 1 }}.</span>
                    <span class="flex-1 truncate text-slate-200">{{ $p->name }}{{ $p->team_name ? ' · '.$p->team_name : '' }}</span>
                    <span class="font-semibold {{ $rank === 0 ? 'text-yellow-300' : 'text-sky-300' }}">{{ $p->total_score }}p</span>
                </li>
            @empty
                <li class="text-slate-500 text-center py-3">Henüz katılımcı yok</li>
            @endforelse
        </ol>
    </section>
</div>

@endsection

@push('scripts')
<script>
const ACTION_URL   = @json(route('remote.action', $session->admin_token));
const LOCK_URL     = @json(route('remote.lock', $session->admin_token));
const TIMER_URL    = @json(route('remote.timer', $session->admin_token));
const CSRF_TOKEN   = @json(csrf_token());
const SESSION_CODE = @json($session->code);
const ADMIN_TOKEN  = @json($session->admin_token);

async function ajaxPost(url, body = {}) {
    const loadEl = document.getElementById('ctrl-loading');
    loadEl?.classList.remove('hidden');
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        return await res.json();
    } finally {
        loadEl?.classList.add('hidden');
    }
}

document.querySelectorAll('.ctrl-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const mode = btn.dataset.mode;
        const qid  = btn.dataset.questionId;
        if (!mode) return;
        ajaxPost(ACTION_URL, qid ? { mode, question_id: qid } : { mode });
    });
});

document.getElementById('lock-btn')?.addEventListener('click', async function () {
    const data = await ajaxPost(LOCK_URL);
    if (!data) return;
    const locked = !!data.locked;
    const label  = document.getElementById('lock-label');
    if (label) label.textContent = locked ? 'Cevapları Aç' : 'Cevapları Kilitle';
    this.classList.remove('border-emerald-500/60','bg-emerald-500/10','text-emerald-200','border-rose-500/60','bg-rose-500/10','text-rose-200');
    this.classList.add(...(locked ? ['border-emerald-500/60','bg-emerald-500/10','text-emerald-200'] : ['border-rose-500/60','bg-rose-500/10','text-rose-200']));
});

document.getElementById('timer-save-btn')?.addEventListener('click', async function () {
    const val = parseInt(document.getElementById('timer-input').value) || 0;
    const data = await ajaxPost(TIMER_URL, { time_limit: val });
    if (data?.ok) {
        const old = this.textContent;
        this.textContent = 'Kaydedildi';
        setTimeout(() => { this.textContent = old; }, 1200);
    }
});

document.getElementById('participant-list')?.addEventListener('click', async function (e) {
    const btn = e.target.closest('.kick-btn');
    if (!btn) return;
    const pid = btn.dataset.participantId;
    if (!confirm('Bu katılımcı çıkarılsın mı?')) return;
    const url = `/remote/${ADMIN_TOKEN}/participants/${pid}`;
    const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }});
    if (res.ok) document.querySelector(`#participant-list [data-id="${pid}"]`)?.remove();
});

const BAR_COLORS = { A:'bg-sky-500', B:'bg-violet-500', C:'bg-amber-500', D:'bg-rose-500' };
function updateAnswerBars(counts, total) {
    ['A','B','C','D'].forEach(opt => {
        const n = Number(counts?.[opt] ?? 0);
        const pct = total > 0 ? Math.round(n / total * 100) : 0;
        document.getElementById('bar-'+opt)?.style && (document.getElementById('bar-'+opt).style.width = pct + '%');
        const cnt = document.getElementById('count-'+opt); if (cnt) cnt.textContent = n;
        const p = document.getElementById('pct-'+opt); if (p) p.textContent = pct + '%';
    });
    const t = document.getElementById('total-answers-count'); if (t) t.textContent = total || 0;
}

function updateScoreboard(scoreboard) {
    const list = document.getElementById('live-scoreboard');
    if (!list || !Array.isArray(scoreboard)) return;
    list.innerHTML = '';
    if (!scoreboard.length) {
        list.innerHTML = '<li class="text-slate-500 text-center py-3">Henüz katılımcı yok</li>';
        return;
    }
    scoreboard.forEach((p, i) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2 rounded-lg px-3 py-1.5 ' + (i===0 ? 'bg-yellow-500/10 border border-yellow-500/30' : (i===1 ? 'bg-slate-700/40' : 'bg-slate-900/70'));
        const team = p.team_name ? (' · ' + p.team_name) : '';
        li.innerHTML = `<span class="w-5 text-center font-bold ${i===0?'text-yellow-400':i===1?'text-slate-300':'text-slate-500'}">${i+1}.</span>` +
                       `<span class="flex-1 truncate text-slate-200">${p.name}${team}</span>` +
                       `<span class="font-semibold ${i===0?'text-yellow-300':'text-sky-300'}">${p.total_score}p</span>`;
        list.appendChild(li);
    });
}

function updateParticipantScore(participant) {
    const item = document.querySelector(`#participant-list [data-id="${participant.id}"]`);
    const el = item?.querySelector('.score-display');
    if (el) el.textContent = participant.total_score + 'p';
}

function initEcho() {
    const dot = document.getElementById('echo-dot');
    const label = document.getElementById('echo-label');
    try {
        const conn = window.Echo.connector.pusher.connection;
        conn.bind('connected', () => { dot?.classList.replace('bg-slate-600','bg-emerald-500'); if (label) label.textContent = 'Canlı bağlantı aktif'; });
        conn.bind('disconnected', () => { dot?.classList.replace('bg-emerald-500','bg-red-500'); if (label) label.textContent = 'Bağlantı kesildi'; });
    } catch (e) {}

    window.Echo.channel('quiz.session.' + SESSION_CODE)
        .listen('.ParticipantJoined', (e) => {
            const cnt = document.getElementById('participant-count');
            if (cnt && e.total != null) cnt.textContent = e.total;
            if (e.participant) {
                const list = document.getElementById('participant-list');
                if (list && !list.querySelector(`[data-id="${e.participant.id}"]`)) {
                    const li = document.createElement('li');
                    li.setAttribute('data-id', e.participant.id);
                    li.className = 'flex items-center justify-between rounded-md bg-slate-900/70 px-2 py-1 group';
                    const team = e.participant.team_name ? `<span class="text-[9px] text-slate-500 truncate block">${e.participant.team_name}</span>` : '';
                    li.innerHTML = `<div class="min-w-0 flex-1"><span class="truncate block">${e.participant.name}</span>${team}</div>` +
                                   `<div class="flex items-center gap-1 ml-2 shrink-0"><span class="font-mono text-slate-500 score-display">0p</span>` +
                                   `<button data-participant-id="${e.participant.id}" class="kick-btn hidden group-hover:flex items-center justify-center h-4 w-4 rounded text-[9px] text-rose-400 hover:bg-rose-500/20" title="Oturumdan çıkar">x</button></div>`;
                    list.appendChild(li);
                }
            }
        })
        .listen('.AnswerSubmitted', (e) => {
            updateAnswerBars(e.answer_counts, e.total_answers);
            updateParticipantScore(e.participant);
            updateScoreboard(e.scoreboard);
        })
        .listen('.ParticipantKicked', (e) => {
            document.querySelector(`#participant-list [data-id="${e.participant_id}"]`)?.remove();
        })
        .listen('.AnswersLocked', (e) => {
            const lockBtn = document.getElementById('lock-btn');
            const lockLbl = document.getElementById('lock-label');
            if (lockLbl) lockLbl.textContent = e.locked ? 'Cevapları Aç' : 'Cevapları Kilitle';
            if (lockBtn) {
                lockBtn.classList.remove('border-emerald-500/60','bg-emerald-500/10','text-emerald-200','border-rose-500/60','bg-rose-500/10','text-rose-200');
                lockBtn.classList.add(...(e.locked ? ['border-emerald-500/60','bg-emerald-500/10','text-emerald-200'] : ['border-rose-500/60','bg-rose-500/10','text-rose-200']));
            }
        });
}

window.addEventListener('load', () => {
    if (window.Echo) initEcho();
    else {
        const label = document.getElementById('echo-label');
        if (label) label.textContent = 'Echo bulunamadı';
    }
});
</script>
@endpush
