<x-filament-panels::page>
    @php
        $preview = $this->preview;
        $services = collect($preview['services'] ?? []);
    @endphp

    <div wire:poll.30s.visible="refreshData" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <x-filament::section
                    heading="Discord Versand"
                    description="Webhook, Automatik und Snapshot-Versand für den Statuskanal."
                >
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <label class="block">
                                <span class="text-sm font-medium text-gray-950 dark:text-white">Webhook-URL</span>
                                <input
                                    type="url"
                                    wire:model.defer="webhookUrl"
                                    placeholder="https://discord.com/api/webhooks/..."
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-950 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                >
                            </label>
                        </div>

                        <div class="space-y-3">
                            <label class="flex gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                                <input type="checkbox" wire:model="autoEnabled" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-950 dark:text-white">Automatischen Versand aktivieren</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                        Sendet nur bei echten Statusänderungen und nutzt den Scheduler.
                                    </span>
                                </span>
                            </label>

                            <label class="flex gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                                <input type="checkbox" wire:model="includeImage" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-950 dark:text-white">Statusbild im Embed</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                        Nutzt die Live-Grafik aus der öffentlichen Statusseite.
                                    </span>
                                </span>
                            </label>

                            <label class="flex gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                                <input type="checkbox" wire:model="includeServiceLinks" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-950 dark:text-white">Direkte Service-Links einfügen</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                        Verlinkt im Embed direkt auf die einzelnen Service-Statusseiten.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-filament::button color="primary" wire:click="saveSettings">
                                Einstellungen speichern
                            </x-filament::button>

                            <x-filament::button color="gray" wire:click="sendNow">
                                Snapshot jetzt senden
                            </x-filament::button>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Automatik</p>
                                <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $this->autoEnabled ? 'Aktiviert' : 'Deaktiviert' }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->includeImage ? 'Mit Bildvorschau' : 'Ohne Bildvorschau' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Letzter Versand</p>
                                <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $this->lastSentAt ? \Carbon\Carbon::parse($this->lastSentAt)->translatedFormat('d. M Y · H:i:s') : 'Noch nicht gesendet' }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->includeServiceLinks ? 'Mit Service-Links' : 'Ohne Service-Links' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <div class="xl:col-span-3">
                <x-filament::section
                    heading="Embed-Vorschau"
                    description="So wirkt der aktuelle Snapshot in Discord, Slack und Open-Graph-Vorschauen."
                >
                    <div class="space-y-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-950 dark:text-white">
                                    {{ $preview['globalMessage'] ?? 'Nebuliton Status' }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Stand {{ $preview['lastUpdatedLabel'] ?? 'gerade eben' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-300">
                                    {{ $preview['statusBreakdown']['operational'] ?? 0 }} betriebsbereit
                                </span>
                                <span class="inline-flex items-center rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">
                                    {{ $preview['statusBreakdown']['degraded'] ?? 0 }} beeinträchtigt
                                </span>
                                <span class="inline-flex items-center rounded-full bg-danger-50 px-3 py-1 text-xs font-semibold text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">
                                    {{ $preview['statusBreakdown']['down'] ?? 0 }} Ausfall
                                </span>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-black/20">
                            <img
                                src="{{ route('status.overview.image', ['v' => $preview['shareHash'] ?? 'preview']) }}"
                                alt="Discord-Statusbild"
                                class="block w-full"
                            >
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Dienste</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $preview['serviceCount'] ?? 0 }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Im aktuellen Snapshot</p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Verfügbarkeit</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                                    {{ number_format((float) ($preview['averageUptime'] ?? 100), 2, ',', '.') }} %
                                </p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Durchschnitt aller Dienste</p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Bildquelle</p>
                                <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">/status/card.svg</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Für Embeds und Vorschauen</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Dienste im Snapshot</h4>
                                <span class="text-xs text-gray-400">{{ $services->count() }}</span>
                            </div>

                            <div class="mt-3 space-y-2">
                                @forelse ($services as $service)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $service['name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $service['status']->label() }}</p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                                {{ number_format((float) $service['uptime_percentage'], 2, ',', '.') }} %
                                            </p>
                                            <a
                                                href="{{ route('status.service.show', ['service' => $service['slug']]) }}"
                                                target="_blank"
                                                rel="noreferrer"
                                                class="text-xs text-primary-600 hover:text-primary-500 dark:text-primary-300"
                                            >
                                                Statusseite öffnen
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                                        Es sind noch keine Dienste vorhanden.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
