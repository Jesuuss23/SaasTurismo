<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Gosoota Oomishaa (catalog_product_types)
        Schema::create('catalog_product_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 60); // TOUR, EXCURSIÓN, PAQUETE, CIRCUITO...
            $table->boolean('requires_itinerary')->default(false);
            $table->boolean('requires_components')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
        });

        // 2. Ramaddii Oomishaa (catalog_product_categories)
        Schema::create('catalog_product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('ref', 20); // CAT-1, CAT-2...
            $table->string('name', 80);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'ref']);
        });

        // 3. Ramaddii Xiqqaa (catalog_product_subcategories)
        Schema::create('catalog_product_subcategories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('catalog_product_categories')->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['category_id', 'name']);
        });

        // 4. Oomishaalee Bu'uuraa (catalog_products)
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('ref', 20); // PRD-001
            $table->string('code', 30); // Qodaa keessoo
            $table->string('sku', 40);
            $table->string('name', 160);
            $table->string('internal_name', 160)->nullable();
            $table->foreignUuid('product_type_id')->constrained('catalog_product_types');
            $table->foreignUuid('category_id')->constrained('catalog_product_categories');
            $table->foreignUuid('subcategory_id')->nullable()->constrained('catalog_product_subcategories')->nullOnDelete();
            $table->foreignUuid('primary_destination_id')->constrained('catalog_destinations');
            $table->foreignUuid('main_provider_id')->nullable()->constrained('catalog_providers')->nullOnDelete();
            
            $table->string('status', 20)->default('DRAFT'); // DRAFT, REVIEW, APPROVED, ACTIVE, SUSPENDED, INACTIVE, ARCHIVED
            $table->text('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->decimal('duration_value', 6, 1)->default(1);
            $table->string('duration_unit', 10)->default('días'); // horas, días, noches
            $table->string('modality', 15)->default('Grupal'); // Privado, Compartido, Grupal
            
            $table->smallInteger('minimum_passengers')->default(1);
            $table->smallInteger('maximum_passengers')->default(20);
            $table->smallInteger('minimum_age')->default(0);
            $table->smallInteger('maximum_age')->default(99);
            
            $table->boolean('is_bookable')->default(true);
            $table->boolean('is_quoteable')->default(true);
            $table->boolean('is_b2b')->default(false);
            $table->boolean('is_ecommerce')->default(false);
            $table->decimal('markup_pct', 6, 2)->default(25.00);
            $table->string('availability_model', 20)->default('SIN_CONTROL');
            
            $table->string('slug', 160)->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'ref']);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        // Eegumsa RLS dabalataa mirkaneessuu
        DB::statement('ALTER TABLE catalog_product_types ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_prod_types ON catalog_product_types USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE catalog_product_categories ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_categories ON catalog_product_categories USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE catalog_products ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_products ON catalog_products USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
        Schema::dropIfExists('catalog_product_subcategories');
        Schema::dropIfExists('catalog_product_categories');
        Schema::dropIfExists('catalog_product_types');
    }
};