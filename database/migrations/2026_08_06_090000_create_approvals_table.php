<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic polymorphic Approval Engine (ADR-08, MODULES.md §4.1).
 *
 * Backs Leave Requests, Payroll finalization, Expenses and Offboarding
 * settlement. No module may add approval-state columns to its own table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            /*
             * Deliberately NOT $table->morphs('approvable'): that helper adds its
             * own index led by approvable_type, which violates the tenant_id-leading
             * rule (NFR-06). The composite index below is declared explicitly.
             *
             * Values are short morph-map aliases ('leave_request', 'payroll_run'),
             * never fully-qualified class names (BR-902), so the column stays narrow.
             */
            $table->string('approvable_type', 64);
            $table->unsignedBigInteger('approvable_id');

            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedTinyInteger('current_level')->default(1);

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['tenant_id', 'approvable_type', 'approvable_id', 'status'],
                'approvals_tenant_subject_status_index'
            );
            $table->index(['tenant_id', 'status', 'current_level'], 'approvals_tenant_inbox_index');
            $table->index(['tenant_id', 'decided_by'], 'approvals_tenant_decided_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
