<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_normalized')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('whatsapp_normalized')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
            $table->string('status')->default('new');
            $table->string('priority')->default('medium');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('co_assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('interested_in')->nullable();
            $table->string('category')->nullable();
            $table->text('requirement')->nullable();
            $table->string('quantity')->nullable();
            $table->string('purpose')->nullable();
            $table->string('decision_timeline')->nullable();
            $table->decimal('min_budget', 15, 2)->nullable();
            $table->decimal('max_budget', 15, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('preferred_contact_method')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('preferred_contact_time')->nullable();
            $table->string('medium')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->boolean('email_opt_in')->default(true);
            $table->boolean('sms_opt_in')->default(true);
            $table->boolean('whatsapp_opt_in')->default(true);
            $table->boolean('do_not_contact')->default(false);
            $table->string('external_id')->nullable();
            $table->unsignedTinyInteger('lead_score')->nullable();
            $table->string('sentiment')->nullable();
            $table->unsignedTinyInteger('engagement_score')->nullable();
            $table->unsignedTinyInteger('conversion_probability')->nullable();
            $table->string('sla_status')->default('safe');
            $table->timestamp('sla_deadline_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('next_followup_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'assigned_user_id']);
            $table->index(['organization_id', 'assigned_team_id']);
            $table->index(['organization_id', 'campaign_id']);
            $table->index(['organization_id', 'source_id']);
            $table->index(['organization_id', 'pipeline_stage_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'priority']);
            $table->index(['organization_id', 'phone_normalized']);
            $table->index(['organization_id', 'whatsapp_normalized']);
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'external_id']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'last_activity_at']);
            $table->index(['organization_id', 'next_followup_at']);
        });

        Schema::create('lead_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['lead_id', 'tag_id']);
        });

        Schema::create('lead_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offering_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('category')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interests');
        Schema::dropIfExists('lead_tag');
        Schema::dropIfExists('leads');
    }
};
