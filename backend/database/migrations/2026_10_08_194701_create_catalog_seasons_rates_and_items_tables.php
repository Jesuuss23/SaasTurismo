<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Waayiloota Waggaa (catalog_seasons)
        Schema::create('catalog_seasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('ref', 20); // TMP-01
            $table->string('name', 120);
            $table->string('type', 20); // Alta, Media, Baja, Feriado, Fecha especial
            $table->date('date_from');
            $table->date('date_to');
            $table->smallInteger('priority')->default(0); // 0 = dursa olaanaa
            $table->decimal('factor', 4, 2)->default(1.00);
            $table->string('status', 10)->default('activa'); // activa, inactiva
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'ref']);
            $table->index(['tenant_id', 'status']);
        });

        // 2. Gatiiwwan Oomishaa (catalog_rates)
        Schema::create('catalog_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->string('ref', 20); // TAR-001
            $table->string('rate_type', 15)->default('Venta'); // Costo, Venta, Retail, B2B
            $table->string('segment', 10)->default('Adulto'); // Adulto, Niño, Infante
            $table->string('channel', 12)->default('Directo'); // Directo, B2B, E-commerce, Todos
            $table->char('currency', 3)->default('USD');
            $table->string('price_basis', 15)->default('Por persona');
            $table->decimal('cost', 14, 2)->default(0.00);
            $table->decimal('price', 14, 2);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->foreignUuid('season_id')->nullable()->constrained('catalog_seasons')->nullOnDelete();
            $table->string('status', 10)->default('vigente'); // vigente, inactiva
            $table->integer('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'ref']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'valid_to']);
        });

        // 3. Komponentoota Oomishaa (catalog_product_items)
        Schema::create('catalog_product_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('catalog_services')->nullOnDelete();
            $table->foreignUuid('component_product_id')->nullable()->constrained('catalog_products')->nullOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('catalog_providers')->nullOnDelete();
            
            $table->decimal('quantity', 8, 2)->default(1.00);
            $table->string('unit', 20)->default('pax');
            $table->smallInteger('sort_order')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_included')->default(true);
            $table->decimal('unit_cost_override', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
        });

        // Eegumsa RLS Postgres
        DB::statement('ALTER TABLE catalog_seasons ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_seasons ON catalog_seasons USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE catalog_rates ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_rates ON catalog_rates USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE catalog_product_items ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_items ON catalog_product_items USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_items');
        Schema::dropIfExists('catalog_rates');
        Schema::dropIfExists('catalog_seasons');
    }
};