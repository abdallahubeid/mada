<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_portal_enabled')->default(true);

            // Hero
            $table->string('hero_badge_text')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_primary_cta_text')->nullable();
            $table->string('hero_primary_cta_url')->nullable();
            $table->string('hero_secondary_cta_text')->nullable();
            $table->string('hero_secondary_cta_url')->nullable();
            $table->boolean('is_hero_active')->default(true);

            // About & values
            $table->string('about_title')->nullable();
            $table->string('about_subtitle')->nullable();
            $table->text('vision_text')->nullable();
            $table->text('mission_text')->nullable();
            $table->json('values_json')->nullable();
            $table->boolean('is_about_active')->default(true);

            // Services
            $table->string('services_title')->nullable();
            $table->string('services_subtitle')->nullable();
            $table->json('services_json')->nullable();
            $table->boolean('is_services_active')->default(true);

            // Culture
            $table->string('culture_title')->nullable();
            $table->string('culture_subtitle')->nullable();
            $table->json('culture_perks_json')->nullable();
            $table->boolean('is_culture_active')->default(true);

            // Stats
            $table->string('stats_title')->nullable();
            $table->json('stats_json')->nullable();
            $table->boolean('is_stats_active')->default(true);

            // Careers teaser
            $table->string('careers_badge_text')->nullable();
            $table->string('careers_title')->nullable();
            $table->string('careers_subtitle')->nullable();
            $table->boolean('is_careers_active')->default(true);

            // FAQ
            $table->string('faq_title')->nullable();
            $table->string('faq_subtitle')->nullable();
            $table->json('faqs_json')->nullable();
            $table->boolean('is_faq_active')->default(true);

            // CTA banner
            $table->string('cta_title')->nullable();
            $table->text('cta_subtitle')->nullable();
            $table->string('cta_button_text')->nullable();
            $table->boolean('is_cta_active')->default(true);

            // Contact
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_address')->nullable();
            $table->string('office_hours')->nullable();
            $table->text('map_embed_url')->nullable();
            $table->boolean('is_contact_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_portal_settings');
    }
};
