<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->default('monthly')->after('plan');
            $table->string('subscription_status', 20)->default('trial')->after('billing_cycle');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('renews_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'billing_cycle',
                'subscription_status',
                'trial_ends_at',
                'renews_at',
            ]);
        });
    }
};
