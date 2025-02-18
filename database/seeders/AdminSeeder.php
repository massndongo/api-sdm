<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = Role::where('name', 'super_admin')->first();

        User::create([
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'phone' => '777777777',
            'password' => bcrypt('stade'),
            'is_active' => true,
            'role_id' => $superadmin->id,
        ]);
    }
}
