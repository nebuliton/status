<section class="grid gap-5 lg:grid-cols-2">
    <div class="status-card px-5 py-5 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="status-icon-shell">
                    @include('partials.status.icon', ['name' => 'incident', 'class' => 'h-5 w-5'])
                </div>
                <div>
                    <h2 class="font-display text-xl font-semibold text-slate-950">Aktive Vorfälle</h2>
                    <p class="mt-1 text-sm text-slate-500">Laufende Störungen und laufende Statusmeldungen.</p>
                </div>
            </div>

            <span class="status-inline-meta">{{ $activeIncidents->count() }}</span>
        </div>

        <div class="mt-5 space-y-4">
            @forelse ($activeIncidents as $incident)
                <article class="status-card-subtle px-4 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-950">{{ $incident->title }}</h3>
                        <span class="status-pill {{ $incident->status->badgeClasses() }}">{{ $incident->status->label() }}</span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $incident->description }}</p>

                    <div class="mt-5 space-y-3 border-l border-slate-200 pl-4">
                        @foreach ($incident->updates as $update)
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="status-pill {{ $update->status->badgeClasses() }}">{{ $update->status->label() }}</span>
                                    <span class="text-xs text-slate-400">{{ $update->created_at->translatedFormat('d. M Y · H:i') }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $update->message }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="status-card-subtle px-4 py-5 text-sm text-slate-500">
                    Derzeit sind keine aktiven Vorfälle offen.
                </div>
            @endforelse
        </div>
    </div>

    <div class="status-card px-5 py-5 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="status-icon-shell">
                    @include('partials.status.icon', ['name' => 'check', 'class' => 'h-5 w-5'])
                </div>
                <div>
                    <h2 class="font-display text-xl font-semibold text-slate-950">Gelöste Vorfälle</h2>
                    <p class="mt-1 text-sm text-slate-500">Bereits abgeschlossene Ereignisse im Rückblick.</p>
                </div>
            </div>

            <span class="status-inline-meta">{{ $resolvedIncidents->count() }}</span>
        </div>

        <div class="mt-5 space-y-4">
            @forelse ($resolvedIncidents as $incident)
                <article class="status-card-subtle px-4 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-950">{{ $incident->title }}</h3>
                        <span class="status-pill {{ $incident->status->badgeClasses() }}">{{ $incident->status->label() }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($incident->description, 180) }}</p>
                    <p class="mt-4 text-xs text-slate-400">Eröffnet {{ $incident->created_at->translatedFormat('d. M Y · H:i') }}</p>
                </article>
            @empty
                <div class="status-card-subtle px-4 py-5 text-sm text-slate-500">
                    Es wurden noch keine gelösten Vorfälle veröffentlicht.
                </div>
            @endforelse
        </div>
    </div>
</section>
