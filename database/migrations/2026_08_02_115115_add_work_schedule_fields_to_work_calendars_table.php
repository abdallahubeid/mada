<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_calendars', function (Blueprint $table) {
            $table->time('work_start_time')->nullable()->after('working_days');
            $table->time('work_end_time')->nullable()->after('work_start_time');
            $table->unsignedSmallInteger('grace_period_minutes')->default(15)->after('work_end_time');
            $table->json('weekend_days')->nullable()->after('grace_period_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('work_calendars', function (Blueprint $table) {
            $table->dropColumn([
                'work_start_time',
                'work_end_time',
                'grace_period_minutes',
                'weekend_days',
            ]);
        });
    }
};
