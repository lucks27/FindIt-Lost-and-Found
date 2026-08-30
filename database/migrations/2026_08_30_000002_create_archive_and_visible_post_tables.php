<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('archive_items')) {
            Schema::create('archive_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
                $table->string('status')->default('completed');
                $table->string('archive_type')->default('completed');
                $table->text('reason')->nullable();
                $table->foreignId('archived_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'archived_at']);
            });
        }

        if (!Schema::hasTable('visible_posted_items')) {
            Schema::create('visible_posted_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
                $table->string('post_type')->default('public');
                $table->boolean('is_visible')->default(true);
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('posted_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_visible', 'posted_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visible_posted_items');
        Schema::dropIfExists('archive_items');
    }
};
