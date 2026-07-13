<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_candidate_can_optimize_profile_with_complete_data()
    {
        $category = Category::create(['name' => 'Programming', 'slug' => 'programming']);

        JobListing::create([
            'employer_id' => User::factory()->create(['role' => 'employer'])->id,
            'category_id' => $category->id,
            'title' => 'Laravel Developer',
            'description' => 'Build APIs',
            'responsibilities' => 'Write code',
            'skills_required' => 'PHP, Laravel, MySQL',
            'location' => 'Remote',
            'work_type' => 'remote',
            'experience_level' => 'mid',
            'salary_min' => 3000,
            'salary_max' => 5000,
            'status' => 'approved',
            'deadline' => now()->addDays(5)->toDateString(),
        ]);

        $candidate = User::create([
            'name' => 'Jane Candidate',
            'email' => 'jane@test.com',
            'password' => bcrypt('password'),
            'role' => 'candidate',
        ]);
        $candidate->candidateProfile()->create([
            'bio' => 'I am a passionate developer with 3 years of experience in PHP and Laravel.',
            'linkedin_url' => 'https://linkedin.com/in/jane',
            'phone' => '01012345678',
            'skills' => ['PHP', 'Laravel', 'MySQL'],
            'resume_path' => 'resumes/jane.pdf',
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson('/api/ai/optimize-profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'completeness_score',
                    'strengths',
                    'gaps',
                    'skills_analysis' => [
                        'in_demand_you_have',
                        'in_demand_you_need',
                        'your_unique_skills',
                    ],
                    'bio_feedback',
                    'suggestions',
                ],
            ])
            ->assertJsonPath('success', true);

        $this->assertIsInt($response->json('data.completeness_score'));
        $this->assertGreaterThanOrEqual(0, $response->json('data.completeness_score'));
        $this->assertLessThanOrEqual(100, $response->json('data.completeness_score'));
    }

    public function test_candidate_without_profile_gets_404()
    {
        $candidate = User::create([
            'name' => 'No Profile User',
            'email' => 'noprofile@test.com',
            'password' => bcrypt('password'),
            'role' => 'candidate',
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson('/api/ai/optimize-profile');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Candidate profile not found. Please complete your profile first.');
    }

    public function test_non_candidate_cannot_optimize_profile()
    {
        $employer = User::create([
            'name' => 'Employer User',
            'email' => 'employer@test.com',
            'password' => bcrypt('password'),
            'role' => 'employer',
        ]);

        Sanctum::actingAs($employer);

        $response = $this->postJson('/api/ai/optimize-profile');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_optimize_profile()
    {
        $response = $this->postJson('/api/ai/optimize-profile');

        $response->assertStatus(401);
    }

    public function test_optimize_profile_returns_empty_state_when_no_jobs()
    {
        $candidate = User::create([
            'name' => 'Empty Market User',
            'email' => 'empty@test.com',
            'password' => bcrypt('password'),
            'role' => 'candidate',
        ]);
        $candidate->candidateProfile()->create([
            'bio' => 'A skilled developer.',
            'linkedin_url' => 'https://linkedin.com/in/empty',
            'phone' => '01012345678',
            'skills' => ['PHP'],
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson('/api/ai/optimize-profile');

        $response->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No active job listings available for market analysis yet. Please try again later.');
    }
}
