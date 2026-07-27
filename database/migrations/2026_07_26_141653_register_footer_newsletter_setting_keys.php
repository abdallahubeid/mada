<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Registers footer navigation, newsletter, and description setting keys.
     */
    public function up(): void
    {
        $keys = [
            'footer_description',
            'footer_newsletter_title',
            'footer_newsletter_btn_text',
            'footer_title1',
            'footer_btn1_text',
            'footer_btn1_link',
            'footer_btn2_text',
            'footer_btn2_link',
            'footer_btn3_text',
            'footer_btn3_link',
            'footer_btn4_text',
            'footer_btn4_link',
            'footer_title2',
            'footer_btn5_text',
            'footer_btn5_link',
            'footer_btn6_text',
            'footer_btn6_link',
            'footer_btn7_text',
            'footer_btn7_link',
            'footer_title3',
            'footer_btn8_text',
            'footer_btn8_link',
            'footer_btn9_text',
            'footer_btn9_link',
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
            'footer_description',
            'footer_newsletter_title',
            'footer_newsletter_btn_text',
            'footer_title1',
            'footer_btn1_text',
            'footer_btn1_link',
            'footer_btn2_text',
            'footer_btn2_link',
            'footer_btn3_text',
            'footer_btn3_link',
            'footer_btn4_text',
            'footer_btn4_link',
            'footer_title2',
            'footer_btn5_text',
            'footer_btn5_link',
            'footer_btn6_text',
            'footer_btn6_link',
            'footer_btn7_text',
            'footer_btn7_link',
            'footer_title3',
            'footer_btn8_text',
            'footer_btn8_link',
            'footer_btn9_text',
            'footer_btn9_link',
        ])->delete();
    }
};
