<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority', 16)->default('medium');
            $table->string('status', 32)->default('todo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'status']);
            $table->index(['tenant_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
