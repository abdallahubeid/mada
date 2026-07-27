<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mandatory SoftDeletes on all core business tables (see .cursor/rules/soft-deletes.mdc).
 * Infrastructure tables (sessions, jobs, cache, permission pivots) are excluded.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'users',
        'faqs',
        'testimonials',
        'plan_features',
        'images',
        'platform_audit_logs',
        'settings',
        'problems',
        'solutions',
        'offerings',
        'modules',
        'ai_features',
        'features',
        'support_messages',
        'newsletter_campaigns',
        'platform_notifications',
    ];

    public function up(): void
    {
        // Soft-deleted settings must not block re-upsert of the same key.
        if (Schema::hasTable('settings')) {
            $this->ensureSettingsKeyIsNonUnique();
        }

        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('settings') && ! $this->settingsKeyHasUniqueIndex()) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->unique('key');
            });
        }
    }

    private function ensureSettingsKeyIsNonUnique(): void
    {
        if ($this->settingsKeyHasUniqueIndex()) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->dropUnique(['key']);
            });
        }

        if (! $this->settingsKeyHasIndex()) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->index('key');
            });
        }
    }

    private function settingsKeyHasUniqueIndex(): bool
    {
        foreach (Schema::getIndexes('settings') as $index) {
            if (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['key']) {
                return true;
            }
        }

        return false;
    }

    private function settingsKeyHasIndex(): bool
    {
        foreach (Schema::getIndexes('settings') as $index) {
            if (($index['columns'] ?? []) === ['key']) {
                return true;
            }
        }

        return false;
    }
};
