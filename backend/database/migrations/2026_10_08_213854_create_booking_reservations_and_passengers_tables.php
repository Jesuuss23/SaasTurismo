<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Mataa Qabiinsa Iddoo (booking_reservations)
        Schema::create('booking_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // RES-2026-000001
            $table->foreignUuid('client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignUuid('quote_id')->nullable()->constrained('quote_quotes')->nullOnDelete();
            $table->foreignUuid('owner_id')->constrained('iam_users');
            $table->string('status', 30)->default('confirmada'); // borrador, confirmada, en_operacion, completada, cancelada
            $table->date('travel_date_start');
            $table->date('travel_date_end')->nullable();
            $table->smallInteger('pax')->default(1);
            $table->char('currency', 3)->default('USD');
            $table->decimal('fx_rate', 12, 6)->default(3.750000);
            $table->decimal('total', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('balance', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'owner_id']);
        });

        // 2. Tarree Imaltootaa (booking_passengers)
        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('reservation_id')->constrained('booking_reservations')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('doc_type', 20)->default('PASAPORTE'); // PASAPORTE, DNI, CE
            $table->string('doc_number', 30);
            $table->string('nationality', 60)->default('Extranjero');
            $table->date('birth_date')->nullable();
            $table->string('passenger_type', 20)->default('Adulto'); // Adulto, Niño, Infante
            $table->boolean('is_lead')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'reservation_id']);
        });

        // Eegumsa RLS Postgres mirkaneessuu
        DB::statement('ALTER TABLE booking_reservations ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_reservations ON booking_reservations USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE booking_passengers ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_passengers ON booking_passengers USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_passengers');
        Schema::dropIfExists('booking_reservations');
    }
};