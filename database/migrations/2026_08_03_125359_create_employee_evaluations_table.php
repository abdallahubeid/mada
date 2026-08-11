<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('period_type', 32);
            $table->string('period_key', 32);
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'employee_id', 'period_type', 'period_key'],
                'employee_evaluations_period_unique'
            );
            $table->index(['tenant_id', 'period_type', 'period_key']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_evaluations');
    }
};
