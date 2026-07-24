<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('platform_settings');
    }

    public function down(): void
    {
        // Intentionally empty — platform_settings has been removed from the application.
    }
};
