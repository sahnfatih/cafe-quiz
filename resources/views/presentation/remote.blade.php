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

<style>
    * { -webkit-tap-highlight-color: transparent; }
    .ctrl-btn:active { transform: scale(.94); }
    #question-list::-webkit-scrollbar { width: 4px; }
    #question-list::-webkit-scrollbar-track { background: transparent; }
    #question-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }
    #participant-list::-webkit-scrollbar { width: 4px; }
    #participant-list::-webkit-scrollbar-track { background: transparent; }
    #participant-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }
</style>

<div class="space-y-4 pb-6">

{{-- ──────────── ÜST BÖLÜM: Oturum + Kontroller ──────────── --}}
<div class="grid gap-4 lg:grid-cols-[2fr,3fr]">

    {{-- ── Oturum Kartı ── --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg space-y-4">

        <div class="flex items-center justify-between">
            <h1 class="text-sm font-black text-white">🎮 Oturum</h1>
            <div class="flex items-center gap-1.5">
                <span id="echo-dot" class="h-2 w-2 rounded-full bg-slate-600 shrink-0"></span>
                <span id="echo-label" class="text-[10px] text-slate-500">Bağlanıyor…</span>
            </div>
        </div>

        {{-- QR + Bilgiler --}}
        <div class="flex items-start gap-4">
            <div class="shrink-0 rounded-2xl border border-slate-700 bg-white p-2 shadow-md">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode(route('participant.join', $session->code)) }}"
                     alt="QR" class="h-28 w-28 block rounded-lg">
            </div>

            <div class="flex-1 min-w-0 space-y-2">
                {{-- Kod badge --}}
                <div class="flex items-center gap-2 rounded-xl bg-sky-500/10 border border-sky-500/20 px-3 py-2">
                    <span class="text-[10px] text-slate-400">Kod</span>
                    <span class="font-mono font-black text-sky-300 text-lg tracking-[0.2em]">{{ $session->code }}</span>
                </div>

                {{-- İstatistikler --}}
                <div class="grid grid-cols-3 gap-1.5">
                    <div class="rounded-xl bg-slate-800/60 border border-slate-700/60 px-2 py-1.5 text-center">
                        <p id="participant-count" class="text-base font-black text-sky-300">{{ $session->participants->count() }}</p>
                        <p class="text-[9px] text-slate-500">Oyuncu</p>
                    </div>
                    <div class="rounded-xl bg-slate-800/60 border border-slate-700/60 px-2 py-1.5 text-center">
                        <p class="text-base font-black text-violet-300">{{ $session->quiz->questions->count() }}</p>
                        <p class="text-[9px] text-slate-500">Soru</p>
                    </div>
                    <div class="rounded-xl bg-slate-800/60 border border-slate-700/60 px-2 py-1.5 text-center">
                        <p class="text-base font-black text-amber-300">{{ $session->time_limit }}<span class="text-[10px] font-normal text-slate-500">sn</span></p>
                        <p class="text-[9px] text-slate-500">Süre</p>
                    </div>
                </div>

                {{-- Bağlantı URL --}}
                <button type="button"
                        onclick="copyJoinUrl(this)"
                        data-join-url="@json(route('participant.join', $session->code))"
                        class="w-full rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-1.5
                               text-[10px] text-sky-400 hover:border-sky-500 hover:text-sky-300
                               transition-colors text-center font-medium">
                    Bağlantıyı Kopyala
                </button>
            </div>
        </div>

        {{-- Hızlı Linkler --}}
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('remote.qr', $session->admin_token) }}" target="_blank"
               class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/60
                      px-3 py-2 text-xs text-slate-300 hover:border-sky-500 hover:text-sky-300 transition-colors">
                <span>📱</span> Tam Ekran QR
            </a>
            <a href="{{ route('display.show', $session->code) }}" target="_blank"
               class="flex items-center justify-center gap-1.5 rounded-xl border border-violet-500/40 bg-violet-500/10
                      px-3 py-2 text-xs text-violet-300 hover:bg-violet-500/20 transition-colors">
                <span>📺</span> Sunum Ekranı
            </a>
        </div>
    </section>

    {{-- ── Kontrol Kartı ── --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg space-y-4">

        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-white">🎛️ Akışı Yönet</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $session->quiz->title }}</p>
            </div>
            <div id="active-question-badge"
                 class="{{ $currentQuestion ? '' : 'invisible' }} shrink-0 text-right rounded-xl bg-sky-500/10 border border-sky-500/20 px-3 py-1.5">
                <p class="text-[9px] text-slate-500 leading-none mb-0.5">Aktif Soru</p>
                <p id="active-question-label" class="text-sm font-black text-sky-300">
                    {{ $currentQuestion ? '#'.$currentQuestion->position.' · '.$currentQuestion->points.'p' : '' }}
                </p>
            </div>
        </div>

        {{-- Ana Navigasyon Butonları --}}
        <div class="grid grid-cols-2 gap-2.5">
            <button data-mode="prev"
                    class="ctrl-btn flex items-center justify-center gap-2 rounded-2xl
                           border border-slate-700 bg-slate-800/60
                           py-4 text-sm font-bold text-slate-300
                           hover:border-slate-500 hover:text-white transition-colors">
                ← Önceki
            </button>
            <button data-mode="next"
                    class="ctrl-btn flex items-center justify-center gap-2 rounded-2xl
                           bg-gradient-to-r from-sky-500 to-sky-400
                           py-4 text-sm font-black text-white shadow-lg shadow-sky-500/25
                           hover:from-sky-400 hover:to-sky-300 transition-colors">
                Sıradaki →
            </button>
        </div>

        {{-- Soru Kontrolü --}}
        <div class="grid grid-cols-2 gap-2.5">
            <button data-mode="reveal"
                    class="ctrl-btn flex items-center justify-center gap-1.5 rounded-2xl
                           border border-amber-500/50 bg-amber-500/10
                           py-3 text-sm font-bold text-amber-200
                           hover:bg-amber-500/20 active:scale-95 transition-all">
                💡 Cevabı Göster
            </button>
            <button data-mode="show_results"
                    class="ctrl-btn flex items-center justify-center gap-1.5 rounded-2xl
                           border border-emerald-500/50 bg-emerald-500/10
                           py-3 text-sm font-bold text-emerald-200
                           hover:bg-emerald-500/20 active:scale-95 transition-all">
                🥇 Top 3
            </button>
        </div>

        {{-- ─── Sunum Yönetimi ─── --}}
        <div class="rounded-2xl border border-slate-700/60 bg-slate-800/30 p-3 space-y-2.5">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">🎬 Sunum Yönetimi</p>

            {{-- Bekleme ekranına dön --}}
            <button data-mode="lobby"
                    class="ctrl-btn w-full flex items-center justify-center gap-2 rounded-xl
                           border border-sky-700/60 bg-sky-900/30
                           py-2.5 text-sm font-bold text-sky-300
                           hover:bg-sky-800/40 hover:border-sky-500/70 active:scale-95 transition-all">
                🏠 Bekleme Ekranına Dön
            </button>

            {{-- Tüm sonuçları göster --}}
            <button data-mode="show_all_results"
                    class="ctrl-btn w-full flex items-center justify-center gap-2 rounded-xl
                           border border-violet-500/50 bg-violet-500/10
                           py-2.5 text-sm font-bold text-violet-200
                           hover:bg-violet-500/20 active:scale-95 transition-all">
                🎊 Tüm Sonuçları Göster
            </button>

            {{-- Sunumu bitir --}}
            <button id="finish-btn"
                    class="ctrl-btn w-full flex items-center justify-center gap-2 rounded-xl
                           border border-rose-700/50 bg-rose-900/20
                           py-2.5 text-sm font-bold text-rose-400
                           hover:bg-rose-800/30 hover:border-rose-500/70 active:scale-95 transition-all">
                🏁 Sunumu Bitir
            </button>
        </div>

        {{-- Süre + Kilit --}}
        <div class="grid grid-cols-2 gap-2.5">
            <div class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-2.5">
                <span class="text-xs text-slate-500 shrink-0">⏱</span>
                <input id="timer-input" type="number" min="0" max="600" value="{{ $session->time_limit }}"
                       class="flex-1 w-0 min-w-0 bg-transparent text-sm text-white outline-none font-mono">
                <span class="text-[10px] text-slate-500 shrink-0">sn</span>
                <button id="timer-save-btn"
                        class="shrink-0 rounded-lg bg-sky-500/20 border border-sky-500/30 px-2 py-1
                               text-[10px] text-sky-300 hover:bg-sky-500/30 font-semibold transition-colors">
                    Kaydet
                </button>
            </div>
            <button id="lock-btn"
                    class="rounded-xl border px-3 py-2.5 text-sm font-bold transition-colors
                           {{ $session->answers_locked
                               ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-200'
                               : 'border-rose-500/50 bg-rose-500/10 text-rose-200' }}">
                <span id="lock-label">{{ $session->answers_locked ? '🔓 Cevapları Aç' : '🔒 Kilitle' }}</span>
            </button>
        </div>

        {{-- Export + Analitik --}}
        <div class="grid grid-cols-2 gap-2.5">
            <a href="{{ route('remote.export', $session->admin_token) }}"
               class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/60
                      px-3 py-2 text-xs text-slate-300 hover:border-emerald-500 hover:text-emerald-300 transition-colors">
                📊 CSV İndir
            </a>
            <a href="{{ route('remote.analytics', $session->admin_token) }}"
               class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/60
                      px-3 py-2 text-xs text-slate-300 hover:border-violet-500 hover:text-violet-300 transition-colors">
                📈 Analitik
            </a>
        </div>

        <div id="ctrl-loading" class="hidden text-center text-xs text-sky-400 py-1 animate-pulse">
            ⏳ İşleniyor…
        </div>
    </section>
</div>

{{-- ──────────── ORTA BÖLÜM: Soru Listesi + Katılımcılar ──────────── --}}
<div class="grid gap-4 lg:grid-cols-2">

    {{-- Soru Listesi --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">📋 Sorular</h3>
        <div id="question-list" class="space-y-1 max-h-48 overflow-y-auto pr-1">
            @foreach($session->quiz->questions as $q)
                <button data-mode="goto" data-question-id="{{ $q->id }}"
                        class="ctrl-btn question-item w-full text-left rounded-xl border px-3 py-2 text-[11px]
                               transition-colors
                               {{ $currentQuestion && $currentQuestion->id === $q->id
                                    ? 'border-sky-500/60 bg-sky-500/10 text-sky-200'
                                    : 'border-slate-800/80 bg-slate-900/60 text-slate-300 hover:border-sky-500/40 hover:bg-sky-500/5' }}">
                    <span class="font-black text-sky-400">#{{ $q->position }}</span>
                    <span class="text-slate-500 ml-1 text-[10px]">({{ $q->points }}p)</span>
                    <span class="ml-1.5">{{ \Illuminate\Support\Str::limit($q->text, 40) }}</span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Katılımcılar --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">👥 Katılımcılar</h3>
        <ul id="participant-list" class="space-y-1 max-h-48 overflow-y-auto pr-1 text-[11px]">
            @foreach($session->participants as $p)
                <li data-id="{{ $p->id }}"
                    class="flex items-center gap-2 rounded-xl bg-slate-800/50 border border-slate-700/50
                           px-3 py-2 group hover:border-slate-600 transition-colors">
                    <div class="min-w-0 flex-1">
                        <span class="truncate block text-slate-200 font-medium">{{ $p->name }}</span>
                        @if($p->team_name)
                            <span class="text-[9px] text-violet-400 truncate block">{{ $p->team_name }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="font-mono text-sky-400 font-semibold score-display">{{ $p->total_score }}p</span>
                        <button data-participant-id="{{ $p->id }}"
                                class="kick-btn hidden group-hover:flex items-center justify-center
                                       h-5 w-5 rounded-lg text-[10px] text-rose-400
                                       hover:bg-rose-500/20 border border-rose-500/30 transition-colors"
                                title="Oturumdan çıkar">✕</button>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</div>

{{-- ──────────── ALT BÖLÜM: Cevap Grafikleri + Sıralama ──────────── --}}
<div class="grid gap-4 lg:grid-cols-2">

    {{-- Anlık Cevaplar --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-black text-white">📊 Anlık Cevaplar</h2>
            <span class="rounded-full bg-sky-500/10 border border-sky-500/20 px-2.5 py-0.5 text-[11px] text-sky-300 font-semibold">
                Toplam: <span id="total-answers-count">{{ $initialTotal }}</span>
            </span>
        </div>

        @foreach([
            'A' => ['sky-500',    'sky-400',    'bg-sky-500'],
            'B' => ['violet-500', 'violet-400', 'bg-violet-500'],
            'C' => ['amber-500',  'amber-400',  'bg-amber-500'],
            'D' => ['rose-500',   'rose-400',   'bg-rose-500'],
        ] as $opt => [$barColor, $labelColor, $bgClass])
            @php
                $n   = $initialCounts[$opt] ?? 0;
                $pct = $initialTotal > 0 ? round($n / $initialTotal * 100) : 0;
            @endphp
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-lg {{ $bgClass }} flex items-center justify-center text-white text-[10px] font-black">{{ $opt }}</span>
                        <div class="w-32 h-2 rounded-full bg-slate-800 overflow-hidden">
                            <div id="bar-{{ $opt }}" class="h-full {{ $bgClass }} transition-all duration-500 ease-out" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-[11px]">
                        <span id="pct-{{ $opt }}" class="text-slate-500 w-8 text-right">{{ $pct }}%</span>
                        <span id="count-{{ $opt }}" class="text-slate-200 font-bold w-4 text-right">{{ $n }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    {{-- Canlı Sıralama --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg">
        <h2 class="text-sm font-black text-white mb-3">🏆 Canlı Sıralama</h2>
        <ol id="live-scoreboard" class="space-y-1.5">
            @forelse($session->participants()->orderByDesc('total_score')->orderByDesc('total_speed_bonus')->take(10)->get() as $rank => $p)
                <li data-id="{{ $p->id }}"
                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-[11px]
                           {{ $rank === 0 ? 'bg-yellow-500/10 border border-yellow-500/30'
                                         : ($rank === 1 ? 'bg-slate-700/40 border border-slate-700/50'
                                                        : 'bg-slate-900/60 border border-slate-800/60') }}">
                    <span class="w-5 text-center font-black text-sm
                                 {{ $rank === 0 ? 'text-yellow-400' : ($rank === 1 ? 'text-slate-300' : 'text-slate-600') }}">
                        {{ $rank === 0 ? '🥇' : ($rank === 1 ? '🥈' : ($rank === 2 ? '🥉' : ($rank+1).'.')) }}
                    </span>
                    <span class="flex-1 truncate text-slate-200 font-medium">
                        {{ $p->name }}{{ $p->team_name ? ' · '.$p->team_name : '' }}
                    </span>
                    <span class="font-black {{ $rank === 0 ? 'text-yellow-300' : 'text-sky-300' }}">
                        {{ $p->total_score }}p
                    </span>
                </li>
            @empty
                <li class="text-slate-500 text-center py-4 text-xs">Henüz katılımcı yok</li>
            @endforelse
        </ol>
    </section>
</div>

</div>
@endsection

@push('scripts')
<script>
function copyJoinUrl(btn) {
    var url = btn.dataset.joinUrl;
    navigator.clipboard.writeText(url).then(function () {
        var orig = btn.textContent;
        btn.textContent = 'Kopyalandı ✓';
        setTimeout(function () { btn.textContent = orig; }, 1500);
    });
}

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

/* Sunumu Bitir — onay dialogu */
document.getElementById('finish-btn')?.addEventListener('click', () => {
    if (!confirm('Sunumu bitirmek istediğinizden emin misiniz?\nBu işlem geri alınamaz.')) return;
    ajaxPost(ACTION_URL, { mode: 'finish' });
});

document.getElementById('lock-btn')?.addEventListener('click', async function () {
    const data = await ajaxPost(LOCK_URL);
    if (!data) return;
    const locked = !!data.locked;
    const label  = document.getElementById('lock-label');
    if (label) label.textContent = locked ? '🔓 Cevapları Aç' : '🔒 Kilitle';
    this.classList.remove('border-emerald-500/50','bg-emerald-500/10','text-emerald-200','border-rose-500/50','bg-rose-500/10','text-rose-200');
    this.classList.add(...(locked ? ['border-emerald-500/50','bg-emerald-500/10','text-emerald-200'] : ['border-rose-500/50','bg-rose-500/10','text-rose-200']));
});

document.getElementById('timer-save-btn')?.addEventListener('click', async function () {
    const val = parseInt(document.getElementById('timer-input').value) || 0;
    const data = await ajaxPost(TIMER_URL, { time_limit: val });
    if (data?.ok) {
        const old = this.textContent;
        this.textContent = '✓';
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
        const bar = document.getElementById('bar-'+opt);
        if (bar) bar.style.width = pct + '%';
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
        list.innerHTML = '<li class="text-slate-500 text-center py-4 text-xs">Henüz katılımcı yok</li>';
        return;
    }
    const medals = ['🥇','🥈','🥉'];
    const rowCls = [
        'bg-yellow-500/10 border border-yellow-500/30',
        'bg-slate-700/40 border border-slate-700/50',
        'bg-slate-900/60 border border-slate-800/60',
    ];
    scoreboard.forEach((p, i) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2.5 rounded-xl px-3 py-2 text-[11px] ' + (rowCls[i] || rowCls[2]);
        const team = p.team_name ? (' · ' + p.team_name) : '';
        const medal = medals[i] || ((i+1)+'.');
        li.innerHTML = `<span class="w-5 text-center font-black text-sm ${i===0?'text-yellow-400':i===1?'text-slate-300':'text-slate-600'}">${medal}</span>` +
                       `<span class="flex-1 truncate text-slate-200 font-medium">${p.name}${team}</span>` +
                       `<span class="font-black ${i===0?'text-yellow-300':'text-sky-300'}">${p.total_score}p</span>`;
        list.appendChild(li);
    });
}

function updateParticipantScore(participant) {
    const item = document.querySelector(`#participant-list [data-id="${participant.id}"]`);
    const el = item?.querySelector('.score-display');
    if (el) el.textContent = participant.total_score + 'p';
}

/* ─── Remote: aktif soru badge + soru listesi güncelle ─── */
function updateActiveQuestionUI(questionId, questionPosition, questionPoints) {
    const badge = document.getElementById('active-question-badge');
    const label = document.getElementById('active-question-label');

    if (!questionId) {
        if (badge) badge.classList.add('invisible');
        if (label) label.textContent = '';
    } else {
        if (badge) badge.classList.remove('invisible');
        if (label) label.textContent = '#' + questionPosition + ' · ' + questionPoints + 'p';
    }

    // Soru listesinde aktif olanı vurgula
    document.querySelectorAll('.question-item').forEach(btn => {
        const qid = Number(btn.dataset.questionId);
        if (qid === questionId) {
            btn.className = btn.className.replace(/border-slate-\S+|bg-slate-\S+|text-slate-\S+|hover:\S+/g, '');
            btn.classList.add('border-sky-500/60', 'bg-sky-500/10', 'text-sky-200');
        } else {
            btn.classList.remove('border-sky-500/60', 'bg-sky-500/10', 'text-sky-200');
            btn.classList.add('border-slate-800/80', 'bg-slate-900/60', 'text-slate-300',
                              'hover:border-sky-500/40', 'hover:bg-sky-500/5');
        }
    });
}

/* ─── Cevap barlarını sıfırla ─── */
function resetAnswerBars() {
    updateAnswerBars({ A:0, B:0, C:0, D:0 }, 0);
}

function initEcho() {
    const dot = document.getElementById('echo-dot');
    const label = document.getElementById('echo-label');
    try {
        const conn = window.Echo.connector.pusher.connection;
        conn.bind('connected', () => {
            dot?.classList.remove('bg-slate-600','bg-red-500');
            dot?.classList.add('bg-emerald-500');
            if (label) label.textContent = 'Canlı bağlantı aktif';
        });
        conn.bind('disconnected', () => {
            dot?.classList.remove('bg-slate-600','bg-emerald-500');
            dot?.classList.add('bg-red-500');
            if (label) label.textContent = 'Bağlantı kesildi';
        });
    } catch (e) {}

    window.Echo.channel('quiz.session.' + SESSION_CODE)
        /* ─── Quiz durumu güncellendi: remote kendi UI'ını senkronize eder ─── */
        .listen('.QuizStateUpdated', (e) => {
            const q    = e.question;
            const mode = e.mode;

            if (mode === 'lobby' || !q) {
                // Bekleme ekranı — aktif soruyu temizle
                updateActiveQuestionUI(null, null, null);
                resetAnswerBars();
            } else if (mode === 'next' || mode === 'prev' || mode === 'goto' || mode === 'start') {
                // Yeni soru — badge güncelle, barları sıfırla
                updateActiveQuestionUI(q.id, q.position, q.points);
                resetAnswerBars();
            } else if (mode === 'show_results' || mode === 'finish' || mode === 'show_all_results') {
                // Sunum bitti — badge temizle
                updateActiveQuestionUI(null, null, null);
            }
            // reveal: badge değişmez
        })
        .listen('.ParticipantJoined', (e) => {
            const cnt = document.getElementById('participant-count');
            if (cnt && e.total != null) cnt.textContent = e.total;
            if (e.participant) {
                const list = document.getElementById('participant-list');
                if (list && !list.querySelector(`[data-id="${e.participant.id}"]`)) {
                    const li = document.createElement('li');
                    li.setAttribute('data-id', e.participant.id);
                    li.className = 'flex items-center gap-2 rounded-xl bg-slate-800/50 border border-slate-700/50 px-3 py-2 group hover:border-slate-600 transition-colors';
                    const team = e.participant.team_name ? `<span class="text-[9px] text-violet-400 truncate block">${e.participant.team_name}</span>` : '';
                    li.innerHTML = `<div class="min-w-0 flex-1"><span class="truncate block text-slate-200 font-medium">${e.participant.name}</span>${team}</div>` +
                                   `<div class="flex items-center gap-1.5 shrink-0"><span class="font-mono text-sky-400 font-semibold score-display">0p</span>` +
                                   `<button data-participant-id="${e.participant.id}" class="kick-btn hidden group-hover:flex items-center justify-center h-5 w-5 rounded-lg text-[10px] text-rose-400 hover:bg-rose-500/20 border border-rose-500/30 transition-colors" title="Oturumdan çıkar">✕</button></div>`;
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
            if (lockLbl) lockLbl.textContent = e.locked ? '🔓 Cevapları Aç' : '🔒 Kilitle';
            if (lockBtn) {
                lockBtn.classList.remove('border-emerald-500/50','bg-emerald-500/10','text-emerald-200','border-rose-500/50','bg-rose-500/10','text-rose-200');
                lockBtn.classList.add(...(e.locked ? ['border-emerald-500/50','bg-emerald-500/10','text-emerald-200'] : ['border-rose-500/50','bg-rose-500/10','text-rose-200']));
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
