<!DOCTYPE html>
<html lang="tr" class="h-full bg-black">
<head>
    <meta charset="UTF-8">
    <title>Sunum Ekranı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-black text-white">
    {{ $slot }}
    @livewireScripts
    @stack('scripts')
</body>
</html>

