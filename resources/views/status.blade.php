<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'Systemstatus'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 antialiased">
        <livewire:status-page />

        @livewireScripts
    </body>
</html>
