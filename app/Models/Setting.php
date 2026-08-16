<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Platform key/value settings for landing-page CMS content.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    use SoftDeletes;

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
            'site_favicon',
            'hero_badge_text',
            'hero_title',
            'hero_description',
            'hero_btn1_text',
            'hero_btn1_link',
            'hero_btn2_text',
            'hero_btn2_link',
            'problems_badge_text',
            'problems_title',
            'problems_sub_title',
            'solutions_badge_text',
            'solutions_title',
            'solutions_sub_title',
            'solutions_btn_text',
            'solutions_btn_link',
            'offerings_title',
            'offerings_sub_title',
            'modules_badge_text',
            'modules_title',
            'modules_sub_title',
            'product_previews_badge_text',
            'product_previews_title',
            'product_previews_sub_title',
            'previews_img',
            'previews_video',
            /*
             * Video section (Super Admin → Landing Settings → "Video Section").
             *
             * Stored as key/value rows like every other CMS field rather than as
             * new columns on `settings`. The table is a key/value store — it has
             * exactly `id, key, value, timestamps` — so columns would need a
             * parallel read path and would bypass `Setting::map()`, the CMS
             * screen and `MarketingCache` all at once. No migration is required.
             *
             * `video_url` and the uploaded `previews_video` are alternatives:
             * the URL wins when both are set, so an admin can point at a CDN
             * without deleting the file they uploaded earlier.
             *
             * `is_video_section_active` is stored as the string '1' / '0'
             * because `value` is a text column; read it through
             * `Setting::isVideoSectionActive()` rather than casting at each
             * call site.
             */
            'video_url',
            'video_title',
            'video_description',
            'is_video_section_active',
            'ai_badge_text',
            'ai_title',
            'ai_sub_title',
            'why_us_badge_text',
            'why_us_title',
            'why_us_sub_title',
            'testimonials_badge_text',
            'testimonials_title',
            'testimonials_sub_title',
            'pricing_title',
            'pricing_sub_title',
            'pricing_btn_text',
            'pricing_btn_link',
            'faq_title',
            'faq_sub_title',
            'cta_title',
            'cta_sub_title',
            'cta_btn1_text',
            'cta_btn1_link',
            'cta_btn2_text',
            'cta_btn2_link',
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

    /**
     * Public URL for a setting-stored upload path (custom disk → public root).
     */
    public static function assetUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return asset($path);
    }

    /**
     * Settings keys that support AJAX image deletion from the admin Site tab.
     *
     * @return list<string>
     */
    public static function deletableBrandingKeys(): array
    {
        return ['site_logo', 'site_favicon'];
    }

    /**
     * Is the public landing video section switched on?
     *
     * `value` is a text column, so the toggle round-trips as '1' / '0' rather
     * than as a real boolean. Reading it through here keeps the string-to-bool
     * coercion in one place instead of at every call site, and means a NULL row
     * (key never saved) is treated as ON — the section should be visible by
     * default rather than silently missing on a fresh install.
     */
    public static function isVideoSectionActive(): bool
    {
        $value = static::getValue('is_video_section_active');

        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Resolved source for the landing video: external URL wins over upload.
     *
     * The two are deliberately alternatives rather than one field. An admin
     * moving to a CDN can paste a URL without first deleting the file they
     * uploaded months ago, and can fall back by clearing the URL — the upload
     * is still there. Returning null means "no video", which the component
     * treats as "render nothing" rather than as an error.
     */
    public static function videoSource(): ?string
    {
        $url = static::getValue('video_url');

        if (filled($url)) {
            return $url;
        }

        $upload = static::getValue('previews_video');

        return filled($upload) ? static::assetUrl($upload) : null;
    }

    /**
     * Delete the stored file for a branding setting and null the DB value.
     */
    public static function clearUploadedFile(string $key): void
    {
        if (! in_array($key, self::deletableBrandingKeys(), true)) {
            abort(422, 'Invalid setting key.');
        }

        $setting = static::query()->where('key', $key)->first();

        if ($setting === null) {
            return;
        }

        if (filled($setting->value)) {
            Storage::disk('custom')->delete($setting->value);
        }

        $setting->update(['value' => null]);
    }
}
