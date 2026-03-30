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
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 6.5a3.5 3.5 0 00-4.95 4.95L4.6 16.4a2.12 2.12 0 103 3l4.95-4.95a3.5 3.5 0 004.95-4.95l-2.36 2.36-2.95-2.95 2.36-2.41z"/>
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

    @case('browser')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <rect x="3.5" y="5" width="17" height="14" rx="2.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 9h17M7 7h.01M10 7h.01"/>
        </svg>
        @break

    @case('server')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <rect x="4" y="4.5" width="16" height="6" rx="2"/>
            <rect x="4" y="13.5" width="16" height="6" rx="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7.5h.01M8 16.5h.01M12 7.5h4M12 16.5h4"/>
        </svg>
        @break

    @case('cloud')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 18h8a4 4 0 00.6-8 5.5 5.5 0 00-10.5-1.5A3.5 3.5 0 008 18z"/>
        </svg>
        @break

    @case('shield')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4l6 2.5V11c0 4.3-2.5 7.2-6 9-3.5-1.8-6-4.7-6-9V6.5L12 4z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12.5l1.6 1.6 3.4-3.6"/>
        </svg>
        @break

    @case('bolt')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2L5 13h5l-1 9 8-11h-5l1-9z"/>
        </svg>
        @break

    @case('cube')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 4v10l-7 4-7-4V7l7-4z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M5 7l7 4 7-4"/>
        </svg>
        @break

    @case('credit-card')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <rect x="3" y="6" width="18" height="12" rx="2.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 14h4"/>
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

    @case('share')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <circle cx="18" cy="5" r="3"/>
            <circle cx="6" cy="12" r="3"/>
            <circle cx="18" cy="19" r="3"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.7 10.9l6.6-3.8M8.7 13.1l6.6 3.8"/>
        </svg>
        @break

    @case('image')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" aria-hidden="true">
            <rect x="3.5" y="5" width="17" height="14" rx="2.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14l3-3 2.5 2.5L16.5 10l4 5M8 9.5h.01"/>
        </svg>
        @break

    @case('github')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2C6.48 2 2 6.58 2 12.22c0 4.5 2.87 8.32 6.84 9.66.5.09.68-.22.68-.49 0-.24-.01-1.04-.01-1.88-2.78.62-3.37-1.21-3.37-1.21-.45-1.18-1.11-1.49-1.11-1.49-.91-.64.07-.63.07-.63 1 .08 1.53 1.06 1.53 1.06.9 1.57 2.35 1.12 2.92.85.09-.67.35-1.12.64-1.37-2.22-.26-4.56-1.14-4.56-5.09 0-1.13.39-2.06 1.03-2.79-.1-.26-.45-1.31.1-2.73 0 0 .84-.27 2.75 1.06A9.33 9.33 0 0112 6.84c.85 0 1.71.12 2.51.35 1.9-1.33 2.74-1.06 2.74-1.06.56 1.42.21 2.47.11 2.73.64.73 1.03 1.66 1.03 2.79 0 3.96-2.34 4.82-4.57 5.08.36.32.68.95.68 1.92 0 1.39-.01 2.5-.01 2.84 0 .27.18.59.69.49A10.18 10.18 0 0022 12.22C22 6.58 17.52 2 12 2z"/>
        </svg>
        @break
@endswitch
