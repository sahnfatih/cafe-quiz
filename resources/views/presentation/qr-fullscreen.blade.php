<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Katılım QR — {{ $session->code }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { cursor: none; }
        @keyframes pulse-ring {
            0%   { transform: scale(1); opacity: 0.4; }
            50%  { transform: scale(1.08); opacity: 0.15; }
            100% { transform: scale(1); opacity: 0.4; }
        }
        .pulse-ring { animation: pulse-ring 2.5s ease-in-out infinite; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-black text-white flex items-center justify-center">

<div class="text-center space-y-8 px-8">
    {{-- Başlık --}}
    <div class="space-y-2">
        <h1 class="text-4xl font-bold tracking-tight">Yarışmaya Katıl!</h1>
        <p class="text-slate-400 text-lg">QR kodu tara veya kodu gir</p>
    </div>

    {{-- QR Kodu --}}
    <div class="relative inline-block">
        {{-- Animasyonlu halka --}}
        <div class="absolute inset-0 rounded-3xl border-4 border-sky-500/40 pulse-ring"></div>
        <div class="relative rounded-3xl border-2 border-sky-500/60 bg-white p-4 shadow-2xl shadow-sky-500/20">
            <img
                src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data={{ urlencode(route('participant.join', $session->code)) }}"
                alt="Katılım QR"
                class="h-80 w-80"
            >
        </div>
    </div>

    {{-- Oturum kodu --}}
    <div class="space-y-2">
        <p class="text-slate-400 text-sm uppercase tracking-widest">Oturum Kodu</p>
        <div class="text-7xl font-black tracking-[0.3em] text-sky-400 font-mono">
            {{ $session->code }}
        </div>
    </div>

    {{-- URL --}}
    <div class="text-slate-500 text-base font-mono">
        {{ rtrim(config('app.url'), '/') }}/join/{{ $session->code }}
    </div>

    {{-- Katılımcı sayısı (canlı) --}}
    <div class="text-slate-400 text-sm">
        Katılımcı: <span id="pcount" class="text-sky-300 font-semibold">{{ $session->participants()->count() }}</span>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        if (!window.Echo) return;
        window.Echo.channel('quiz.session.{{ $session->code }}')
            .listen('.ParticipantJoined', function (e) {
                const el = document.getElementById('pcount');
                if (el && e.total != null) el.textContent = e.total;
            });
    });
</script>
</body>
</html>
