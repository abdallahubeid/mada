<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_settings', function (Blueprint $table) {
            $table->string('evaluation_periodicity', 32)->default('quarterly')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('org_settings', function (Blueprint $table) {
            $table->dropColumn('evaluation_periodicity');
        });
    }
};
