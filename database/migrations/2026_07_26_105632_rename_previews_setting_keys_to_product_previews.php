<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Renames previews text keys to product_previews_* for the landing CMS.
     */
    public function up(): void
    {
        $renames = [
            'previews_title' => 'product_previews_title',
            'previews_sup_title' => 'product_previews_sub_title',
        ];

        foreach ($renames as $from => $to) {
            $existing = Setting::query()->where('key', $from)->first();

            if ($existing !== null) {
                Setting::query()->updateOrCreate(
                    ['key' => $to],
                    ['value' => $existing->value],
                );
                $existing->delete();

                continue;
            }

            Setting::query()->firstOrCreate(
                ['key' => $to],
                ['value' => null],
            );
        }

        Setting::query()->firstOrCreate(
            ['key' => 'product_previews_badge_text'],
            ['value' => null],
        );
    }

    public function down(): void
    {
        $renames = [
            'product_previews_title' => 'previews_title',
            'product_previews_sub_title' => 'previews_sup_title',
        ];

        foreach ($renames as $from => $to) {
            $existing = Setting::query()->where('key', $from)->first();

            if ($existing !== null) {
                Setting::query()->updateOrCreate(
                    ['key' => $to],
                    ['value' => $existing->value],
                );
                $existing->delete();
            }
        }

        Setting::query()->where('key', 'product_previews_badge_text')->delete();
    }
};
