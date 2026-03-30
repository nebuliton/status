<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $service['name'].' Status'])
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $service['name'] }} · {{ $service['status']->label() }}">
        <meta property="og:description" content="{{ $snapshot['description'] }}">
        <meta property="og:url" content="{{ request()->fullUrl() }}">
        <meta property="og:image" content="{{ $shareImageUrl }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $service['name'] }} · {{ $service['status']->label() }}">
        <meta name="twitter:description" content="{{ $snapshot['description'] }}">
        <meta name="twitter:image" content="{{ $shareImageUrl }}">
    </head>
    <body class="status-shell min-h-screen text-slate-900 antialiased">
        <div class="status-mesh"></div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="status-card px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="status-icon-shell h-12 w-12 overflow-hidden rounded-2xl border-slate-200 bg-white p-0">
                            <img src="{{ config('services.nebuliton.logo_url') }}" alt="Nebuliton Logo" class="h-full w-full object-cover" loading="eager">
                        </div>

                        <div>
                            <p class="font-display text-xl font-semibold tracking-tight text-slate-950">{{ $service['name'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">Teilbarer Live-Status für diesen einzelnen Dienst.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="status-pill {{ $service['status']->badgeClasses() }}">
                            <span class="h-2 w-2 rounded-full bg-current"></span>
                            {{ $service['status']->label() }}
                        </span>

                        <button
                            type="button"
                            class="status-share-button"
                            onclick="window.nebulitonShareLink('{{ request()->fullUrl() }}', '{{ addslashes($service['name']) }}')"
                        >
                            @include('partials.status.icon', ['name' => 'share', 'class' => 'h-4 w-4'])
                            Link kopieren
                        </button>

                        <a href="{{ route('status.index') }}" class="status-footer-link">
                            Gesamtstatus
                            @include('partials.status.icon', ['name' => 'arrow-up-right', 'class' => 'h-4 w-4'])
                        </a>
                    </div>
                </div>
            </header>

            <main class="mt-6 flex-1">
                <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                    <article class="status-card px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-4">
                                    @include('partials.status.service-icon', [
                                        'icon' => $service['icon'],
                                        'class' => 'h-12 w-12 rounded-2xl bg-white text-slate-500',
                                    ])

                                    <div class="min-w-0">
                                        <h1 class="truncate font-display text-2xl font-semibold text-slate-950">{{ $service['name'] }}</h1>
                                        <p class="mt-1 text-sm text-slate-500">{{ $service['group_name'] ?: 'Nebuliton Dienst' }}</p>
                                    </div>
                                </div>

                                <p class="mt-5 text-sm leading-7 text-slate-600">
                                    {{ $service['last_check_message'] ?: $service['status']->description() }}
                                </p>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span class="status-inline-meta">
                                        @include('partials.status.icon', ['name' => $service['check_type']?->icon() ?? 'activity', 'class' => 'h-4 w-4'])
                                        {{ $service['check_type']?->label() ?? 'Unbekannt' }}
                                    </span>
                                    <span class="status-inline-meta">
                                        @include('partials.status.icon', ['name' => 'activity', 'class' => 'h-4 w-4'])
                                        {{ $service['target'] }}
                                    </span>
                                    <span class="status-inline-meta">
                                        @include('partials.status.icon', ['name' => 'clock', 'class' => 'h-4 w-4'])
                                        {{ $snapshot['lastUpdatedLabel'] }}
                                    </span>
                                    @if ($service['last_response_time_ms'] !== null)
                                        <span class="status-inline-meta">
                                            @include('partials.status.icon', ['name' => 'check', 'class' => 'h-4 w-4'])
                                            {{ $service['last_response_time_ms'] }} ms
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="status-share-stat-box">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Verfügbarkeit</p>
                                <p class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ number_format($service['uptime_percentage'], 2) }}%</p>
                                <p class="mt-1 text-sm text-slate-500">Durchschnitt der letzten 90 Tage</p>
                            </div>
                        </div>
                    </article>

                    <article class="status-card px-5 py-5 sm:px-6">
                        <p class="text-sm font-semibold text-slate-950">Vorschaubild für Teilen und Embeds</p>
                        <p class="mt-1 text-sm text-slate-500">Dieses Bild wird für Open Graph und Discord-Vorschauen gerendert.</p>

                        <div class="mt-4 overflow-hidden rounded-[24px] border border-slate-200 bg-slate-50">
                            <img src="{{ $shareImageUrl }}" alt="Vorschaubild für {{ $service['name'] }}" class="w-full object-cover" loading="eager">
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ $shareImageUrl }}" target="_blank" rel="noreferrer" class="status-share-button">
                                @include('partials.status.icon', ['name' => 'image', 'class' => 'h-4 w-4'])
                                Bild öffnen
                            </a>
                            <button
                                type="button"
                                class="status-share-button"
                                onclick="window.nebulitonShareLink('{{ $shareImageUrl }}', '{{ addslashes($service['name']) }} Bild')"
                            >
                                @include('partials.status.icon', ['name' => 'share', 'class' => 'h-4 w-4'])
                                Bildlink kopieren
                            </button>
                        </div>
                    </article>
                </section>

                <section class="status-card mt-6 px-5 py-5 sm:px-6">
                    <div class="mb-3 flex items-center justify-between text-sm text-slate-500">
                        <span>Letzte 90 Tage</span>
                        <span>{{ $service['history'][0]['date']->translatedFormat('d. M') }} bis {{ last($service['history'])['date']->translatedFormat('d. M') }}</span>
                    </div>

                    <div class="flex gap-[3px]">
                        @foreach ($service['history'] as $day)
                            @php($dayStatus = $day['status'])
                            <div
                                @class([
                                    'status-history-segment group relative h-10 flex-1 min-w-[5px] rounded-md',
                                    $dayStatus?->segmentClasses() ?? 'bg-slate-200/80',
                                ])
                            >
                                <div class="pointer-events-none absolute -top-12 left-1/2 z-10 hidden -translate-x-1/2 whitespace-nowrap rounded-xl bg-slate-950 px-3 py-2 text-xs text-white shadow-lg group-hover:block">
                                    {{ $day['date']->translatedFormat('d. M Y') }} · {{ $dayStatus?->label() ?? 'Keine Daten' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </main>
        </div>

        <div id="status-share-toast" class="status-share-toast hidden">Link kopiert</div>

        <script>
            window.nebulitonShareLink = async function (url, title) {
                try {
                    if (navigator.share) {
                        await navigator.share({ title, url });
                    } else if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(url);
                    } else {
                        const input = document.createElement('input');
                        input.value = url;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        input.remove();
                    }

                    const toast = document.getElementById('status-share-toast');
                    if (toast) {
                        toast.textContent = 'Link kopiert';
                        toast.classList.remove('hidden');
                        toast.classList.add('status-share-toast-visible');
                        window.clearTimeout(window.__nebulitonShareToastTimer);
                        window.__nebulitonShareToastTimer = window.setTimeout(() => {
                            toast.classList.remove('status-share-toast-visible');
                            toast.classList.add('hidden');
                        }, 1800);
                    }
                } catch (error) {
                    console.error(error);
                }
            };
        </script>
    </body>
</html>
