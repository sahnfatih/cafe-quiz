@php($title = 'Katıl')
@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg shadow-slate-900/40 space-y-4">
            <header>
                <h1 class="text-lg font-semibold mb-1">Yarışmaya Katıl</h1>
                <p class="text-xs text-slate-400">Oturum kodu: <span class="font-mono text-sky-400">{{ $session->code }}</span></p>
            </header>
            <form method="post" action="{{ route('participant.register', $session->code) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs mb-1">İsim / Takma Ad</label>
                    <input type="text" name="name" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
                </div>
                <button type="submit"
                        class="w-full rounded-xl bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-400">
                    Katıl
                </button>
            </form>
        </section>
    </div>
@endsection

