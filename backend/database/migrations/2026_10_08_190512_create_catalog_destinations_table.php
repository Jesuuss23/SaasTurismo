<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla con clave primaria explícita
        Schema::create('catalog_destinations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('ref', 20); // D01, D02...
            $table->uuid('parent_id')->nullable();
            $table->string('name', 120);
            $table->string('type', 20); // País, Región, Departamento, Provincia, Ciudad, Zona, Lugar, Atracción, Ruta
            $table->smallInteger('level')->default(0);
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->text('description')->nullable();
            $table->text('path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'ref']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'type']);
        });

        // 2. Agregar la FK autoreferenciada una vez que la tabla y PK ya existen
        Schema::table('catalog_destinations', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('catalog_destinations')->nullOnDelete();
        });

        // 3. Activar RLS
        DB::statement('ALTER TABLE catalog_destinations ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_destinations ON catalog_destinations USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_destinations');
    }
};