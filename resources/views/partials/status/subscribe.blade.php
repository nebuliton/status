<section class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
    <div class="status-card px-5 py-6 sm:px-6">
        <div class="flex items-center gap-3">
            <div class="status-icon-shell">
                @include('partials.status.icon', ['name' => 'subscribe', 'class' => 'h-5 w-5'])
            </div>
            <div>
                <h2 class="font-display text-2xl font-semibold text-slate-950">Status-Updates abonnieren</h2>
                <p class="mt-1 text-sm text-slate-500">E-Mail-Liste für künftige Benachrichtigungen, Webhooks oder Discord-Integrationen.</p>
            </div>
        </div>

        <form wire:submit="subscribe" class="mt-6 space-y-4">
            <div>
                <label for="subscriberEmail" class="text-sm font-medium text-slate-700">E-Mail-Adresse</label>
                <input
                    id="subscriberEmail"
                    type="email"
                    wire:model.live.debounce.250ms="subscriberEmail"
                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                    placeholder="ops@company.com"
                >
                @error('subscriberEmail')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="submit" class="inline-flex items-center justify-center rounded-full border border-slate-900 bg-slate-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                    Updates abonnieren
                </button>

                @if ($subscriptionMessage)
                    <p class="text-sm text-emerald-700">{{ $subscriptionMessage }}</p>
                @endif
            </div>
        </form>
    </div>

    <aside class="space-y-5">
        <div class="status-card px-5 py-5 sm:px-6">
            <h2 class="font-display text-xl font-semibold text-slate-950">Benachrichtigungskanäle</h2>
            <div class="mt-5 space-y-3">
                <div class="status-card-subtle px-4 py-4">
                    <p class="text-sm font-semibold text-slate-950">E-Mail-Abonnenten</p>
                    <p class="mt-2 text-sm text-slate-500">Sobald Benachrichtigungen aktiv sind, gehen Status-Änderungen direkt an diese Liste.</p>
                </div>
                <div class="status-card-subtle px-4 py-4">
                    <p class="text-sm font-semibold text-slate-950">Discord und Webhooks</p>
                    <p class="mt-2 text-sm text-slate-500">Können später als zusätzliche Kanäle ergänzt werden, ohne die Seite umzubauen.</p>
                </div>
            </div>
        </div>

        <div class="status-card px-5 py-5 sm:px-6">
            <h2 class="font-display text-xl font-semibold text-slate-950">Direkt einsatzbereit</h2>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Neue Vorfälle, Wartungen und Status-Änderungen erscheinen hier sofort in einer klaren, ruhigen Oberfläche.
            </p>
        </div>
    </aside>
</section>
