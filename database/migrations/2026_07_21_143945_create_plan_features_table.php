<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('feature_key')->nullable();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->index(['plan_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
