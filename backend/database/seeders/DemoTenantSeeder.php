<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Agencia Demo
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-travel'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Qhapaq Travel Demo',
                'ruc' => '20601234567',
                'plan' => 'enterprise',
                'status' => 'activo',
                'currency' => 'USD',
                'fx_rate' => 3.75,
            ]
        );

        // 2. Fijar contexto de Team/Tenant en Spatie
        setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Clonar roles de plantilla para este tenant
        $templateRoles = Role::whereNull('tenant_id')->with('permissions')->get();
        foreach ($templateRoles as $template) {
            $tenantRole = Role::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $template->name,
                    'guard_name' => 'api',
                ],
                [
                    'id' => (string) Str::uuid(),
                    'display_name' => $template->display_name,
                    'scope' => $template->scope,
                    'experience' => $template->experience,
                ]
            );

            $permissionNames = $template->permissions->pluck('name')->toArray();
            $tenantRole->syncPermissions($permissionNames);
        }

        // 3. Crear Usuario Administrador de prueba
        $admin = User::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@agencia.com'],
            [
                'id' => (string) Str::uuid(),
                'code' => 'U01',
                'name' => 'Administrador Demo',
                'password' => Hash::make('Password123!'),
                'status' => 'activo',
            ]
        );

        $tenantAdminRole = Role::where('tenant_id', $tenant->id)
            ->where('name', 'tenant_admin')
            ->first();

        // Asignación directa con el pivot tenant_id garantizado
        if ($tenantAdminRole) {
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $tenantAdminRole->id,
                    'model_type' => User::class,
                    'model_id' => $admin->id,
                    'tenant_id' => $tenant->id,
                ]
            );
        }
    }
}