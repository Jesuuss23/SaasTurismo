<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ops_services', function (Blueprint $table) {
            $table->string('provider_confirmation_code', 60)->nullable()->after('voucher_code');
            $table->timestamp('provider_confirmed_at')->nullable()->after('provider_confirmation_code');
            $table->text('provider_notes')->nullable()->after('provider_confirmed_at');
            $table->string('rejection_reason', 200)->nullable()->after('provider_notes');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('ops_services', function (Blueprint $table) {
            $table->dropColumn([
                'provider_confirmation_code',
                'provider_confirmed_at',
                'provider_notes',
                'rejection_reason',
                'rejected_at',
            ]);
        });
    }
};