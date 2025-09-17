<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plantillas_whatsapp', function (Blueprint $table) {
            if (!Schema::hasColumn('plantillas_whatsapp', 'subject')) {
                $table->string('subject')->default('');
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'message')) {
                $table->text('message')->nullable();
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'category')) {
                $table->string('category')->default('general');
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'status')) {
                $table->string('status')->default('draft');
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'target_audience')) {
                $table->string('target_audience')->default('all');
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'variables')) {
                $table->json('variables')->nullable();
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'priority')) {
                $table->string('priority')->default('medium');
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'has_attachment')) {
                $table->boolean('has_attachment')->default(false);
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'is_schedulable')) {
                $table->boolean('is_schedulable')->default(false);
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0);
            }
            if (!Schema::hasColumn('plantillas_whatsapp', 'last_used')) {
                $table->timestamp('last_used')->nullable();
            }
        });

        // Copy existing content -> message when message is empty
        if (Schema::hasColumn('plantillas_whatsapp', 'content') && Schema::hasColumn('plantillas_whatsapp', 'message')) {
            DB::statement("UPDATE plantillas_whatsapp SET message = content WHERE (message IS NULL OR message = '') AND content IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plantillas_whatsapp', function (Blueprint $table) {
            if (Schema::hasColumn('plantillas_whatsapp', 'subject')) {
                $table->dropColumn('subject');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'message')) {
                $table->dropColumn('message');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'target_audience')) {
                $table->dropColumn('target_audience');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'variables')) {
                $table->dropColumn('variables');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'has_attachment')) {
                $table->dropColumn('has_attachment');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'is_schedulable')) {
                $table->dropColumn('is_schedulable');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'usage_count')) {
                $table->dropColumn('usage_count');
            }
            if (Schema::hasColumn('plantillas_whatsapp', 'last_used')) {
                $table->dropColumn('last_used');
            }
        });
    }
};

