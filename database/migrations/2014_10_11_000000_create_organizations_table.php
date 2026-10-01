<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->default('generic');
            $table->string('logo')->nullable();
            $table->string('currency', 8)->default('USD');
            $table->string('country', 8)->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('language', 8)->default('en');
            $table->string('date_format')->default('Y-m-d');
            $table->string('phone_country', 8)->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('sla_minutes')->default(15);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
