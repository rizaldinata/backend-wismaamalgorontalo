<?php

namespace Modules\Setting\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Modules\Setting\Models\FeatureToggle;
use Modules\Setting\Models\FeatureToggleLog;

class FeatureToggleService
{
    private const CACHE_KEY_PREFIX = 'feature_toggle_';

    private const MODULES_STATUSES_PATH = 'modules_statuses.json';

    /**
     * Get effective status of a feature toggle by its key.
     * Uses cache for performance.
     */
    public function isEnabled(string $key): bool
    {
        return Cache::rememberForever(self::CACHE_KEY_PREFIX.$key, function () use ($key) {
            $toggle = FeatureToggle::with('parent')->where('key', $key)->first();
            if (! $toggle) {
                // Default to false if not found. Or should we check legacy? We decided to migrate completely to feature_toggles.
                return false;
            }

            return $toggle->effective_status;
        });
    }

    /**
     * Update the active status of a toggle.
     */
    public function updateStatus(string $key, bool $isActive, ?int $userId = null): bool
    {
        $toggle = FeatureToggle::where('key', $key)->first();

        if (! $toggle || $toggle->is_locked) {
            return false;
        }

        $oldValue = $toggle->is_active;
        $toggle->is_active = $isActive;
        $toggle->save();

        // Log the change
        FeatureToggleLog::create([
            'toggle_id' => $toggle->id,
            'user_id' => $userId,
            'old_value' => $oldValue,
            'new_value' => $isActive,
        ]);

        // Clear cache for this toggle
        Cache::forget(self::CACHE_KEY_PREFIX.$key);

        // If it's a module, clear its children's cache since their effective status might change
        if ($toggle->isModule()) {
            foreach ($toggle->children as $child) {
                Cache::forget(self::CACHE_KEY_PREFIX.$child->key);
            }
        }

        return true;
    }

    /**
     * Get hierarchical structure of feature toggles for UI.
     */
    public function getHierarchicalToggles(): array
    {
        $modules = FeatureToggle::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        $togglesArray = $modules->toArray();

        $path = base_path(self::MODULES_STATUSES_PATH);
        $statuses = [];
        if (File::exists($path)) {
            $statuses = json_decode(File::get($path), true) ?? [];
        }

        $mapLicensed = function (array &$item, bool $parentLicensed) use (&$mapLicensed, $statuses) {
            if ($item['parent_id'] === null) {
                // Modul utama: ambil dari JSON berdasarkan key ucfirst
                $moduleName = ucfirst($item['key']);
                if (isset($statuses[$moduleName])) {
                    $item['is_licensed'] = (bool) $statuses[$moduleName];
                } else {
                    $item['is_licensed'] = true; // default true jika tidak ada di JSON (misal core module)
                }
            } else {
                // Sub-fitur mewarisi lisensi parent
                $item['is_licensed'] = $parentLicensed;
            }

            if (! empty($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $mapLicensed($child, $item['is_licensed']);
                }
            }
        };

        foreach ($togglesArray as &$moduleItem) {
            $mapLicensed($moduleItem, true);
        }

        return $togglesArray;
    }
}
