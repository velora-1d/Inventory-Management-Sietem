<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        if (method_exists($admin, 'assignRole') && \Spatie\Permission\Models\Role::where('name', 'super_admin')->exists()) {
            $admin->syncRoles(['super_admin']);
        }
    }
}
