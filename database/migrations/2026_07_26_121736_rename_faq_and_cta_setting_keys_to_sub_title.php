<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Renames FAQ and CTA settings keys to sub_title for consistency.
     */
    public function up(): void
    {
        $this->migrateSettingKey('faq_sup_title', 'faq_sub_title');
        $this->migrateSettingKey('cta_sup_title', 'cta_sub_title');
    }

    public function down(): void
    {
        $this->migrateSettingKey('faq_sub_title', 'faq_sup_title');
        $this->migrateSettingKey('cta_sub_title', 'cta_sup_title');
    }

    private function migrateSettingKey(string $from, string $to): void
    {
        $existing = Setting::query()->where('key', $from)->first();

        if ($existing !== null) {
            Setting::query()->updateOrCreate(
                ['key' => $to],
                ['value' => $existing->value],
            );
            $existing->delete();
        } else {
            Setting::query()->firstOrCreate(
                ['key' => $to],
                ['value' => null],
            );
        }
    }
};
