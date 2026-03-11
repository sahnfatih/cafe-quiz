@php($title = 'Kumanda')
@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[2fr,3fr]">
        {{-- Oturum & QR --}}
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-900/40 space-y-4">
            <header class="space-y-1">
                <h1 class="text-base font-semibold">Oturum Kodun</h1>
                <p class="text-xs text-slate-400">
                    Kodu değişmez; QR’ı ekrana yansıt, müşteriler telefonlarıyla tarayıp hızlıca katılsın.
                </p>
            </header>

            <div class="flex items-center gap-4">
                <div class="shrink-0 rounded-2xl border border-slate-700 bg-slate-950/80 p-2">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(route('participant.join', $session->code)) }}"
                        alt="Katılım QR"
                        class="h-40 w-40"
                    >
                </div>
                <div class="space-y-3 text-xs text-slate-300">
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-slate-500 mb-1">Katılım Linki</div>
                        <button type="button"
                                onclick="navigator.clipboard.writeText('{{ route('participant.join', $session->code) }}')"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-left font-mono text-[11px] text-sky-300 hover:border-sky-500">
                            {{ route('participant.join', $session->code) }}
                        </button>
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-slate-500 mb-1">Sunum Ekranı</div>
                        <button type="button"
                                onclick="navigator.clipboard.writeText('{{ route('display.show', $session->code) }}')"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-left font-mono text-[11px] text-slate-300 hover:border-sky-500">
                            {{ route('display.show', $session->code) }}
                        </button>
                    </div>
                    <div class="flex gap-4 text-[11px] text-slate-400 pt-1">
                        <span>Katılımcı: <span id="participant-count" class="text-sky-300 font-semibold">{{ $session->participants->count() }}</span></span>
                        <span>Soru: <span class="text-sky-300 font-semibold">{{ $session->quiz->questions->count() }}</span></span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Kontroller & Soru listesi --}}
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-900/40 space-y-4">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold mb-1">Akışı Yönet</h2>
                    <p class="text-xs text-slate-400">
                        {{ $session->quiz->title }} · Oturum kodu:
                        <span class="font-mono text-sky-400">{{ $session->code }}</span>
                    </p>
                </div>
                @if($currentQuestion)
                    <div class="text-right text-[11px] text-slate-400">
                        <div>Aktif soru:</div>
                        <div class="text-sky-300 font-semibold">
                            #{{ $currentQuestion->position }} · {{ $currentQuestion->points }}p
                        </div>
                    </div>
                @endif
            </header>

            <div class="grid grid-cols-3 gap-3">
                <form method="post" action="{{ route('remote.action', $session->admin_token) }}">
                    @csrf
                    <input type="hidden" name="mode" value="prev">
                    <button type="submit"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-3 text-xs font-medium hover:border-sky-500">
                        ← Önceki
                    </button>
                </form>

                <form method="post" action="{{ route('remote.action', $session->admin_token) }}">
                    @csrf
                    <input type="hidden" name="mode" value="next">
                    <button type="submit"
                            class="w-full rounded-xl bg-sky-500 px-3 py-3 text-xs font-semibold text-white hover:bg-sky-400">
                        Sıradaki Soru →
                    </button>
                </form>

                <form method="post" action="{{ route('remote.action', $session->admin_token) }}">
                    @csrf
                    <input type="hidden" name="mode" value="show_results">
                    <button type="submit"
                            class="w-full rounded-xl border border-emerald-500/70 bg-emerald-500/10 px-3 py-3 text-xs font-medium text-emerald-200 hover:bg-emerald-500/20">
                        Sonuçları Göster
                    </button>
                </form>
            </div>

            <div class="pt-3 border-t border-slate-800/70">
                <div class="flex items-center justify-between mb-2 text-xs text-slate-400">
                    <span>Sorulara hızlı geçiş:</span>
                    <span>Katılımcılar:</span>
                </div>
                <div class="max-h-52 overflow-y-auto space-y-1 pr-1">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            @foreach($session->quiz->questions as $q)
                                <form method="post" action="{{ route('remote.action', $session->admin_token) }}">
                                    @csrf
                                    <input type="hidden" name="mode" value="goto">
                                    <input type="hidden" name="question_id" value="{{ $q->id }}">
                                    <button type="submit"
                                            class="w-full text-left rounded-lg border px-3 py-2 text-[11px]
                                                   {{ $currentQuestion && $currentQuestion->id === $q->id
                                                        ? 'border-sky-500 bg-sky-500/10 text-sky-100'
                                                        : 'border-slate-800 bg-slate-900/70 text-slate-300 hover:border-sky-500' }}">
                                        <span class="font-semibold mr-1">#{{ $q->position }}</span>
                                        <span class="text-slate-400">({{ $q->points }}p)</span>
                                        <span class="ml-2">{{ \Illuminate\Support\Str::limit($q->text, 40) }}</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <div>
                            <div class="text-[11px] text-slate-400 mb-1">Anlık katılımcılar</div>
                            <ul id="participant-list" class="space-y-1 text-[11px] text-slate-300">
                                @foreach($session->participants as $p)
                                    <li data-id="{{ $p->id }}" class="flex items-center justify-between rounded-md bg-slate-900/70 px-2 py-1">
                                        <span class="truncate">{{ $p->name }}</span>
                                        <span class="ml-2 text-slate-500">{{ $p->total_score }}p</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        const remoteSessionCode = @json($session->code);

        if (window.Echo) {
            window.Echo.channel('quiz.session.' + remoteSessionCode)
                .listen('.ParticipantJoined', (e) => {
                    const countEl = document.getElementById('participant-count');
                    const listEl = document.getElementById('participant-list');
                    if (countEl && typeof e.total !== 'undefined') {
                        countEl.textContent = e.total;
                    }
                    if (listEl && e.participant) {
                        const existing = listEl.querySelector('[data-id="' + e.participant.id + '"]');
                        if (!existing) {
                            const li = document.createElement('li');
                            li.setAttribute('data-id', e.participant.id);
                            li.className = 'flex items-center justify-between rounded-md bg-slate-900/70 px-2 py-1';
                            li.innerHTML = '<span class="truncate">' + e.participant.name + '</span>' +
                                '<span class="ml-2 text-slate-500">' + e.participant.total_score + 'p</span>';
                            listEl.appendChild(li);
                        }
                    }
                });
        }
    </script>
@endsection

