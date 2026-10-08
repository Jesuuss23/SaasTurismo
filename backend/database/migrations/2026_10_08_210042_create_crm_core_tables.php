<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tarree Kaataaloogii CRM (crm_catalog_items: madda, tajaajila, sababoota dhabamuu...)
        Schema::create('crm_catalog_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('kind', 30); // lead_source, lead_channel, lead_interest, lost_reason, discard_reason...
            $table->string('code', 40);
            $table->string('label', 120);
            $table->boolean('requires_detail')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'kind', 'code']);
        });

        // 2. Caasaa Maamiltoota Bu'uuraa (crm_clients)
        Schema::create('crm_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // CLI-0001
            $table->string('kind', 10)->default('persona'); // persona, empresa
            $table->string('name', 200);
            $table->string('doc_type', 10)->default('DNI'); // DNI, RUC, PASAPORTE, CE
            $table->string('doc_number', 20);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('country', 80)->default('Perú');
            $table->string('city', 80)->nullable();
            $table->string('segment_code', 40)->default('Retail'); // Retail, Corporativo, VIP, Grupos, B2B
            $table->foreignUuid('owner_id')->constrained('iam_users');
            $table->smallInteger('nps_last')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'owner_id']);
        });

        // 3. Tarree Leads (crm_leads)
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // LEAD-0001
            $table->string('full_name', 160);
            $table->string('company', 160)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('channel_code', 40)->default('Web');
            $table->string('source_code', 40)->default('Formulario web');
            $table->string('interest_code', 40)->default('Tour');
            $table->foreignUuid('destination_id')->nullable()->constrained('catalog_destinations')->nullOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('catalog_products')->nullOnDelete();
            $table->date('travel_date')->nullable();
            $table->smallInteger('pax')->default(1);
            $table->string('status', 30)->default('nuevo'); // nuevo, contactado, calificado, convertido, duplicado, descartado
            $table->foreignUuid('owner_id')->nullable()->constrained('iam_users')->nullOnDelete();
            $table->smallInteger('score')->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('converted_client_id')->nullable()->constrained('crm_clients')->nullOnDelete();
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'owner_id']);
        });

        // Eegumsa RLS Postgres
        DB::statement('ALTER TABLE crm_catalog_items ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_crm_items ON crm_catalog_items USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE crm_clients ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_crm_clients ON crm_clients USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE crm_leads ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_crm_leads ON crm_leads USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_clients');
        Schema::dropIfExists('crm_catalog_items');
    }
};