<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::create(['name' => 'super_admin']);
        \App\Models\Role::create(['name' => 'admin']);
        \App\Models\Role::create(['name' => 'staff']);
        \App\Models\Role::create(['name' => 'joueur']);
        \App\Models\Role::create(['name' => 'portier']);
        \App\Models\Role::create(['name' => 'supporter']);
        \App\Models\Role::create(['name' => 'gestionnaire_ligue']);
        \App\Models\Role::create(['name' => 'gestionnaire_district']);
        \App\Models\Role::create(['name' => 'gestionnaire_club']);
    }
}
