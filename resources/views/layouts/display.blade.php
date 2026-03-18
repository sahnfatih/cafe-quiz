<!DOCTYPE html>
<html lang="tr" class="h-full bg-black">
<head>
    <meta charset="UTF-8">
    <title>Sunum Ekranı · Cafe Quiz</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- CSS + Echo (Alpine YOK — Livewire kendi Alpine'ını yüklüyor) --}}
    @vite(['resources/css/app.css', 'resources/js/echo-setup.js'])
    @livewireStyles
</head>
<body class="h-full bg-black text-white">
    {{ $slot }}
    @livewireScripts
    @stack('scripts')
</body>
</html>
