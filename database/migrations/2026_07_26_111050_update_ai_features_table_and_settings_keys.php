<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds Phosphor icon column to ai_features and renames AI settings key to sub_title.
     */
    public function up(): void
    {
        Schema::table('ai_features', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('description');
        });

        if (Schema::hasColumn('ai_features', 'icon_key')) {
            Schema::table('ai_features', function (Blueprint $table) {
                $table->dropColumn('icon_key');
            });
        }

        $existing = Setting::query()->where('key', 'ai_sup_title')->first();

        if ($existing !== null) {
            Setting::query()->updateOrCreate(
                ['key' => 'ai_sub_title'],
                ['value' => $existing->value],
            );
            $existing->delete();
        } else {
            Setting::query()->firstOrCreate(
                ['key' => 'ai_sub_title'],
                ['value' => null],
            );
        }
    }

    public function down(): void
    {
        Schema::table('ai_features', function (Blueprint $table) {
            $table->string('icon_key')->nullable()->after('description');
            $table->dropColumn('icon');
        });

        $existing = Setting::query()->where('key', 'ai_sub_title')->first();

        if ($existing !== null) {
            Setting::query()->updateOrCreate(
                ['key' => 'ai_sup_title'],
                ['value' => $existing->value],
            );
            $existing->delete();
        }
    }
};
