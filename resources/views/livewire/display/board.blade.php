<div class="h-screen w-screen bg-gradient-to-br from-slate-950 via-slate-900 to-black text-white flex items-center justify-center px-8">
    <div class="max-w-6xl w-full grid gap-8 grid-cols-[3fr,1.4fr] items-stretch">
        {{-- Soru + medya + şıklar --}}
        <main class="rounded-3xl border border-slate-800/80 bg-slate-900/80 px-10 py-8 shadow-2xl shadow-black/70 flex flex-col">
            <div class="flex items-baseline justify-between gap-4 mb-4">
                <div class="text-slate-400 text-xl" wire:key="status-line">
                    @if($mode === 'show_results' || $mode === 'finish')
                        Sonuçlar
                    @elseif($question)
                        {{ $question['points'] ?? 0 }} puanlık soru
                    @else
                        Hazırlanıyor… QR kodu tarayıp yarışmaya katılabilirsiniz.
                    @endif
                </div>
                <div class="font-mono text-sm text-slate-400">
                    Kod: <span class="text-sky-300 text-xl align-middle">{{ $session->code }}</span>
                </div>
            </div>

            <div class="text-3xl font-semibold leading-snug min-h-[4rem]" wire:key="question-text">
                {{ $question['text'] ?? '' }}
            </div>

            {{-- Medya alanı --}}
            <div class="mt-6 min-h-[260px] flex items-center justify-center" wire:key="media-area">
                @if($question && ($question['media_type'] ?? 'none') === 'image' && !empty($question['image_path']))
                    <div class="max-h-72">
                        <img
                            src="{{ asset('storage/'.$question['image_path']) }}"
                            alt=""
                            class="max-h-72 rounded-2xl shadow-lg shadow-black/60 object-contain"
                        >
                    </div>
                @elseif($question && ($question['media_type'] ?? 'none') === 'youtube' && $videoUrl)
                    <div class="w-full aspect-video rounded-2xl overflow-hidden shadow-lg shadow-black/60">
                        <iframe
                            class="w-full h-full"
                            src="{{ $videoUrl }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>
                @endif
            </div>

            {{-- Şıklar --}}
            <div class="mt-8 grid grid-cols-2 gap-4 text-xl font-semibold" wire:key="options-area">
                @foreach (['A','B','C','D'] as $opt)
                    @php
                        $key = 'option_'.strtolower($opt);
                        $text = $question[$key] ?? null;
                    @endphp
                    @if($text)
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3">
                            <span class="text-sky-400 mr-2">{{ $opt }})</span>
                            <span>{{ $text }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Skorboard --}}
            @if($mode === 'show_results' || $mode === 'finish')
                <section class="mt-8 rounded-3xl border border-emerald-500/60 bg-emerald-600/10 px-8 py-6" wire:key="scoreboard">
                    <h2 class="text-2xl font-semibold mb-4 flex items-center gap-3">
                        🏆 İlk 3
                    </h2>
                    <ol class="space-y-2 text-lg">
                        @forelse($topParticipants as $index => $p)
                            <li class="flex items-center justify-between">
                                <span>{{ $index + 1 }}. {{ $p['name'] }}</span>
                                <span class="text-sm text-emerald-300">
                                    {{ $p['total_score'] }} p
                                    <span class="text-emerald-500/80">(+{{ $p['total_speed_bonus'] }} hız)</span>
                                </span>
                            </li>
                        @empty
                            <li class="text-sm text-slate-400">Henüz puanlanmış katılımcı yok.</li>
                        @endforelse
                    </ol>
                </section>
            @endif
        </main>

        {{-- Sağ tarafta QR paneli --}}
        <aside class="rounded-3xl border border-slate-800/80 bg-slate-950/80 px-6 py-6 shadow-2xl shadow-black/80 flex flex-col items-center justify-center">
            <div class="text-sm text-slate-300 mb-3 text-center">
                Telefonunuzla tarayıp yarışmaya katılın
            </div>
            <div class="rounded-2xl border border-slate-700 bg-slate-900/80 p-3 mb-4">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data={{ urlencode(route('participant.join', $session->code)) }}"
                    alt="Katılım QR"
                    class="h-64 w-64"
                >
            </div>
            <div class="font-mono text-xs text-sky-300 text-center break-all max-w-xs">
                {{ route('participant.join', $session->code) }}
            </div>
        </aside>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Livewire || !window.Echo) return;

            const componentId = @json($this->getId());
            const channelName = 'quiz.session.{{ $session->code }}';

            window.Echo.channel(channelName)
                .listen('.QuizStateUpdated', (e) => {
                    const component = window.Livewire.find(componentId);
                    if (component) {
                        component.call('handleQuizUpdate', e);
                    }
                });
        });
    </script>
@endpush


