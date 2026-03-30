<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php($shareSnapshot = app(\App\Services\Status\StatusPageService::class)->overviewShareSnapshot())
        @php($shareImageUrl = route('status.overview.image', ['v' => $shareSnapshot['shareHash']]))
        @include('partials.head', ['title' => 'Systemstatus'])
        <meta property="og:type" content="website">
        <meta property="og:title" content="Nebuliton Status">
        <meta property="og:description" content="Live-Status, Vorfälle und Wartungen für alle überwachten Nebuliton-Dienste.">
        <meta property="og:url" content="{{ route('home') }}">
        <meta property="og:image" content="{{ $shareImageUrl }}">
        <meta property="og:image:secure_url" content="{{ $shareImageUrl }}">
        <meta property="og:image:type" content="image/svg+xml">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Nebuliton Status">
        <meta name="twitter:description" content="Live-Status, Vorfälle und Wartungen für alle überwachten Nebuliton-Dienste.">
        <meta name="twitter:image" content="{{ $shareImageUrl }}">
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 antialiased">
        <livewire:status-page />

        @livewireScripts
    </body>
</html>
