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
        // 1. Create Employer User and profile
        $employer = User::updateOrCreate(
            ['email' => 'employer@test.com'],
            [
                'name' => 'Demo Employer',
                'password' => Hash::make('password'),
                'role' => 'employer',
            ]
        );

        $employer->employerProfile()->updateOrCreate(
            ['user_id' => $employer->id],
            [
                'company_name' => 'Demo Corp',
                'website' => 'https://democorp.example',
                'description' => 'A demo employer company.',
            ]
        );

        // 2. Create Candidate User and profile
        $candidate = User::updateOrCreate(
            ['email' => 'candidate@test.com'],
            [
                'name' => 'Demo Candidate',
                'password' => Hash::make('password'),
                'role' => 'candidate',
            ]
        );

        $candidate->candidateProfile()->updateOrCreate(
            ['user_id' => $candidate->id],
            [
                'bio' => 'A demo candidate looking for exciting opportunities.',
                'phone' => '+20 100 000 0000',
                'skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL', 'Git'],
            ]
        );
    }
}
