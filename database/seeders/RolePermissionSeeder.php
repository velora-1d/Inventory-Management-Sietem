<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'pos.access',
            'sales.view',
            'sales.create',
            'sales.manage',
            'orders.view',
            'orders.create',
            'orders.manage',
            'master.view',
            'master.manage',
            'inventory.view',
            'inventory.manage',
            'inventory.adjust',
            'purchases.view',
            'purchases.create',
            'purchases.approve',
            'purchases.receive',
            'work.view',
            'work.manage',
            'work.assign',
            'work.qc',
            'shipping.view',
            'shipping.manage',
            'finance.view',
            'finance.manage',
            'finance.verify_payment',
            'reports.view',
            'users.view',
            'users.manage',
            'settings.view',
            'settings.manage',
            'audit.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'owner' => $permissions,
            'super_admin' => $permissions,
            'admin' => [
                'dashboard.view',
                'pos.access',
                'sales.view',
                'sales.create',
                'sales.manage',
                'orders.view',
                'orders.manage',
                'master.view',
                'master.manage',
                'inventory.view',
                'inventory.manage',
                'purchases.view',
                'purchases.create',
                'work.view',
                'work.manage',
                'work.assign',
                'shipping.view',
                'shipping.manage',
                'finance.view',
                'finance.verify_payment',
                'reports.view',
                'audit.view',
            ],
            'manager' => [
                'dashboard.view',
                'sales.view',
                'orders.view',
                'orders.manage',
                'master.view',
                'inventory.view',
                'inventory.adjust',
                'purchases.view',
                'purchases.approve',
                'work.view',
                'work.manage',
                'work.assign',
                'work.qc',
                'shipping.view',
                'finance.view',
                'reports.view',
                'audit.view',
            ],
            'purchasing' => [
                'dashboard.view',
                'master.view',
                'inventory.view',
                'purchases.view',
                'purchases.create',
            ],
            'warehouse' => [
                'dashboard.view',
                'master.view',
                'inventory.view',
                'inventory.manage',
                'purchases.view',
                'purchases.receive',
                'work.view',
                'work.manage',
            ],
            'qc_packing' => [
                'dashboard.view',
                'orders.view',
                'inventory.view',
                'work.view',
                'work.manage',
                'work.qc',
            ],
            'cashier' => [
                'dashboard.view',
                'pos.access',
                'sales.view',
                'sales.create',
                'master.view',
                'inventory.view',
            ],
            'finance' => [
                'dashboard.view',
                'sales.view',
                'orders.view',
                'purchases.view',
                'finance.view',
                'finance.manage',
                'finance.verify_payment',
                'reports.view',
            ],
            'shipping' => [
                'dashboard.view',
                'orders.view',
                'work.view',
                'shipping.view',
                'shipping.manage',
            ],
            'customer' => [
                'orders.view',
                'orders.create',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($perms);
        }

        // Ensure existing default admin account holds super_admin role
        $admin = User::where('username', 'admin')
            ->orWhere('email', 'admin@admin.com')
            ->first();

        if ($admin && !$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
