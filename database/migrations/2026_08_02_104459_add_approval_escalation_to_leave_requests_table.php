<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->boolean('requires_manager_escalation')->default(false)->after('status');
            $table->unsignedTinyInteger('approval_level')->default(1)->after('requires_manager_escalation');
            $table->unsignedTinyInteger('current_approval_level')->default(0)->after('approval_level');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'requires_manager_escalation',
                'approval_level',
                'current_approval_level',
            ]);
        });
    }
};
