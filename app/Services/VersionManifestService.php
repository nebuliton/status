<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class VersionManifestService
{
    private const SAFE_DIRECTORY_ROOTS = [
        'app',
        'bootstrap',
        'config',
        'database',
        'docs',
        'lang',
        'public',
        'resources',
        'routes',
        'tests',
    ];

    private const SAFE_ROOT_FILES = [
        'artisan',
        'composer.json',
        'composer.lock',
        'Dockerfile',
        'docker-compose.yml',
        'docker-compose.yaml',
        'eslint.config.js',
        'package.json',
        'package-lock.json',
        'phpunit.xml',
        'pint.json',
        'README.md',
        'tsconfig.json',
        'version.json',
    ];

    private const SAFE_ROOT_PATTERNS = [
        '*.sh',
        '*.ps1',
        'vite.config.*',
    ];

    private const DISALLOWED_PREFIXES = [
        '.env',
        '.git',
        '.idea',
        'bootstrap/cache',
        'node_modules',
        'public/storage',
        'storage',
        'vendor',
    ];

    public function readLocal(): array
    {
        $path = base_path('version.json');

        if (! File::exists($path)) {
            throw new RuntimeException('version.json fehlt im Projektroot.');
        }

        return $this->parseJson(File::get($path));
    }

    public function safeReadLocal(): array
    {
        try {
            return $this->readLocal();
        } catch (\Throwable) {
            return [
                'version' => 'unbekannt',
                'channel' => 'stable',
                'branch' => 'main',
                'update_paths' => $this->defaultUpdatePaths(),
            ];
        }
    }

    public function parseJson(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('version.json konnte nicht gelesen werden.');
        }

        $version = trim((string) ($decoded['version'] ?? ''));
        $channel = trim((string) ($decoded['channel'] ?? 'stable'));
        $branch = trim((string) ($decoded['branch'] ?? 'main'));
        $paths = $decoded['update_paths'] ?? $this->defaultUpdatePaths();

        if ($version === '') {
            throw new RuntimeException('version.json benötigt ein Feld "version".');
        }

        if (! is_array($paths)) {
            throw new RuntimeException('version.json enthält ungültige update_paths.');
        }

        return [
            'version' => $version,
            'channel' => $channel !== '' ? $channel : 'stable',
            'branch' => $branch !== '' ? $branch : 'main',
            'update_paths' => $this->validateUpdatePaths($paths),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function defaultUpdatePaths(): array
    {
        return [
            ...self::SAFE_DIRECTORY_ROOTS,
            ...self::SAFE_ROOT_FILES,
            ...self::SAFE_ROOT_PATTERNS,
        ];
    }

    /**
     * @param  array<int, mixed>  $paths
     * @return array<int, string>
     */
    public function validateUpdatePaths(array $paths): array
    {
        $validated = [];

        foreach ($paths as $path) {
            $normalizedPath = $this->normalizePath((string) $path);

            if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
                throw new RuntimeException("Ungültiger Update-Pfad [{$path}].");
            }

            foreach (self::DISALLOWED_PREFIXES as $prefix) {
                if ($normalizedPath === $prefix || str_starts_with($normalizedPath.'/', $prefix.'/')) {
                    throw new RuntimeException("Unsicherer Update-Pfad [{$normalizedPath}].");
                }
            }

            if (in_array($normalizedPath, self::SAFE_ROOT_FILES, true)) {
                $validated[] = $normalizedPath;

                continue;
            }

            if (! str_contains($normalizedPath, '/')) {
                foreach (self::SAFE_ROOT_PATTERNS as $pattern) {
                    if (Str::is($pattern, $normalizedPath)) {
                        $validated[] = $normalizedPath;

                        continue 2;
                    }
                }
            }

            $rootSegment = explode('/', $normalizedPath)[0];

            if (! in_array($rootSegment, self::SAFE_DIRECTORY_ROOTS, true)) {
                throw new RuntimeException("Nicht erlaubter Update-Pfad [{$normalizedPath}].");
            }

            $validated[] = $normalizedPath;
        }

        return array_values(array_unique($validated));
    }

    public function isPathAllowed(string $path, array $allowedPaths): bool
    {
        $normalizedPath = $this->normalizePath($path);

        foreach (self::DISALLOWED_PREFIXES as $prefix) {
            if ($normalizedPath === $prefix || str_starts_with($normalizedPath.'/', $prefix.'/')) {
                return false;
            }
        }

        foreach ($allowedPaths as $allowedPath) {
            $allowedPath = $this->normalizePath((string) $allowedPath);

            if ($allowedPath === $normalizedPath) {
                return true;
            }

            if (str_contains($allowedPath, '*')) {
                if (! str_contains($normalizedPath, '/') && Str::is($allowedPath, $normalizedPath)) {
                    return true;
                }

                continue;
            }

            if (str_starts_with($normalizedPath.'/', $allowedPath.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function normalizePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
