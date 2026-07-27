<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Renames pricing settings key to sub_title for consistency.
     */
    public function up(): void
    {
        $existing = Setting::query()->where('key', 'pricing_sup_title')->first();

        if ($existing !== null) {
            Setting::query()->updateOrCreate(
                ['key' => 'pricing_sub_title'],
                ['value' => $existing->value],
            );
            $existing->delete();
        } else {
            Setting::query()->firstOrCreate(
                ['key' => 'pricing_sub_title'],
                ['value' => null],
            );
        }
    }

    public function down(): void
    {
        $existing = Setting::query()->where('key', 'pricing_sub_title')->first();

        if ($existing !== null) {
            Setting::query()->updateOrCreate(
                ['key' => 'pricing_sup_title'],
                ['value' => $existing->value],
            );
            $existing->delete();
        }
    }
};
