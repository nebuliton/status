<?php

use App\Models\Subscriber;
use App\Services\Status\ServiceCheckRunner;
use App\Services\Status\StatusPageService;
use App\Services\VersionManifestService;
use Livewire\Volt\Component;

new class extends Component {
    public string $activeTab = 'overview';

    public string $subscriberEmail = '';

    public ?string $subscriptionMessage = null;

    protected function rules(): array
    {
        return [
            'subscriberEmail' => ['required', 'email', 'max:255'],
        ];
    }

    public function mount(): void
    {
        if (app()->isLocal()) {
            app(ServiceCheckRunner::class)->runDueChecks();
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->getTabsProperty())) {
            $this->activeTab = $tab;
        }
    }

    public function updatedSubscriberEmail(): void
    {
        $this->resetValidation('subscriberEmail');
    }

    public function refreshSnapshot(): void
    {
        if (app()->isLocal()) {
            app(ServiceCheckRunner::class)->runDueChecks();
        }
    }

    public function subscribe(): void
    {
        $validated = $this->validate(
            messages: [
                'subscriberEmail.required' => 'Bitte gib eine E-Mail-Adresse ein.',
                'subscriberEmail.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
                'subscriberEmail.max' => 'Die E-Mail-Adresse darf höchstens 255 Zeichen lang sein.',
            ],
        );
        $email = str($validated['subscriberEmail'])->trim()->lower()->toString();

        Subscriber::query()->updateOrCreate(
            ['email' => $email],
            ['is_active' => true],
        );

        $this->subscriberEmail = '';
        $this->subscriptionMessage = 'Du bist eingetragen. Sobald Benachrichtigungen aktiv sind, erhältst du die nächsten Updates per E-Mail.';
    }

    public function getSnapshotProperty(): array
    {
        return app(StatusPageService::class)->snapshot();
    }

    public function getReleaseProperty(): array
    {
        return app(VersionManifestService::class)->safeReadLocal();
    }

    public function getTabsProperty(): array
    {
        return [
            'overview' => ['label' => 'Übersicht', 'icon' => 'overview'],
            'incidents' => ['label' => 'Vorfälle', 'icon' => 'incident'],
            'announcements' => ['label' => 'Ankündigungen', 'icon' => 'announcement'],
            'maintenance' => ['label' => 'Wartung', 'icon' => 'maintenance'],
            'subscribe' => ['label' => 'Abonnieren', 'icon' => 'subscribe'],
        ];
    }
}; ?>

@php($snapshot = $this->snapshot)
@php($globalStatus = $snapshot['globalStatus'])
@php($tabs = $this->tabs)
@php($activeIncidents = $snapshot['incidents']['active'])
@php($resolvedIncidents = $snapshot['incidents']['resolved'])
@php($announcements = $snapshot['announcements'])
@php($upcomingMaintenances = $snapshot['maintenances']['upcoming'])
@php($completedMaintenances = $snapshot['maintenances']['completed'])
@php($release = $this->release)
@php($logoUrl = config('services.nebuliton.logo_url'))
@php($githubUrl = config('services.nebuliton.github_url'))

