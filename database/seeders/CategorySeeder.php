<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Programming',
            'Frontend',
            'Mobile',
            'AI / ML',
            'Data Science',
            'DevOps',
            'Cloud',
            'Cybersecurity',
            'QA',
            'Embedded Systems',
            'UI / UX',
            'Product Management',
            'Marketing',
            'Sales',
            'HR',
            'Finance',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}