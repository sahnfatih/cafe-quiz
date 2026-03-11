@php($title = 'Quizler')
@extends('layouts.app')

@section('content')
    <div class="grid gap-8 md:grid-cols-[2fr,3fr]">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/40">
            <h1 class="text-lg font-semibold mb-4">Yeni Quiz Oluştur</h1>
            <form action="{{ route('admin.quizzes.store') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Başlık</label>
                    <input type="text" name="title" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                           required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Açıklama</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm"></textarea>
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-400">
                    Kaydet
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/40">
            <h2 class="text-lg font-semibold mb-4">Quiz Listesi</h2>
            <div class="space-y-2">
                @forelse($quizzes as $quiz)
                    <a href="{{ route('admin.quizzes.show', $quiz) }}"
                       class="block rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3 hover:border-sky-500 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium">{{ $quiz->title }}</div>
                                <div class="text-xs text-slate-400">
                                    {{ $quiz->questions_count ?? $quiz->questions()->count() }} soru
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">Henüz quiz yok, yukarıdan oluşturabilirsin.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