<div wire:poll.30s.visible="refreshSnapshot" class="status-shell min-h-screen text-slate-900">
    <div class="status-mesh"></div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
        <header class="status-card mb-5 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-4">
                    <div class="status-icon-shell h-12 w-12 overflow-hidden rounded-2xl border-slate-200 bg-white p-0 shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
                        <img src="{{ $logoUrl }}" alt="Nebuliton Logo" class="h-full w-full object-cover" loading="eager">
                    </div>

                    <div>
                        <p class="font-display text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">Nebuliton</p>
                        <p class="mt-1 text-sm text-slate-500">Status, Vorfälle und Wartungen auf einen Blick.</p>
                    </div>
                </a>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="status-pill {{ $globalStatus->badgeClasses() }}">
                        <span class="h-2 w-2 rounded-full bg-current"></span>
                        {{ $snapshot['globalMessage'] }}
                    </span>

                    <span class="status-inline-meta">
                        @include('partials.status.icon', ['name' => 'clock', 'class' => 'h-4 w-4'])
                        Aktualisiert {{ $snapshot['lastUpdatedLabel'] }}
                    </span>

                    <a href="{{ config('services.nebuliton.shop_url') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-950">
                        @include('partials.status.icon', ['name' => 'shopping-bag', 'class' => 'h-4 w-4'])
                        Shop
                    </a>

                    @auth
                        <a href="{{ config('services.nebuliton.control_panel_url') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-900 bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                            @include('partials.status.icon', ['name' => 'panel', 'class' => 'h-4 w-4'])
                            Kontrollzentrum
                        </a>
                    @endauth
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2 rounded-[20px] border border-slate-200 bg-slate-50/80 p-1.5">
                @foreach ($tabs as $key => $tab)
                    <button
                        type="button"
                        wire:click="setTab('{{ $key }}')"
                        @class([
                            'status-tab',
                            'status-tab-active' => $activeTab === $key,
                            'status-tab-idle' => $activeTab !== $key,
                        ])
                    >
                        @include('partials.status.icon', ['name' => $tab['icon'], 'class' => 'h-4 w-4'])
                        <span>{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </header>

        <main class="flex-1">
            <div wire:key="tab-{{ $activeTab }}">
                @if ($activeTab === 'overview')
                    @include('partials.status.overview', [
                        'snapshot' => $snapshot,
                        'activeIncidents' => $activeIncidents,
                    ])
                @elseif ($activeTab === 'incidents')
                    @include('partials.status.incidents', [
                        'activeIncidents' => $activeIncidents,
                        'resolvedIncidents' => $resolvedIncidents,
                    ])
                @elseif ($activeTab === 'announcements')
                    @include('partials.status.announcements', [
                        'announcements' => $announcements,
                    ])
                @elseif ($activeTab === 'maintenance')
                    @include('partials.status.maintenance', [
                        'upcomingMaintenances' => $upcomingMaintenances,
                        'completedMaintenances' => $completedMaintenances,
                    ])
                @elseif ($activeTab === 'subscribe')
                    @include('partials.status.subscribe', [
                        'subscriptionMessage' => $subscriptionMessage,
                    ])
                @endif
            </div>
        </main>

        <footer class="mt-10 border-t border-slate-200/80 px-1 pt-6">
            <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <div class="status-icon-shell h-10 w-10 overflow-hidden rounded-2xl bg-white p-0">
                        <img src="{{ $logoUrl }}" alt="Nebuliton Logo" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-950">Nebuliton Status</p>
                        <p class="text-sm text-slate-500">Betriebsbereit, klar und jederzeit aktuell.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ $githubUrl }}" target="_blank" rel="noreferrer" class="status-footer-meta status-footer-meta-link">
                        <span class="status-footer-meta-icon">
                            @include('partials.status.icon', ['name' => 'github', 'class' => 'h-4 w-4', 'strokeWidth' => 0])
                        </span>
                        <span class="text-sm font-medium text-slate-600">GitHub</span>
                    </a>

                    <span class="status-footer-meta">
                        <span class="status-footer-meta-icon text-brand-600">
                            @include('partials.status.icon', ['name' => 'sparkles', 'class' => 'h-4 w-4'])
                        </span>
                        <span class="text-sm font-medium text-slate-600">
                            v{{ $release['version'] }}
                            <span class="text-slate-400">· {{ $release['branch'] }}</span>
                        </span>
                    </span>

                    <a href="{{ config('services.nebuliton.shop_url') }}" class="status-footer-link">
                        Shop
                        @include('partials.status.icon', ['name' => 'arrow-up-right', 'class' => 'h-4 w-4'])
                    </a>
                    @auth
                        <a href="{{ config('services.nebuliton.control_panel_url') }}" class="status-footer-link">
                            Kontrollzentrum
                            @include('partials.status.icon', ['name' => 'arrow-up-right', 'class' => 'h-4 w-4'])
                        </a>
                    @endauth
                    <span class="text-sm text-slate-400">
                        Letzte Aktualisierung
                        {{ $snapshot['lastUpdatedAt']?->translatedFormat('d. M Y · H:i') ?? 'noch keine Daten' }}
                    </span>
                </div>
            </div>
        </footer>
    </div>

    <div id="status-share-toast" class="status-share-toast hidden">Link kopiert</div>
</div>

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
