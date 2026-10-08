<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Catálogo completo de 57 Permisos
        $permissionGroups = [
            'Administración' => [
                'admin.tenant.manage', 'admin.users.manage', 'admin.roles.manage',
                'admin.settings.manage', 'admin.audit.view', 'admin.billing.manage', 'admin.api.manage',
            ],
            'Colaboración' => [
                'approvals.view', 'inbox.view', 'inbox.manage',
            ],
            'CRM' => [
                'crm.leads.view', 'crm.leads.manage', 'crm.opportunities.manage',
                'crm.activities.manage', 'crm.clients.manage', 'crm.targets.view',
                'crm.targets.manage', 'crm.claims.manage',
            ],
            'Marketing' => [
                'marketing.campaigns.manage',
            ],
            'Catálogo' => [
                'catalog.products.view', 'catalog.products.create', 'catalog.products.update',
                'catalog.products.activate', 'catalog.products.manage_rates',
                'catalog.products.manage_availability', 'catalog.products.view_profitability',
                'catalog.products.publish', 'catalog.providers.manage', 'catalog.allotments.manage',
            ],
            'Cotizaciones' => [
                'quotes.view', 'quotes.create', 'quotes.approve_internal',
                'quotes.send', 'quotes.discount_override',
            ],
            'Reservas' => [
                'reservations.view', 'reservations.create', 'reservations.modify', 'reservations.cancel',
            ],
            'Operaciones' => [
                'operations.view', 'operations.assign', 'operations.vouchers',
                'operations.incidents', 'operations.close', 'operations.travel_docs',
            ],
            'Finanzas' => [
                'finance.receivables', 'finance.payments.register', 'finance.payments.revert',
                'finance.payables', 'finance.commissions', 'finance.profitability',
                'finance.invoicing', 'finance.treasury',
            ],
            'Canales' => [
                'channels.b2b.manage', 'channels.ecommerce.manage', 'channels.cms.publish',
            ],
            'Reportes' => [
                'reports.view', 'reports.export',
            ],
        ];

        foreach ($permissionGroups as $module => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(
                    ['name' => $perm, 'guard_name' => 'api'],
                    ['id' => (string) Str::uuid(), 'module' => $module]
                );
            }
        }

        // 2. Definición de los 13 Roles base
        $rolesData = [
            'tenant_admin' => [
                'display' => 'Tenant Admin',
                'scope' => 'tenant', 'exp' => 'backoffice',
                'perms' => Permission::where('name', '!=', 'admin.tenant.manage')->pluck('name')->toArray(),
            ],
            'manager' => [
                'display' => 'Manager',
                'scope' => 'tenant', 'exp' => 'backoffice',
                'perms' => [
                    'admin.audit.view', 'approvals.view', 'inbox.view', 'inbox.manage',
                    'crm.leads.view', 'crm.leads.manage', 'crm.opportunities.manage', 'crm.activities.manage',
                    'crm.clients.manage', 'crm.targets.view', 'crm.targets.manage', 'crm.claims.manage',
                    'marketing.campaigns.manage',
                    'catalog.products.view', 'catalog.products.create', 'catalog.products.update',
                    'catalog.products.activate', 'catalog.products.manage_rates',
                    'catalog.products.manage_availability', 'catalog.products.view_profitability',
                    'catalog.products.publish', 'catalog.allotments.manage',
                    'quotes.view', 'quotes.create', 'quotes.approve_internal', 'quotes.send', 'quotes.discount_override',
                    'reservations.view', 'reservations.create', 'reservations.modify', 'reservations.cancel',
                    'operations.view', 'operations.assign', 'operations.vouchers', 'operations.incidents',
                    'operations.close', 'operations.travel_docs',
                    'finance.receivables', 'finance.payables', 'finance.commissions', 'finance.profitability', 'finance.treasury',
                    'channels.b2b.manage', 'channels.ecommerce.manage', 'channels.cms.publish',
                    'reports.view', 'reports.export',
                ],
            ],
            'sales' => [
                'display' => 'Ventas (Sales)',
                'scope' => 'tenant', 'exp' => 'backoffice',
                'perms' => [
                    'crm.leads.view', 'crm.leads.manage', 'crm.opportunities.manage',
                    'crm.activities.manage', 'crm.clients.manage', 'crm.targets.view',
                    'catalog.products.view', 'quotes.view', 'quotes.create', 'quotes.send',
                    'reservations.view', 'reservations.create', 'reports.view',
                    'approvals.view', 'inbox.view', 'inbox.manage',
                ],
            ],
            'operations' => [
                'display' => 'Operaciones',
                'scope' => 'tenant', 'exp' => 'backoffice',
                'perms' => [
                    'catalog.products.view', 'catalog.products.manage_availability', 'catalog.providers.manage',
                    'catalog.allotments.manage', 'reservations.view', 'reservations.modify',
                    'operations.view', 'operations.assign', 'operations.vouchers', 'operations.incidents',
                    'operations.close', 'operations.travel_docs', 'reports.view',
                    'approvals.view', 'inbox.view', 'inbox.manage',
                ],
            ],
            'finance' => [
                'display' => 'Finanzas',
                'scope' => 'tenant', 'exp' => 'backoffice',
                'perms' => [
                    'finance.receivables', 'finance.payments.register', 'finance.payments.revert',
                    'finance.payables', 'finance.commissions', 'finance.profitability', 'finance.invoicing',
                    'finance.treasury', 'reservations.view', 'quotes.view', 'catalog.products.view',
                    'catalog.products.view_profitability', 'reports.view', 'reports.export', 'approvals.view',
                ],
            ],
            'accounting' => [
                'display' => 'Contabilidad',
                'scope' => 'tenant', 'exp' => 'backoffice',
                'perms' => [
                    'finance.invoicing', 'finance.receivables', 'reservations.view',
                    'reports.view', 'reports.export', 'finance.treasury',
                ],
            ],
            'guide' => [
                'display' => 'Guía',
                'scope' => 'mobile', 'exp' => 'mobile',
                'perms' => ['operations.view', 'operations.incidents'],
            ],
            'driver' => [
                'display' => 'Conductor',
                'scope' => 'mobile', 'exp' => 'mobile',
                'perms' => ['operations.view', 'operations.incidents'],
            ],
            'b2b' => [
                'display' => 'Usuario B2B',
                'scope' => 'canal', 'exp' => 'portal',
                'perms' => ['catalog.products.view', 'quotes.create', 'reservations.create', 'reservations.view'],
            ],
            'customer' => [
                'display' => 'Cliente',
                'scope' => 'canal', 'exp' => 'portal',
                'perms' => ['quotes.view', 'reservations.view'],
            ],
            'passenger' => [
                'display' => 'Pasajero',
                'scope' => 'canal', 'exp' => 'portal',
                'perms' => ['reservations.view'],
            ],
            'provider' => [
                'display' => 'Proveedor',
                'scope' => 'canal', 'exp' => 'portal',
                'perms' => ['operations.view'],
            ],
        ];

        foreach ($rolesData as $roleName => $data) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'api', 'tenant_id' => null],
                [
                    'id' => (string) Str::uuid(),
                    'display_name' => $data['display'],
                    'scope' => $data['scope'],
                    'experience' => $data['exp'],
                ]
            );

            $role->syncPermissions($data['perms']);
        }
    }
}