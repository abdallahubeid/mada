<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant-configurable expense categories (DATABASE_ROADMAP.md §2.4).
 *
 * Required for any meaningful cost breakdown on the Financial Dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'expense_categories_tenant_code_unique');
            $table->index(['tenant_id', 'is_active'], 'expense_categories_tenant_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
