<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>{{ $session->quiz->title }} · Katıl</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes float-blob  { 0%,100%{transform:translate(0,0) scale(1)}   50%{transform:translate(20px,-25px) scale(1.06)} }
        @keyframes float-blob2 { 0%,100%{transform:translate(0,0) scale(1)}   50%{transform:translate(-18px,22px) scale(.94)} }
        @keyframes badge-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(56,189,248,.4)}  70%{box-shadow:0 0 0 12px rgba(56,189,248,0)} }
        @keyframes card-glow   { 0%,100%{box-shadow:0 0 30px rgba(56,189,248,.12),0 25px 50px rgba(0,0,0,.6)}
                                 50%{box-shadow:0 0 50px rgba(56,189,248,.22),0 0 80px rgba(139,92,246,.1),0 25px 50px rgba(0,0,0,.6)} }
        @keyframes icon-float  { 0%,100%{transform:translateY(0) rotate(-3deg)} 50%{transform:translateY(-8px) rotate(3deg)} }
        .blob      { position:fixed; border-radius:50%; filter:blur(80px); pointer-events:none; }
        .blob-1    { animation: float-blob  9s ease-in-out infinite; }
        .blob-2    { animation: float-blob2 11s ease-in-out infinite; }
        .blob-3    { animation: float-blob  14s ease-in-out infinite reverse; }
        .code-badge{ animation: badge-pulse 2.5s ease-in-out infinite; }
        .card      { animation: card-glow  3.5s ease-in-out infinite; }
        .icon-anim { animation: icon-float 3s ease-in-out infinite; }
        * { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-white overflow-x-hidden">

    {{-- Arkaplan dekoratif bloblar --}}
    <div class="blob blob-1 w-72 h-72 bg-sky-600/20   top-[-8%]  right-[-8%]"></div>
    <div class="blob blob-2 w-96 h-96 bg-violet-700/15 bottom-[-12%] left-[-10%]"></div>
    <div class="blob blob-3 w-56 h-56 bg-emerald-600/10 top-[45%] left-[55%]"></div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-5 py-10">

        {{-- Logo alanı --}}
        <div class="mb-7 text-center space-y-3">
            <div class="icon-anim inline-flex items-center justify-center h-20 w-20 rounded-3xl
                        bg-gradient-to-br from-sky-500/25 to-violet-500/25
                        border border-sky-500/30 shadow-2xl shadow-sky-900/30 text-5xl">
                🎯
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold tracking-[0.2em] uppercase mb-1">Cafe Quiz Pro</p>
                <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-sky-300 to-violet-300 leading-tight px-2">
                    {{ $session->quiz->title }}
                </h1>
            </div>
            <div class="code-badge inline-flex items-center gap-2.5 rounded-2xl
                        bg-sky-500/10 border border-sky-500/30 px-5 py-2">
                <span class="text-xs text-slate-400 font-medium">Katılım Kodu</span>
                <span class="font-mono font-black text-sky-300 text-2xl tracking-[0.25em]">{{ $session->code }}</span>
            </div>
        </div>

        {{-- Giriş Kartı --}}
        <div class="card w-full max-w-sm rounded-3xl border border-slate-700/50
                    bg-slate-900/80 backdrop-blur-xl p-6 shadow-2xl">

            @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 px-4 py-3">
                @foreach ($errors->all() as $error)
                <p class="text-xs text-rose-300">⚠️ {{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="post" action="{{ route('participant.register', $session->code) }}" class="space-y-4">
                @csrf

                {{-- İsim --}}
                <div class="space-y-1.5">
                    <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <span>👤</span> İsim / Takma Ad
                        <span class="text-rose-400 ml-auto font-normal text-sm normal-case">Zorunlu</span>
                    </label>
                    <input type="text" name="name" required maxlength="50"
                           placeholder="Ör: Ahmet K."
                           value="{{ old('name') }}"
                           autocomplete="off" autocorrect="off" spellcheck="false"
                           class="w-full rounded-2xl border border-slate-600 bg-slate-800/80 px-4 py-3.5
                                  text-sm font-semibold text-white placeholder:text-slate-600
                                  focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20
                                  focus:bg-slate-800 outline-none transition-all">
                </div>

                {{-- Takım --}}
                <div class="space-y-1.5">
                    <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <span>👥</span> Takım Adı
                        <span class="text-slate-600 ml-auto font-normal text-[10px] normal-case">isteğe bağlı</span>
                    </label>
                    <input type="text" name="team_name" maxlength="50"
                           placeholder="Ör: Masa 5, Team Alpha…"
                           value="{{ old('team_name') }}"
                           autocomplete="off"
                           class="w-full rounded-2xl border border-slate-600 bg-slate-800/80 px-4 py-3.5
                                  text-sm font-semibold text-white placeholder:text-slate-600
                                  focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20
                                  outline-none transition-all">
                    <p class="text-[10px] text-slate-600 pl-1">Aynı takım adını giren oyuncular birlikte görünür</p>
                </div>

                {{-- Buton --}}
                <button type="submit"
                        class="w-full rounded-2xl bg-gradient-to-r from-sky-500 to-violet-500
                               px-4 py-4 text-sm font-black text-white
                               hover:from-sky-400 hover:to-violet-400
                               active:scale-95 transition-all duration-200
                               shadow-xl shadow-sky-500/25 mt-1">
                    Oyuna Gir &nbsp;🚀
                </button>
            </form>
        </div>

        {{-- Alt bilgi --}}
        <div class="mt-6 flex items-center gap-3 text-xs text-slate-600">
            <div class="h-px flex-1 bg-slate-800"></div>
            <span class="flex items-center gap-1.5">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ $session->participants()->count() }} oyuncu katıldı
            </span>
            <div class="h-px flex-1 bg-slate-800"></div>
        </div>

    </div>
</body>
</html>
