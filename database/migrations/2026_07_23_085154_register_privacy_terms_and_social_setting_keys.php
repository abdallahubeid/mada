<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Registers privacy, terms, and footer social setting keys in the key/value store.
     * Values stay null until edited in admin (no seeder content).
     */
    public function up(): void
    {
        $keys = [
            'privacy_badge_text',
            'privacy_title',
            'privacy_sub_title',
            'privacy_description',
            'privacy_btn_text',
            'privacy_btn_link',
            'terms_badge_text',
            'terms_title',
            'terms_sub_title',
            'terms_description',
            'terms_btn_text',
            'terms_btn_link',
            'social_btn1_text',
            'social_btn1_link',
            'social_btn2_text',
            'social_btn2_link',
            'social_btn3_text',
            'social_btn3_link',
            'social_btn4_text',
            'social_btn4_link',
            'social_btn5_text',
            'social_btn5_link',
        ];

        foreach ($keys as $key) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => null],
            );
        }
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', [
            'privacy_badge_text',
            'privacy_title',
            'privacy_sub_title',
            'privacy_description',
            'privacy_btn_text',
            'privacy_btn_link',
            'terms_badge_text',
            'terms_title',
            'terms_sub_title',
            'terms_description',
            'terms_btn_text',
            'terms_btn_link',
            'social_btn1_text',
            'social_btn1_link',
            'social_btn2_text',
            'social_btn2_link',
            'social_btn3_text',
            'social_btn3_link',
            'social_btn4_text',
            'social_btn4_link',
            'social_btn5_text',
            'social_btn5_link',
        ])->delete();
    }
};
