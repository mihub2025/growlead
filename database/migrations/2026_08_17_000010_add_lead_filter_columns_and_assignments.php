<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('meta_created_at')->nullable()->after('next_followup_at');
            $table->timestamp('last_assigned_at')->nullable()->after('assigned_user_id');
            $table->unsignedInteger('times_assigned')->default(0)->after('last_assigned_at');

            $table->index(['organization_id', 'updated_at']);
            $table->index(['organization_id', 'meta_created_at']);
            $table->index(['organization_id', 'last_assigned_at']);
            $table->index(['organization_id', 'times_assigned']);
        });

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'lead_id']);
            $table->index(['lead_id', 'assigned_at']);
        });

        Schema::table('lead_activities', function (Blueprint $table) {
            $table->index(['lead_id', 'type'], 'lead_activities_lead_type_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['lead_id', 'status', 'due_at'], 'tasks_lead_status_due_index');
            $table->index(['lead_id', 'type'], 'tasks_lead_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_lead_status_due_index');
            $table->dropIndex('tasks_lead_type_index');
        });

        Schema::table('lead_activities', function (Blueprint $table) {
            $table->dropIndex('lead_activities_lead_type_index');
        });

        Schema::dropIfExists('lead_assignments');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'updated_at']);
            $table->dropIndex(['organization_id', 'meta_created_at']);
            $table->dropIndex(['organization_id', 'last_assigned_at']);
            $table->dropIndex(['organization_id', 'times_assigned']);
            $table->dropColumn(['meta_created_at', 'last_assigned_at', 'times_assigned']);
        });
    }
};
