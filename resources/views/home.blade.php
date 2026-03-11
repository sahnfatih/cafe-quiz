<!DOCTYPE html>
<html lang="tr" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <title>Cafe Quiz Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-slate-50">
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-3xl grid gap-8 md:grid-cols-[2fr,3fr] items-center">
        <section>
            <h1 class="text-3xl font-semibold mb-3 tracking-tight">
                Cafe Quiz <span class="text-sky-400">Pro</span>
            </h1>
            <p class="text-sm text-slate-300 mb-6 max-w-md">
                Kafende gerçek zamanlı, uzaktan kumandalı quiz etkinlikleri düzenle.
                Admin telefonundan yönet, büyük ekrandan sun, katılımcılar kendi
                telefonlarından katılsın.
            </p>

            <div class="space-y-3">
                <a href="{{ route('login') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-400">
                    Admin Girişi
                </a>
                <form action="{{ route('participant.join', 'XXXX') }}" onsubmit="event.preventDefault(); goJoin();"
                      class="flex gap-2 items-center">
                    <input id="join-code-input" type="text" maxlength="4" placeholder="Oturum Kodu"
                           class="flex-1 rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm uppercase tracking-[0.3em] text-center">
                    <button type="submit"
                            class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-2 text-sm font-medium hover:border-sky-500">
                        Katıl
                    </button>
                </form>
                <p class="text-[11px] text-slate-500">
                    Oturum kodu genelde büyük ekranda veya görevli tarafından paylaşılır.
                </p>
            </div>
        </section>

        <section class="hidden md:block">
            <div class="relative rounded-3xl border border-sky-500/40 bg-sky-500/5 p-4 shadow-2xl shadow-sky-900/30">
                <div class="aspect-video rounded-2xl bg-slate-900/80 border border-slate-700/70 flex items-center justify-center text-slate-400 text-xs">
                    Public Display Önizlemesi
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-slate-300">
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3 py-2">
                        <div class="font-semibold mb-1">Admin</div>
                        <p>Quizleri oluşturur, soruları yönetir ve kumanda ekranından akışı kontrol eder.</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3 py-2">
                        <div class="font-semibold mb-1">Katılımcı</div>
                        <p>QR veya link ile katılır, sadece şıkları görür ve hızlı cevap vererek puan toplar.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    function goJoin() {
        const input = document.getElementById('join-code-input');
        const code = (input.value || '').trim().toUpperCase();
        if (!code) return;
        window.location.href = '/join/' + encodeURIComponent(code);
    }
</script>
</body>
</html>

