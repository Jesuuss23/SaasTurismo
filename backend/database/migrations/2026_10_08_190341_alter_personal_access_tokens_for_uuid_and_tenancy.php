<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recrear la tabla con tokenable_id uuid y campos de tenancy
        Schema::dropIfExists('personal_access_tokens');

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('tokenable_id'); // UUID para que encaje con iam_users
            $table->string('tokenable_type');
            $table->uuid('tenant_id')->nullable(); // Tenancy del token
            $table->string('experience', 20)->default('backoffice'); // backoffice, portal, mobile
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('mfa_verified_at')->nullable();
            $table->string('device_name', 120)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
            $table->index(['tenant_id']);
        });

        // Activar RLS en personal_access_tokens
        DB::statement('ALTER TABLE personal_access_tokens ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_tokens ON personal_access_tokens USING (tenant_id IS NULL OR tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};