<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Captures the organization-details step of the SaaS registration wizard
     * (docs/USER_JOURNEYS.md — Onboarding). These are informational metadata
     * only; they do not participate in the tenant lifecycle FSM in
     * app/Domain/Tenancy/Enums/TenantStatus.php.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('slug');
            $table->string('team_size')->nullable()->after('industry');
            $table->string('plan')->default('startup')->after('team_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['industry', 'team_size', 'plan']);
        });
    }
};
