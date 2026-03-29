<section class="status-card px-5 py-5 sm:px-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="status-icon-shell">
                @include('partials.status.icon', ['name' => 'announcement', 'class' => 'h-5 w-5'])
            </div>
            <div>
                <h2 class="font-display text-xl font-semibold text-slate-950">Ankündigungen</h2>
                <p class="mt-1 text-sm text-slate-500">Servicehinweise, Produktupdates und kundenrelevante Betriebsinformationen.</p>
            </div>
        </div>

        <span class="status-inline-meta">{{ $announcements->count() }}</span>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @forelse ($announcements as $announcement)
            <article class="status-card-subtle px-5 py-5">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($announcement->is_pinned)
                        <span class="status-pill border-indigo-200/80 bg-indigo-50 text-indigo-700">Angepinnt</span>
                    @endif

                    @if ($announcement->published_at)
                        <span class="text-xs text-slate-400">{{ $announcement->published_at->translatedFormat('d. M Y · H:i') }}</span>
                    @endif
                </div>

                <h3 class="mt-4 font-display text-2xl font-semibold tracking-tight text-slate-950">{{ $announcement->title }}</h3>

                @if ($announcement->excerpt)
                    <p class="mt-3 text-sm font-medium text-slate-600">{{ $announcement->excerpt }}</p>
                @endif

                <p class="mt-3 text-sm leading-6 text-slate-500">{{ $announcement->content }}</p>
            </article>
        @empty
            <div class="status-card-subtle col-span-full px-5 py-8 text-sm text-slate-500">
                Es wurden noch keine Ankündigungen veröffentlicht.
            </div>
        @endforelse
    </div>
</section>
