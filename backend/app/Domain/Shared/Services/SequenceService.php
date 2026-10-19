<?php

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SequenceService
{
    public static function nextCode(string $tenantId, string $entityType): string
    {
        $year = date('Y');

        $configs = [
            'reservation' => ['prefix' => "RES-{$year}-", 'pad' => 6, 'table' => 'booking_reservations', 'col' => 'code'],
            'quote'       => ['prefix' => "COT-{$year}-", 'pad' => 5, 'table' => 'quote_quotes',          'col' => 'code'],
            'client'      => ['prefix' => 'CLI-',         'pad' => 4, 'table' => 'crm_clients',           'col' => 'code'],
            'lead'        => ['prefix' => 'LEAD-',        'pad' => 4, 'table' => 'crm_leads',             'col' => 'code'],
            'opportunity' => ['prefix' => 'OPP-',         'pad' => 4, 'table' => 'crm_opportunities',     'col' => 'code'],
            'product'     => ['prefix' => 'PRD-',         'pad' => 3, 'table' => 'catalog_products',       'col' => 'ref'],
            'destination' => ['prefix' => 'D',            'pad' => 2, 'table' => 'catalog_destinations',   'col' => 'ref'],
            'provider'    => ['prefix' => 'PRV-',         'pad' => 3, 'table' => 'catalog_providers',      'col' => 'ref'],
            'service'     => ['prefix' => 'SRV-',         'pad' => 3, 'table' => 'catalog_services',       'col' => 'ref'],
            'allotment'   => ['prefix' => "ALT-{$year}-", 'pad' => 3, 'table' => 'catalog_allotments',    'col' => 'code'],
            'b2b_agency'  => ['prefix' => 'B2B-',         'pad' => 3, 'table' => 'channels_b2b_agencies',  'col' => 'code'],
            'voucher'     => ['prefix' => "VOU-{$year}-", 'pad' => 5, 'table' => 'ops_services',          'col' => 'voucher_code'],
            'payment'     => ['prefix' => "REC-{$year}-", 'pad' => 5, 'table' => 'fin_payments',          'col' => 'code'],
            'claim'       => ['prefix' => "CLA-{$year}-", 'pad' => 5, 'table' => 'crm_claims',            'col' => 'code'],
            'approval'    => ['prefix' => "APR-{$year}-", 'pad' => 4, 'table' => 'collab_approvals',      'col' => 'code'],
            'payable'      => ['prefix' => "CXP-{$year}-", 'pad' => 4, 'table' => 'fin_payables',      'col' => 'code'],
            'disbursement' => ['prefix' => "DSP-{$year}-", 'pad' => 4, 'table' => 'fin_disbursements', 'col' => 'code'],
        ];

        $cfg = $configs[$entityType] ?? [
            'prefix' => strtoupper($entityType) . '-',
            'pad'    => 4,
            'table'  => null,
            'col'    => 'code',
        ];

        return DB::transaction(function () use ($tenantId, $entityType, $cfg, $year) {
            $seq = DB::table('sys_sequences')
                ->where('tenant_id', $tenantId)
                ->where('doc_type', $entityType)
                ->lockForUpdate()
                ->first();

            $currentMax = 0;
            if (!empty($cfg['table']) && Schema::hasTable($cfg['table'])) {
                $currentMax = DB::table($cfg['table'])
                    ->where('tenant_id', $tenantId)
                    ->count();
            }

            if (!$seq) {
                $assignedNumber = max(1, $currentMax + 1);

                DB::table('sys_sequences')->insert([
                    'id'          => (string) Str::uuid(),
                    'tenant_id'   => $tenantId,
                    'doc_type'    => $entityType,
                    'label'       => ucfirst($entityType),
                    'series'      => $year,
                    'prefix'      => $cfg['prefix'],
                    'pad_width'   => $cfg['pad'],
                    'next_number' => $assignedNumber + 1,
                    'year_reset'  => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                $assignedNumber = (int) $seq->next_number;
                if ($currentMax >= $assignedNumber) {
                    $assignedNumber = $currentMax + 1;
                }

                DB::table('sys_sequences')
                    ->where('id', $seq->id)
                    ->update([
                        'next_number' => $assignedNumber + 1,
                        'updated_at'  => now(),
                    ]);
            }

            // Validación de colisión por código formateado
            if (!empty($cfg['table']) && Schema::hasTable($cfg['table'])) {
                while (true) {
                    $candidate = $cfg['prefix'] . str_pad((string) $assignedNumber, $cfg['pad'], '0', STR_PAD_LEFT);
                    $exists = DB::table($cfg['table'])
                        ->where('tenant_id', $tenantId)
                        ->where($cfg['col'], $candidate)
                        ->exists();

                    if (!$exists) {
                        return $candidate;
                    }

                    $assignedNumber++;
                }
            }

            return $cfg['prefix'] . str_pad((string) $assignedNumber, $cfg['pad'], '0', STR_PAD_LEFT);
        });
    }
}