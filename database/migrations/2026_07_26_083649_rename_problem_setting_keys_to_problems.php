<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Renames singular problem_* keys to problems_* (and sup → sub) for the landing CMS.
     */
    public function up(): void
    {
        $renames = [
            'problem_badge_text' => 'problems_badge_text',
            'problem_title' => 'problems_title',
            'problem_sup_title' => 'problems_sub_title',
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
    }

    public function down(): void
    {
        $renames = [
            'problems_badge_text' => 'problem_badge_text',
            'problems_title' => 'problem_title',
            'problems_sub_title' => 'problem_sup_title',
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
    }
};
