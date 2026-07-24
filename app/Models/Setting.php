<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Platform key/value settings for landing-page CMS content.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * All landing CMS keys (section-prefixed for uniqueness).
     *
     * @return list<string>
     */
    public static function landingKeys(): array
    {
        return [
            'site_logo',
            'hero_badge_text',
            'hero_title',
            'hero_description',
            'hero_btn1_text',
            'hero_btn1_link',
            'hero_btn2_text',
            'hero_btn2_link',
            'problem_badge_text',
            'problem_title',
            'problem_sup_title',
            'solution_badge_text',
            'solution_title',
            'solution_description',
            'offerings_title',
            'offerings_sup_title',
            'modules_badge_text',
            'modules_title',
            'modules_sup_title',
            'previews_title',
            'previews_sup_title',
            'previews_img',
            'previews_video',
            'ai_badge_text',
            'ai_title',
            'ai_sup_title',
            'features_title',
            'features_sup_title',
            'features_badge_text',
            'testimonials_badge_text',
            'testimonials_title',
            'testimonials_sup_title',
            'pricing_title',
            'pricing_sup_title',
            'pricing_btn_text',
            'pricing_btn_link',
            'faq_title',
            'faq_sup_title',
            'cta_title',
            'cta_sup_title',
            'cta_btn1_text',
            'cta_btn1_link',
            'cta_btn2_text',
            'cta_btn2_link',
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
    }

    /**
     * @return Collection<string, string|null>
     */
    public static function map(): Collection
    {
        return static::query()->pluck('value', 'key');
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }
}
