<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

@php
    $faviconUrl = config('services.nebuliton.logo_url');
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="image/png">
<link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/png">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|space-grotesk:500,600,700" rel="stylesheet" />

@php
    $hotPath = public_path('hot');

    if (is_file($hotPath)) {
        $hotUrl = trim((string) file_get_contents($hotPath));
        $parts = parse_url($hotUrl);
        $host = $parts['host'] ?? null;
        $port = (int) ($parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80));

        if (! $host || ! $port) {
            @unlink($hotPath);
        } else {
            $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, 0.2);

            if (is_resource($socket)) {
                fclose($socket);
            } else {
                @unlink($hotPath);
            }
        }
    }
@endphp

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
