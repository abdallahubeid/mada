<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('self_rating', 3, 2)->nullable();
            $table->decimal('manager_rating', 3, 2)->nullable();
            $table->decimal('final_score', 3, 2)->nullable();
            $table->text('self_comments')->nullable();
            $table->text('manager_comments')->nullable();
            $table->string('status', 32)->default('pending_self');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_id', 'review_cycle_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
