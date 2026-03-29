<section class="grid gap-5 lg:grid-cols-2">
    <div class="status-card px-5 py-5 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="status-icon-shell">
                    @include('partials.status.icon', ['name' => 'maintenance', 'class' => 'h-5 w-5'])
                </div>
                <div>
                    <h2 class="font-display text-xl font-semibold text-slate-950">Kommende Wartungen</h2>
                    <p class="mt-1 text-sm text-slate-500">Geplante Arbeiten mit möglicher Auswirkung auf die Plattform.</p>
                </div>
            </div>

            <span class="status-inline-meta">{{ $upcomingMaintenances->count() }}</span>
        </div>

        <div class="mt-5 space-y-4">
            @forelse ($upcomingMaintenances as $maintenance)
                <article class="status-card-subtle px-4 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-950">{{ $maintenance->title }}</h3>
                        <span class="status-pill {{ $maintenance->status->badgeClasses() }}">{{ $maintenance->status->label() }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $maintenance->description }}</p>
                    <p class="mt-4 text-xs text-slate-400">Geplant für {{ $maintenance->scheduled_at->translatedFormat('d. M Y · H:i') }}</p>
                </article>
            @empty
                <div class="status-card-subtle px-4 py-5 text-sm text-slate-500">
                    Derzeit sind keine kommenden Wartungsfenster geplant.
                </div>
            @endforelse
        </div>
    </div>

    <div class="status-card px-5 py-5 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="status-icon-shell">
                    @include('partials.status.icon', ['name' => 'clock', 'class' => 'h-5 w-5'])
                </div>
                <div>
                    <h2 class="font-display text-xl font-semibold text-slate-950">Abgeschlossene Wartungen</h2>
                    <p class="mt-1 text-sm text-slate-500">Vergangene Wartungsfenster im Verlauf.</p>
                </div>
            </div>

            <span class="status-inline-meta">{{ $completedMaintenances->count() }}</span>
        </div>

        <div class="mt-5 space-y-4">
            @forelse ($completedMaintenances as $maintenance)
                <article class="status-card-subtle px-4 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-950">{{ $maintenance->title }}</h3>
                        <span class="status-pill {{ $maintenance->status->badgeClasses() }}">{{ $maintenance->status->label() }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $maintenance->description }}</p>
                    <p class="mt-4 text-xs text-slate-400">Durchgeführt {{ $maintenance->scheduled_at->translatedFormat('d. M Y · H:i') }}</p>
                </article>
            @empty
                <div class="status-card-subtle px-4 py-5 text-sm text-slate-500">
                    Es wurden noch keine abgeschlossenen Wartungen erfasst.
                </div>
            @endforelse
        </div>
    </div>
</section>
