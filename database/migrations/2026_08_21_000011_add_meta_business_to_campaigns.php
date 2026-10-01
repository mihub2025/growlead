<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('meta_business_id')->nullable()->after('external_campaign_id');
            $table->string('meta_business_name')->nullable()->after('meta_business_id');
            $table->string('meta_ad_account_id')->nullable()->after('meta_business_name');
            $table->string('meta_ad_account_name')->nullable()->after('meta_ad_account_id');
            $table->index(['organization_id', 'meta_business_id'], 'campaigns_org_meta_business_index');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_org_meta_business_index');
            $table->dropColumn([
                'meta_business_id',
                'meta_business_name',
                'meta_ad_account_id',
                'meta_ad_account_name',
            ]);
        });
    }
};
