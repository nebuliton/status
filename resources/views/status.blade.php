<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'Systemstatus'])
        <meta property="og:type" content="website">
        <meta property="og:title" content="Nebuliton Status">
        <meta property="og:description" content="Live-Status, Vorfälle und Wartungen für alle überwachten Nebuliton-Dienste.">
        <meta property="og:url" content="{{ route('status.index') }}">
        <meta property="og:image" content="{{ route('status.overview.image') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Nebuliton Status">
        <meta name="twitter:description" content="Live-Status, Vorfälle und Wartungen für alle überwachten Nebuliton-Dienste.">
        <meta name="twitter:image" content="{{ route('status.overview.image') }}">
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 antialiased">
        <livewire:status-page />

        @livewireScripts
    </body>
</html>
