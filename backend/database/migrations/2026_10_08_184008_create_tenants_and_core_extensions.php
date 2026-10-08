<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extensiones necesarias para UUIDv7 y búsquedas de texto
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm";');

        // 2. Tabla global de inquilinos (Tenants)
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 60)->unique();
            $table->string('ruc', 11)->nullable()->unique();
            $table->string('plan', 30)->default('starter'); // starter, professional, enterprise
            $table->string('status', 20)->default('activo'); // activo, suspendido, inactivo
            $table->string('timezone', 50)->default('America/Lima');
            $table->char('currency', 3)->default('USD');
            $table->decimal('fx_rate', 12, 6)->default(3.750000);
            $table->decimal('tax_pct', 5, 2)->default(18.00);
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};