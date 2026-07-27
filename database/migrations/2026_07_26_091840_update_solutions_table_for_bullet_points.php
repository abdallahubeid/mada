<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repurpose solutions rows as landing bullet points: drop button columns, add Phosphor icon key.
     */
    public function up(): void
    {
        Schema::table('solutions', function (Blueprint $table) {
            $table->dropColumn(['btn_text', 'btn_link']);
            $table->string('icon')->nullable()->after('description');
        });

        if (Schema::hasColumn('solutions', 'icon_key')) {
            Schema::table('solutions', function (Blueprint $table) {
                $table->dropColumn('icon_key');
            });
        }
    }

    public function down(): void
    {
        Schema::table('solutions', function (Blueprint $table) {
            $table->string('btn_text')->nullable()->after('description');
            $table->string('btn_link')->nullable()->after('btn_text');
            $table->string('icon_key')->nullable()->after('btn_link');
            $table->dropColumn('icon');
        });
    }
};
