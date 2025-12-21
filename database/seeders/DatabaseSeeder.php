<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ministry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Panggil Seeder Status & Ministry dulu (Biar rapi)
        $this->call([
            StatusSeeder::class,
            MinistrySeeder::class,
        ]);
        
        $this->command->info('✅ Status & Ministry seeded!');

        // 2. Setup Roles & Permissions
        $this->setupRolesAndPermissions();
        $this->command->info('✅ Roles & Permissions created!');

        // 3. Create Users
        $this->createUsers();
        $this->command->info('✅ All Users created successfully!');
        
        // Clear cache biar permission langsung ngefek
        Artisan::call('permission:cache-reset');
    }

    private function setupRolesAndPermissions()
    {
        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $presidenRole = Role::firstOrCreate(['name' => 'Presiden BEM']);
        $wakilPresidenRole = Role::firstOrCreate(['name' => 'Wakil Presiden BEM']);
        $sekretarisRole = Role::firstOrCreate(['name' => 'Sekretaris']);
        $bendaharaRole = Role::firstOrCreate(['name' => 'Bendahara']);
        $menteriRole = Role::firstOrCreate(['name' => 'Menteri']);
        $anggotaRole = Role::firstOrCreate(['name' => 'Anggota']);

        // Create Permissions
        $permissions = [
            'user' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'ministry' => ['view_any', 'view', 'create', 'update', 'delete'],
            'proposal' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'program_kerja' => ['view_any', 'view', 'create', 'update', 'delete'],
            'shield::role' => ['view_any', 'view', 'create', 'update', 'delete'],
            'activity_log' => ['view_any', 'view'],
        ];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => $action . '_' . $module]);
            }
        }

        // Assign Permissions to Roles
        
        // Super Admin: All Access
        $superAdminRole->givePermissionTo(Permission::all());

        // Presiden: All except Shield & Log
        $presidenRole->givePermissionTo(Permission::where('name', 'not like', '%shield%')->where('name', 'not like', '%activity_log%')->get());

        // Wakil: Mirip Presiden tapi limited delete
        $wakilPresidenRole->givePermissionTo([
            'view_any_user', 'view_user', 'create_user', 'update_user',
            'view_any_ministry', 'view_ministry', 'create_ministry', 'update_ministry',
            'view_any_proposal', 'view_proposal', 'create_proposal', 'update_proposal', 'delete_proposal',
            'view_any_program_kerja', 'view_program_kerja', 'create_program_kerja', 'update_program_kerja', 'delete_program_kerja',
        ]);

        // Setup role lain sesuai kebutuhan (simplified for readability here)
        $anggotaRole->givePermissionTo(['view_any_proposal', 'view_proposal', 'create_proposal', 'view_any_program_kerja', 'view_program_kerja', 'create_program_kerja']);
    }

    private function createUsers()
    {
        // 1. SUPER ADMIN (PENTING)
        $admin = User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // Password default
                'ministry_id' => null,
            ]
        );
        $admin->assignRole('Super Admin');

        // 2. Presiden
        $presiden = User::updateOrCreate(
            ['email' => 'presiden@mail.com'],
            [
                'name' => 'Presiden BEM',
                'password' => Hash::make('password'),
                'ministry_id' => null,
            ]
        );
        $presiden->assignRole('Presiden BEM');

        // 3. Menteri (Contoh assigned ke ministry pertama)
        $firstMinistry = Ministry::first();
        if ($firstMinistry) {
            $menteri = User::updateOrCreate(
                ['email' => 'menteri@mail.com'],
                [
                    'name' => 'Menteri Kominfo',
                    'password' => Hash::make('password'),
                    'ministry_id' => $firstMinistry->id,
                ]
            );
            $menteri->assignRole('Menteri');
            
            // Bikin 5 Anggota Dummy pake Factory
            // Note: Pastikan composer require fakerphp/faker sudah dijalankan
            if (class_exists(\Database\Factories\UserFactory::class)) {
                $users = User::factory()->count(5)->create([
                    'ministry_id' => $firstMinistry->id
                ]);
                foreach($users as $user) {
                    $user->assignRole('Anggota');
                }
            }
        }
    }
}