@props([
    'icon',
    'class' => 'h-10 w-10 rounded-2xl bg-white text-slate-500',
])

<div {{ $attributes->merge(['class' => "status-icon-shell overflow-hidden {$class}"]) }}>
    @if (($icon['type'] ?? null) === 'image')
        <img
            src="{{ $icon['value'] }}"
            alt=""
            class="h-full w-full object-cover"
            loading="lazy"
            referrerpolicy="no-referrer"
            onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
        >
        <div class="hidden text-slate-500" data-fallback-icon>
            @include('partials.status.icon', ['name' => $icon['fallback'] ?? 'activity', 'class' => 'h-4 w-4'])
        </div>
    @else
        @include('partials.status.icon', ['name' => $icon['value'] ?? 'activity', 'class' => 'h-4 w-4'])
    @endif
</div>
