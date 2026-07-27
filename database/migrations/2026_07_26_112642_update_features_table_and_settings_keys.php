<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds Phosphor icon column to features and renames Why Us settings keys.
     */
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('description');
        });

        if (Schema::hasColumn('features', 'icon_key')) {
            Schema::table('features', function (Blueprint $table) {
                $table->dropColumn('icon_key');
            });
        }

        $this->migrateSettingKey('features_badge_text', 'why_us_badge_text');
        $this->migrateSettingKey('features_title', 'why_us_title');
        $this->migrateSettingKey('features_sup_title', 'why_us_sub_title');
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('icon_key')->nullable()->after('description');
            $table->dropColumn('icon');
        });

        $this->migrateSettingKey('why_us_badge_text', 'features_badge_text');
        $this->migrateSettingKey('why_us_title', 'features_title');
        $this->migrateSettingKey('why_us_sub_title', 'features_sup_title');
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
