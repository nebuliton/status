<section class="space-y-5">
    <div class="status-card px-4 py-4 sm:px-5">
        <div class="flex flex-wrap items-center gap-3">
            <span class="status-inline-meta">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ $snapshot['statusBreakdown']['operational'] }} betriebsbereit
            </span>
            <span class="status-inline-meta">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                {{ $snapshot['statusBreakdown']['degraded'] }} beeinträchtigt
            </span>
            <span class="status-inline-meta">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                {{ $snapshot['statusBreakdown']['down'] }} Ausfall
            </span>
            <span class="status-inline-meta">
                @include('partials.status.icon', ['name' => 'activity', 'class' => 'h-4 w-4'])
                Ø Verfügbarkeit {{ number_format($snapshot['averageUptime'], 2) }} %
            </span>
            <span class="status-inline-meta">
                @include('partials.status.icon', ['name' => 'incident', 'class' => 'h-4 w-4'])
                {{ $activeIncidents->count() }} aktive Vorfälle
            </span>
        </div>
    </div>

    @forelse ($snapshot['groups'] as $group)
        <details class="status-card overflow-hidden" @if ($loop->first) open @endif>
            <summary class="cursor-pointer list-none px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-950 sm:text-xl">{{ $group['name'] }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $group['services']->count() }} überwachte Dienste in diesem Bereich.</p>
                    </div>

                    <span class="status-inline-meta">{{ $group['services']->count() }} Dienste</span>
                </div>
            </summary>

            <div class="status-separator"></div>

            <div class="space-y-3 px-4 py-4 sm:px-6 sm:py-5">
                @foreach ($group['services'] as $service)
                    <article class="status-card-subtle px-4 py-4 sm:px-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    @include('partials.status.service-icon', [
                                        'icon' => $service['icon'],
                                        'class' => 'h-10 w-10 rounded-2xl bg-white text-slate-500',
                                    ])

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <a href="{{ route('status.service.show', ['service' => $service['slug']]) }}" class="truncate text-base font-semibold text-slate-950 transition hover:text-brand-600">
                                                {{ $service['name'] }}
                                            </a>
                                            <span class="status-pill {{ $service['status']->badgeClasses() }}">
                                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                                {{ $service['status']->label() }}
                                            </span>
                                            <button
                                                type="button"
                                                class="status-share-button status-share-button-compact"
                                                onclick="window.nebulitonShareLink('{{ route('status.service.show', ['service' => $service['slug']]) }}', '{{ addslashes($service['name']) }}')"
                                                title="Dienststatus teilen"
                                            >
                                                @include('partials.status.icon', ['name' => 'share', 'class' => 'h-4 w-4'])
                                            </button>
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="status-inline-meta">
                                                @include('partials.status.icon', ['name' => $service['check_type']?->icon() ?? 'activity', 'class' => 'h-4 w-4'])
                                                {{ $service['check_type']?->label() ?? 'Unbekannt' }}
                                            </span>

                                            <span class="status-inline-meta">
                                                @include('partials.status.icon', ['name' => 'activity', 'class' => 'h-4 w-4'])
                                                {{ $service['target'] }}
                                            </span>

                                            @if ($service['check_enabled'])
                                                @if ($service['last_checked_at'])
                                                    <span class="status-inline-meta">
                                                        @include('partials.status.icon', ['name' => 'clock', 'class' => 'h-4 w-4'])
                                                        {{ $service['last_checked_at']->diffForHumans() }}
                                                    </span>
                                                @endif

                                                @if ($service['last_response_time_ms'] !== null)
                                                    <span class="status-inline-meta">
                                                        @include('partials.status.icon', ['name' => 'check', 'class' => 'h-4 w-4'])
                                                        {{ $service['last_response_time_ms'] }} ms
                                                    </span>
                                                @endif
                                            @else
                                                <span class="status-inline-meta text-slate-400">
                                                    @include('partials.status.icon', ['name' => 'clock', 'class' => 'h-4 w-4'])
                                                    Monitoring pausiert
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-4 text-sm leading-6 text-slate-500">
                                    {{ $service['last_check_message'] ?: $service['status']->description() }}
                                </p>
                            </div>

                            <div class="min-w-[8.5rem] rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-left shadow-sm xl:text-right">
                                <p class="text-xs font-semibold text-slate-400">Verfügbarkeit</p>
                                <p class="mt-1 font-display text-2xl font-semibold text-slate-950">{{ number_format($service['uptime_percentage'], 2) }}%</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="mb-2 flex items-center justify-between text-xs text-slate-400">
                                <span>Letzte 90 Tage</span>
                                <span>{{ $service['history'][0]['date']->translatedFormat('d. M') }} bis {{ last($service['history'])['date']->translatedFormat('d. M') }}</span>
                            </div>

                            @php($historyCount = count($service['history']))
                            <div class="flex gap-[3px]">
                                @foreach ($service['history'] as $day)
                                    @php($dayStatus = $day['status'])
                                    @php($tooltipPosition = $loop->index <= 1 ? 'left' : ($loop->index >= $historyCount - 2 ? 'right' : 'center'))

                                    <div
                                        @class([
                                            'status-history-segment group relative h-7 flex-1 min-w-[4px] rounded-sm',
                                            $dayStatus?->segmentClasses() ?? 'bg-slate-200/80',
                                        ])
                                        title="{{ $day['date']->translatedFormat('d. M Y') }} · {{ $dayStatus?->label() ?? 'Keine Daten' }}"
                                    >
                                        <div
                                            @class([
                                                'status-history-tooltip',
                                                'status-history-tooltip-left' => $tooltipPosition === 'left',
                                                'status-history-tooltip-center' => $tooltipPosition === 'center',
                                                'status-history-tooltip-right' => $tooltipPosition === 'right',
                                            ])
                                        >
                                            {{ $day['date']->translatedFormat('d. M Y') }} · {{ $dayStatus?->label() ?? 'Keine Daten' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </details>
    @empty
        <div class="status-card px-5 py-10 text-center text-slate-500">
            Es wurden noch keine Dienste konfiguriert.
        </div>
    @endforelse
</section>
