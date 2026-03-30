<?php

namespace App\Services;

use App\Models\AppSetting;

class AppSettingsService
{
    public function get(string $key, ?string $default = null): ?string
    {
        try {
            return AppSetting::query()
                ->where('key', $key)
                ->value('value') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param  array<string, scalar|null>  $settings
     */
    public function update(array $settings): void
    {
        foreach ($settings as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? null : (string) $value],
            );
        }
    }
}
