<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Loge', 'Mongomo', 'Annexe-loge'];

        foreach ($categories as $category) {
            TicketCategory::create(['name' => $category]);
        }
    }
}
