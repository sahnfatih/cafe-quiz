@php($title = 'Analitik')
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">📈 Analitik</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $session->quiz->title }} · Kod: <span class="font-mono text-sky-400">{{ $session->code }}</span></p>
        </div>
        <a href="{{ route('remote.show', $session->admin_token) }}"
           class="text-xs text-slate-400 hover:text-sky-400">← Kumanda</a>
    </header>

    {{-- Genel Özet --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-center">
            <div class="text-3xl font-bold text-sky-400">{{ $overall['total_participants'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Katılımcı</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-center">
            <div class="text-3xl font-bold text-violet-400">{{ $overall['total_answers'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Toplam Cevap</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-center">
            <div class="text-3xl font-bold text-emerald-400">{{ $overall['avg_score'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Ortalama Puan</div>
        </div>
    </div>

    {{-- Soru Başına İstatistikler --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
        <h2 class="text-sm font-semibold">Soru Bazlı Analiz</h2>

        @foreach($stats as $s)
        <div class="border-t border-slate-800/60 pt-4 space-y-2">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <span class="text-[10px] font-bold text-sky-400 mr-2">#{{ $s['question']->position }}</span>
                    <span class="text-xs text-slate-300">{{ $s['question']->text }}</span>
                </div>
                <div class="flex gap-4 text-[11px] shrink-0">
                    <span class="text-emerald-400">✓ {{ $s['correct'] }}</span>
                    <span class="text-rose-400">✗ {{ $s['wrong'] }}</span>
                    <span class="text-slate-400">{{ $s['total'] }} cevap</span>
                    @if($s['avg_ms'] > 0)
                    <span class="text-slate-500">{{ round($s['avg_ms']/1000, 1) }}sn</span>
                    @endif
                </div>
            </div>

            {{-- Doğru/Yanlış Bar --}}
            <div class="flex items-center gap-2 text-[10px]">
                <span class="text-emerald-400 w-8 text-right">{{ $s['correct_pct'] }}%</span>
                <div class="flex-1 h-2 rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full bg-emerald-500 transition-all" style="width: {{ $s['correct_pct'] }}%"></div>
                </div>
                <span class="text-rose-400 w-8">{{ 100 - $s['correct_pct'] }}%</span>
            </div>

            {{-- Cevap Dağılımı --}}
            <div class="grid grid-cols-4 gap-2">
                @foreach(['A','B','C','D'] as $opt)
                @php
                    $n   = $s['distribution'][$opt] ?? 0;
                    $pct = $s['total'] > 0 ? round($n / $s['total'] * 100) : 0;
                    $isCorrect = $s['question']->correct_option === $opt;
                @endphp
                <div class="space-y-1">
                    <div class="flex justify-between text-[10px]">
                        <span class="{{ $isCorrect ? 'text-emerald-400 font-bold' : 'text-slate-400' }}">{{ $opt }}</span>
                        <span class="text-slate-500">{{ $n }}</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full {{ $isCorrect ? 'bg-emerald-500' : 'bg-slate-600' }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </section>

    {{-- Katılımcı Sıralaması --}}
    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-3">
        <h2 class="text-sm font-semibold">🏆 Tam Sıralama</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-800">
                        <th class="text-left pb-2">Sıra</th>
                        <th class="text-left pb-2">İsim</th>
                        <th class="text-left pb-2">Takım</th>
                        <th class="text-right pb-2">Puan</th>
                        <th class="text-right pb-2">Hız Bonusu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($session->participants()->orderByDesc('total_score')->orderByDesc('total_speed_bonus')->get() as $i => $p)
                    <tr class="border-b border-slate-800/40">
                        <td class="py-1.5 {{ $i === 0 ? 'text-yellow-400 font-bold' : 'text-slate-500' }}">{{ $i+1 }}.</td>
                        <td class="py-1.5 text-slate-200">{{ $p->name }}</td>
                        <td class="py-1.5 text-slate-400">{{ $p->team_name ?? '-' }}</td>
                        <td class="py-1.5 text-sky-300 text-right font-semibold">{{ $p->total_score }}p</td>
                        <td class="py-1.5 text-slate-500 text-right">+{{ $p->total_speed_bonus }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex gap-3">
        <a href="{{ route('remote.export', $session->admin_token) }}"
           class="rounded-xl bg-emerald-500/20 border border-emerald-500/50 px-4 py-2 text-xs font-medium text-emerald-200 hover:bg-emerald-500/30">
            📥 CSV İndir
        </a>
        <a href="{{ route('remote.show', $session->admin_token) }}"
           class="rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-2 text-xs font-medium text-slate-300 hover:border-sky-500">
            ← Kumandaya Dön
        </a>
    </div>
</div>
@endsection
