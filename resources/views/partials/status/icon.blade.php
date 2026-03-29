@props([
    'name',
    'class' => 'h-5 w-5',
    'strokeWidth' => 1.8,
])

@switch($name)
    @case('sparkles')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.7 4.3L18 9l-4.3 1.7L12 15l-1.7-4.3L6 9l4.3-1.7L12 3zm6 11l.9 2.1L21 17l-2.1.9L18 20l-.9-2.1L15 17l2.1-.9L18 14zM6 15l1.1 2.9L10 19l-2.9 1.1L6 23l-1.1-2.9L2 19l2.9-1.1L6 15z"/>
        </svg>
        @break

    @case('shopping-bag')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8V7a6 6 0 1112 0v1m-13 0h14l-1 11H6L5 8z"/>
        </svg>
        @break

    @case('panel')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 016.5 4h11A2.5 2.5 0 0120 6.5v11a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 17.5v-11z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>
        </svg>
        @break

    @case('overview')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 015.5 4h5A1.5 1.5 0 0112 5.5v5A1.5 1.5 0 0110.5 12h-5A1.5 1.5 0 014 10.5v-5zm8 0A1.5 1.5 0 0113.5 4h5A1.5 1.5 0 0120 5.5v2A1.5 1.5 0 0118.5 9h-5A1.5 1.5 0 0112 7.5v-2zm0 8A1.5 1.5 0 0113.5 12h5a1.5 1.5 0 011.5 1.5v5a1.5 1.5 0 01-1.5 1.5h-5a1.5 1.5 0 01-1.5-1.5v-5zm-8 2A1.5 1.5 0 015.5 14h5a1.5 1.5 0 011.5 1.5v3A1.5 1.5 0 0110.5 20h-5A1.5 1.5 0 014 18.5v-3z"/>
        </svg>
        @break

    @case('incident')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.5v4m0 3h.01M10.3 4.8L3.8 16a2 2 0 001.7 3h13a2 2 0 001.7-3L13.7 4.8a2 2 0 00-3.4 0z"/>
        </svg>
        @break

    @case('announcement')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5V14a2 2 0 002 2h1l1.5 3h2L10 16h4.2a5.8 5.8 0 003.6-1.2L20 13V8l-2.2-1.8A5.8 5.8 0 0014.2 5H9a4 4 0 00-4 4v1.5z"/>
        </svg>
        @break

    @case('maintenance')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 6.5l3 3m-9 9l-3-3m10-9l-7 7m-1.6-7.9a3 3 0 01-4.1 4.1l2.2-2.2L3 5.5l2.2-2.2 2.1 2.1 2.2-2.2a3 3 0 014.1 4.1z"/>
        </svg>
        @break

    @case('subscribe')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 016.5 4h11A2.5 2.5 0 0120 6.5v11a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 17.5v-11z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7l7 6 7-6"/>
        </svg>
        @break

    @case('group')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A2.5 2.5 0 016.5 5h3L11 7h6.5A2.5 2.5 0 0120 9.5v7a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 16.5v-9z"/>
        </svg>
        @break

    @case('globe')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20a8 8 0 100-16 8 8 0 000 16zm0-16c2.2 2.2 3.5 5 3.5 8S14.2 17.8 12 20m0-16C9.8 6.2 8.5 9 8.5 12s1.3 5.8 3.5 8m-7.5-8h15"/>
        </svg>
        @break

    @case('plug')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6V3m6 3V3m-8 6h10v2a5 5 0 01-5 5 5 5 0 01-5-5V9zm5 7v5"/>
        </svg>
        @break

    @case('signal')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 18a7 7 0 0114 0M8.5 14a3.5 3.5 0 017 0M12 18h.01"/>
        </svg>
        @break

    @case('database')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <ellipse cx="12" cy="6" rx="7" ry="3"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6m-14 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>
        </svg>
        @break

    @case('clock')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20a8 8 0 100-16 8 8 0 000 16zm0-11v4l2.5 1.5"/>
        </svg>
        @break

    @case('activity')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2-4 4 8 2-4h6"/>
        </svg>
        @break

    @case('check')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.2 4.2L19 7"/>
        </svg>
        @break

    @case('arrow-up-right')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16L16 8m-5 0h5v5"/>
        </svg>
        @break
@endswitch
