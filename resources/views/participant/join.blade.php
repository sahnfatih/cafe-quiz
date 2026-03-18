@php($title = 'Katıl')
@extends('layouts.app')

@section('content')
<div class="max-w-sm mx-auto">
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg space-y-5">
        <header class="text-center space-y-1">
            <div class="text-3xl">🎮</div>
            <h1 class="text-lg font-bold">Yarışmaya Katıl</h1>
            <p class="text-xs text-slate-400">
                Kod: <span class="font-mono text-sky-400 text-sm">{{ $session->code }}</span>
                &nbsp;·&nbsp; {{ $session->quiz->title }}
            </p>
        </header>

        <form method="post" action="{{ route('participant.register', $session->code) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium mb-1">İsim / Takma Ad <span class="text-rose-400">*</span></label>
                <input type="text" name="name" required maxlength="50"
                       placeholder="Ör: Ahmet K."
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:border-sky-500 outline-none">
                @error('name')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium mb-1 flex items-center gap-1">
                    Takım Adı
                    <span class="text-[10px] text-slate-500 font-normal">(isteğe bağlı)</span>
                </label>
                <input type="text" name="team_name" maxlength="50"
                       placeholder="Ör: Masa 5, Team Alpha…"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:border-violet-500 outline-none">
                <p class="text-[10px] text-slate-500 mt-1">Aynı takım adını giren oyuncular birlikte görünür.</p>
                @error('team_name')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-400 active:scale-95 transition-transform">
                Katıl →
            </button>
        </form>

        <p class="text-center text-[10px] text-slate-600">
            {{ $session->participants()->count() }} kişi katıldı
        </p>
    </section>
</div>
@endsection
