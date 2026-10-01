<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->string('external_campaign_id')->nullable();
            $table->string('objective')->nullable();
            $table->text('description')->nullable();
            $table->string('budget_type')->default('total');
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('draft');
            $table->string('routing_method')->default('round_robin');
            $table->unsignedInteger('target_leads')->nullable();
            $table->unsignedInteger('target_qualified_leads')->nullable();
            $table->decimal('target_cpl', 15, 2)->nullable();
            $table->decimal('target_revenue', 15, 2)->nullable();
            $table->decimal('total_spend', 15, 2)->default(0);
            $table->unsignedInteger('total_leads')->default(0);
            $table->unsignedInteger('qualified_leads')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->string('sync_status')->default('manual');
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('wizard_step')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'source_id']);
        });

        Schema::create('campaign_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weight')->default(1);
            $table->unique(['campaign_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_user');
        Schema::dropIfExists('campaigns');
    }
};
