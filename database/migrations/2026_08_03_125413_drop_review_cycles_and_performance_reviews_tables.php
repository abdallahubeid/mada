<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('review_cycles');
    }

    public function down(): void
    {
        // Intentionally empty — legacy cycle/review tables are not recreated.
    }
};
