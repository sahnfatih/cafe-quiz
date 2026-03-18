@extends('layouts.app')

@section('content')
<style>
    .quiz-card:hover .quiz-arrow { transform: translateX(4px); }
    .quiz-arrow { transition: transform .2s; }
    .field { @apply block w-full rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-sky-500 transition-colors; }
</style>

{{-- Flash mesaj --}}
@if(session('status'))
<div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3">
    <span class="text-emerald-400 text-lg">✓</span>
    <span class="text-sm text-emerald-200 font-medium">{{ session('status') }}</span>
</div>
@endif

<div class="grid gap-6 lg:grid-cols-[380px,1fr]">

    {{-- ── Yeni Quiz Oluştur ── --}}
    <section class="rounded-2xl border border-slate-700/60 bg-slate-900/70 p-6 shadow-xl shadow-slate-900/40 h-fit">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-sky-500/20 to-violet-500/20
                        border border-sky-500/30 flex items-center justify-center text-xl">🎯</div>
            <div>
                <h1 class="text-base font-black text-white">Yeni Quiz</h1>
                <p class="text-[11px] text-slate-500">Yarışma oluştur</p>
            </div>
        </div>

        <form action="{{ route('admin.quizzes.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Quiz Başlığı *</label>
                <input type="text" name="title" required placeholder="örn. Genel Kültür Gecesi"
                       value="{{ old('title') }}"
                       class="block w-full rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-2.5
                              text-sm text-white placeholder-slate-500 outline-none
                              focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 transition-colors">
                @error('title')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Açıklama</label>
                <textarea name="description" rows="3" placeholder="Opsiyonel açıklama…"
                          class="block w-full rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-2.5
                                 text-sm text-white placeholder-slate-500 outline-none resize-none
                                 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 transition-colors">{{ old('description') }}</textarea>
            </div>
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 rounded-xl
                           bg-gradient-to-r from-sky-500 to-violet-500
                           py-2.5 text-sm font-black text-white shadow-lg shadow-sky-500/20
                           hover:from-sky-400 hover:to-violet-400 active:scale-95 transition-all">
                ✨ Quiz Oluştur
            </button>
        </form>
    </section>

    {{-- ── Quiz Listesi ── --}}
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-black text-white">Quizlerim</h2>
            <span class="text-xs text-slate-500 bg-slate-800/60 border border-slate-700/60 rounded-full px-3 py-1">
                {{ $quizzes->count() }} quiz
            </span>
        </div>

        @forelse($quizzes as $quiz)
        <article class="quiz-card group mb-3 rounded-2xl border border-slate-700/50 bg-slate-900/60
                        hover:border-sky-500/40 hover:bg-slate-900/80 transition-all shadow-sm">
            <div class="flex items-center gap-4 px-5 py-4">
                {{-- İkon --}}
                <div class="shrink-0 h-11 w-11 rounded-xl bg-gradient-to-br from-sky-500/15 to-violet-500/15
                            border border-slate-700/60 group-hover:border-sky-500/30
                            flex items-center justify-center text-xl transition-colors">
                    🎮
                </div>

                {{-- Bilgiler --}}
                <div class="flex-1 min-w-0">
                    <h3 class="font-black text-white text-sm truncate">{{ $quiz->title }}</h3>
                    @if($quiz->description)
                        <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $quiz->description }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-[10px] text-slate-500">
                            📋 {{ $quiz->questions_count }} soru
                        </span>
                        <span class="text-[10px] text-slate-600">·</span>
                        <span class="text-[10px] text-slate-500">
                            📅 {{ $quiz->created_at->format('d.m.Y') }}
                        </span>
                    </div>
                </div>

                {{-- Butonlar --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Düzenle --}}
                    <button onclick="openEditModal({{ $quiz->id }}, @js($quiz->title), @js($quiz->description ?? ''))"
                            class="h-8 w-8 rounded-lg border border-slate-700 bg-slate-800/60
                                   flex items-center justify-center text-slate-400
                                   hover:border-amber-500/50 hover:text-amber-300 transition-colors"
                            title="Düzenle">
                        ✏️
                    </button>
                    {{-- Sil --}}
                    <button onclick="confirmDeleteQuiz({{ $quiz->id }}, @js($quiz->title))"
                            class="h-8 w-8 rounded-lg border border-slate-700 bg-slate-800/60
                                   flex items-center justify-center text-slate-400
                                   hover:border-rose-500/50 hover:text-rose-300 transition-colors"
                            title="Sil">
                        🗑️
                    </button>
                    {{-- Aç --}}
                    <a href="{{ route('admin.quizzes.show', $quiz) }}"
                       class="h-8 px-3 rounded-lg bg-sky-500/10 border border-sky-500/30
                              flex items-center justify-center text-xs font-bold text-sky-300
                              hover:bg-sky-500/20 transition-colors gap-1">
                        Aç <span class="quiz-arrow">→</span>
                    </a>
                </div>
            </div>
        </article>
        @empty
        <div class="rounded-2xl border border-slate-800/60 bg-slate-900/40 p-12 text-center">
            <div class="text-4xl mb-3">🎯</div>
            <p class="text-slate-400 text-sm font-medium">Henüz quiz yok</p>
            <p class="text-slate-600 text-xs mt-1">Sol taraftan ilk quizini oluştur!</p>
        </div>
        @endforelse
    </section>
</div>

{{-- ── Quiz Düzenleme Modali ── --}}
<div id="edit-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border border-slate-700/60 bg-slate-900 shadow-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-black text-white">✏️ Quiz Düzenle</h3>
            <button onclick="closeEditModal()" class="text-slate-500 hover:text-white text-xl leading-none">✕</button>
        </div>
        <form id="edit-form" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Başlık *</label>
                <input type="text" id="edit-title" name="title" required
                       class="block w-full rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-2.5
                              text-sm text-white outline-none focus:border-sky-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Açıklama</label>
                <textarea id="edit-description" name="description" rows="3"
                          class="block w-full rounded-xl border border-slate-700 bg-slate-800/60 px-3 py-2.5
                                 text-sm text-white outline-none resize-none focus:border-sky-500 transition-colors"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditModal()"
                        class="flex-1 rounded-xl border border-slate-700 py-2.5 text-sm text-slate-400
                               hover:text-white transition-colors">
                    İptal
                </button>
                <button type="submit"
                        class="flex-1 rounded-xl bg-sky-500 py-2.5 text-sm font-black text-white
                               hover:bg-sky-400 active:scale-95 transition-all">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Silme Formları (gizli) ── --}}
<div id="delete-forms" class="hidden"></div>

<script>
function openEditModal(id, title, desc) {
    document.getElementById('edit-form').action = '/admin/quizzes/' + id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-description').value = desc;
    const modal = document.getElementById('edit-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeEditModal() {
    const modal = document.getElementById('edit-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

function confirmDeleteQuiz(id, title) {
    if (!confirm('«' + title + '» silinsin mi?\nTüm sorular ve veriler de silinecek!')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/quizzes/' + id;
    form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">
                      <input type="hidden" name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
