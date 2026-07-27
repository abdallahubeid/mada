<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Renames singular solution_* keys to solutions_* and registers CTA keys for the landing CMS.
     */
    public function up(): void
    {
        $renames = [
            'solution_badge_text' => 'solutions_badge_text',
            'solution_title' => 'solutions_title',
            'solution_description' => 'solutions_sub_title',
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

        foreach (['solutions_btn_text', 'solutions_btn_link'] as $key) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => null],
            );
        }
    }

    public function down(): void
    {
        $renames = [
            'solutions_badge_text' => 'solution_badge_text',
            'solutions_title' => 'solution_title',
            'solutions_sub_title' => 'solution_description',
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

        Setting::query()->whereIn('key', ['solutions_btn_text', 'solutions_btn_link'])->delete();
    }
};
