<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat Role
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $verifikatorRole = Role::firstOrCreate(['name' => 'Verifikator']);
        $penelitiRole = Role::firstOrCreate(['name' => 'Peneliti']);
        $perangkatDaerahRole = Role::firstOrCreate(['name' => 'Perangkat Daerah']);
        $tamuRole = Role::firstOrCreate(['name' => 'Tamu']);

        // Buat Permissions
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);

        // Assign Permissions ke Admin
        $superAdminRole->givePermissionTo(['view users', 'create users', 'edit users', 'delete users']);

        // Assign Permissions ke Verifikator
        $adminRole->givePermissionTo(['view users']);
        $verifikatorRole->givePermissionTo(['view users']);
    }
}
