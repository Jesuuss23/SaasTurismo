<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sadarkaalee Daandii Gurgurtaa (crm_pipeline_stages)
        Schema::create('crm_pipeline_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // calificacion, descubrimiento, cotizacion, negociacion, ganada, perdida
            $table->string('name', 60);
            $table->smallInteger('default_probability')->default(20);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        // 2. Carraawwan Gurgurtaa (crm_opportunities)
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // OPP-0001
            $table->foreignUuid('client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('stage', 30)->default('descubrimiento');
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->char('currency', 3)->default('USD');
            $table->smallInteger('probability')->default(35);
            $table->date('expected_close_date');
            $table->date('closed_at')->nullable();
            $table->foreignUuid('owner_id')->constrained('iam_users');
            $table->string('destination_label', 120)->nullable();
            $table->smallInteger('pax')->default(1);
            $table->foreignUuid('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('catalog_products')->nullOnDelete();
            $table->string('origin', 40)->default('Manual');
            $table->text('notes')->nullable();
            $table->string('lost_reason_code', 40)->nullable();
            $table->text('lost_detail')->nullable();
            $table->string('lost_at_stage', 30)->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'stage']);
            $table->index(['tenant_id', 'owner_id']);
        });

        // Eegumsa RLS Postgres mirkaneessuu
        DB::statement('ALTER TABLE crm_pipeline_stages ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_stages ON crm_pipeline_stages USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE crm_opportunities ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_opportunities ON crm_opportunities USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_pipeline_stages');
    }
};