<x-filament-widgets::widget>
    <x-filament::section
        heading="Schnellaktionen"
        description="Die wichtigsten Verwaltungsaufgaben direkt aus dem Dashboard."
    >
        <div class="space-y-3">
            @foreach ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10"
                >
                    <div class="mt-0.5 rounded-lg bg-primary-50 p-2 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                        <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $action['title'] }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $action['description'] }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
