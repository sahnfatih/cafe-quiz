@php($title = $quiz->title)
@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold mb-1">{{ $quiz->title }}</h1>
                    <p class="text-sm text-slate-400 max-w-2xl">{{ $quiz->description }}</p>
                </div>
                <form action="{{ route('admin.quizzes.startSession', $quiz) }}" method="post">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-400">
                        Sunumu Başlat
                    </button>
                </form>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-[3fr,2fr]">
            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/40">
                <h2 class="text-lg font-semibold mb-4">Sorular</h2>
                <div class="space-y-3">
                    @forelse($quiz->questions as $question)
                        <article class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-slate-400 mb-1">
                                        #{{ $question->position }} · {{ $question->points }} puan
                                    </div>
                                    <p class="font-medium mb-2">{{ $question->text }}</p>
                                    <ul class="text-xs space-y-1">
                                        <li><span class="font-semibold">A)</span> {{ $question->option_a }}</li>
                                        <li><span class="font-semibold">B)</span> {{ $question->option_b }}</li>
                                        @if($question->option_c)
                                            <li><span class="font-semibold">C)</span> {{ $question->option_c }}</li>
                                        @endif
                                        @if($question->option_d)
                                            <li><span class="font-semibold">D)</span> {{ $question->option_d }}</li>
                                        @endif
                                    </ul>
                                    <div class="mt-2 text-[11px] text-slate-400">
                                        Doğru cevap: <span class="font-semibold">{{ $question->correct_option }}</span>
                                        @if($question->media_type === 'image')
                                            · Görsel
                                        @elseif($question->media_type === 'youtube')
                                            · YouTube ({{ $question->youtube_start }}s - {{ $question->youtube_end }}s)
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-400">Bu quiz için henüz soru eklenmemiş.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/40">
                <h2 class="text-lg font-semibold mb-4">Yeni Soru</h2>
                <form action="{{ route('admin.quizzes.questions.store', $quiz) }}" method="post" enctype="multipart/form-data"
                      class="space-y-4 text-sm">
                    @csrf
                    <div>
                        <label class="block text-xs mb-1">Soru Metni</label>
                        <textarea name="text" rows="3" required
                                  class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs mb-1">A Şıkkı</label>
                            <input type="text" name="option_a" required
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">B Şıkkı</label>
                            <input type="text" name="option_b" required
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">C Şıkkı</label>
                            <input type="text" name="option_c"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">D Şıkkı</label>
                            <input type="text" name="option_d"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs mb-1">Doğru Şık</label>
                            <select name="correct_option"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Puan</label>
                            <input type="number" name="points" value="100" min="1"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs mb-1">Medya Tipi</label>
                        <select name="media_type"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                            <option value="none">Yok</option>
                            <option value="image">Görsel</option>
                            <option value="youtube">YouTube</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <label class="block text-xs mb-1">Görsel (isteğe bağlı)</label>
                            <input type="file" name="image" accept="image/*"
                                   class="w-full text-xs text-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">YouTube URL</label>
                            <input type="url" name="youtube_url"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs mb-1">Başlangıç (saniye)</label>
                                <input type="number" name="youtube_start" min="0"
                                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs mb-1">Bitiş (saniye)</label>
                                <input type="number" name="youtube_end" min="0"
                                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm">
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="mt-2 inline-flex items-center justify-center rounded-xl bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-400">
                        Soruyu Ekle
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection

