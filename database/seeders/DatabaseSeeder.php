<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory(10)->create();

        $this->call(RolesAndPermissionsSeeder::class);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'is_locked' => false,
            ]
        );
        $superAdmin->assignRole('SUPER_ADMIN');

        // Tạo Office Admin
        $officeAdmin = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Office Admin',
                'password' => bcrypt('password'),
            ]
        );
        $officeAdmin->assignRole('OFFICE_ADMIN');

        // Tạo Field Staff
        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Field Staff',
                'password' => bcrypt('password'),
            ]
        );
        $staff->assignRole('FIELD_STAFF');

        $this->call(FakeDataSeeder::class);
    }
}
