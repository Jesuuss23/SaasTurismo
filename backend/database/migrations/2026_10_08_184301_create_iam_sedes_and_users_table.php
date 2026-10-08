<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sedes
        Schema::create('iam_sedes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 120);
            $table->string('address', 200)->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        // Tabla de Usuarios extendida (iam_users)
        Schema::create('iam_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('password')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignUuid('sede_id')->nullable()->constrained('iam_sedes')->nullOnDelete();
            $table->string('status', 20)->default('pendiente'); // pendiente, activo, inactivo
            $table->boolean('mfa_enabled')->default(false);
            $table->boolean('mfa_required')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            
            // Relaciones polimórficas de roles externos
            $table->uuid('client_id')->nullable();
            $table->uuid('agency_id')->nullable();
            $table->uuid('provider_id')->nullable();
            $table->uuid('guide_id')->nullable();
            $table->uuid('driver_id')->nullable();
            $table->uuid('reservation_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
        });

        // 3. Activar RLS en Postgres
        DB::statement('ALTER TABLE iam_sedes ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_sedes ON iam_sedes USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE iam_users ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_users ON iam_users USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('iam_users');
        Schema::dropIfExists('iam_sedes');
    }
};