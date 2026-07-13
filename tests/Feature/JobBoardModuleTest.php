<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobBoardModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();
    }

    public function test_public_can_list_and_view_categories()
    {
        $category = Category::create([
            'name' => 'Software Engineering',
            'slug' => 'software-engineering'
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'jobs_count']
                ]
            ]);

        $response = $this->getJson("/api/categories/{$category->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Software Engineering');
    }

    public function test_public_can_list_only_approved_jobs_with_filters()
    {
        $employer = User::create([
            'name' => 'Employer User',
            'email' => 'employer@test.com',
            'password' => bcrypt('password'),
            'role' => 'employer'
        ]);

        $category = Category::create([
            'name' => 'IT',
            'slug' => 'it'
        ]);

        // Approved job
        $approvedJob = JobListing::create([
            'employer_id' => $employer->id,
            'category_id' => $category->id,
            'title' => 'Senior Laravel Developer',
            'description' => 'Build nice APIs',
            'responsibilities' => 'Write clean code',
            'skills_required' => 'Laravel, PHP',
            'location' => 'Remote',
            'work_type' => 'remote',
            'experience_level' => 'senior',
            'salary_min' => 5000,
            'salary_max' => 8000,
            'status' => 'approved',
            'deadline' => now()->addDays(5)->toDateString()
        ]);

        // Pending job (should not show up)
        $pendingJob = JobListing::create([
            'employer_id' => $employer->id,
            'category_id' => $category->id,
            'title' => 'Junior Developer',
            'description' => 'Help senior devs',
            'responsibilities' => 'Write unit tests',
            'skills_required' => 'PHP',
            'location' => 'Cairo',
            'work_type' => 'onsite',
            'experience_level' => 'junior',
            'status' => 'pending',
            'deadline' => now()->addDays(5)->toDateString()
        ]);

        // List public jobs
        $response = $this->getJson('/api/jobs');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approvedJob->id);

        // Show job listing details
        $response = $this->getJson("/api/jobs/{$approvedJob->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Senior Laravel Developer');

        // Show pending job returns 404
        $response = $this->getJson("/api/jobs/{$pendingJob->id}");
        $response->assertStatus(404);

        // Test Filters
        $response = $this->getJson('/api/jobs?search=Laravel');
        $response->assertStatus(200)->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/jobs?search=Junior');
        $response->assertStatus(200)->assertJsonCount(0, 'data'); // Since Junior is pending
    }

    public function test_employer_can_manage_their_own_jobs()
    {
        $employer = User::create([
            'name' => 'My Employer',
            'email' => 'employer@me.com',
            'password' => bcrypt('password'),
            'role' => 'employer'
        ]);
        
        $employer->employerProfile()->create([
            'company_name' => 'Tech Corp'
        ]);

        $otherEmployer = User::create([
            'name' => 'Other Employer',
            'email' => 'other@employer.com',
            'password' => bcrypt('password'),
            'role' => 'employer'
        ]);

        $category = Category::create([
            'name' => 'Design',
            'slug' => 'design'
        ]);

        Sanctum::actingAs($employer);

        // Store Job
        $logo = UploadedFile::fake()->createWithContent(
            'company_logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')
        );
        $response = $this->postJson('/api/employer/jobs', [
            'category_id' => $category->id,
            'title' => 'UI/UX Designer',
            'description' => 'Create beautiful designs',
            'responsibilities' => 'Draw user flows',
            'skills_required' => 'Figma',
            'location' => 'Hybrid',
            'work_type' => 'hybrid',
            'experience_level' => 'mid',
            'salary_min' => 3000,
            'salary_max' => 5000,
            'deadline' => now()->addDays(10)->toDateString(),
            'logo' => $logo,
            'technologies' => ['Figma', 'Adobe XD']
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'UI/UX Designer')
            ->assertJsonPath('data.status', 'pending');

        $jobId = $response->json('data.id');
        $logoName = basename($response->json('data.logo'));
        Storage::disk('public')->assertExists('job-logos/' . $logoName);

        // List Employer's Jobs
        $response = $this->getJson('/api/employer/jobs');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Show Employer's Job
        $response = $this->getJson("/api/employer/jobs/{$jobId}");
        $response->assertStatus(200);

        // Update Job
        $response = $this->putJson("/api/employer/jobs/{$jobId}", [
            'title' => 'Senior UI/UX Designer',
            'technologies' => ['Figma', 'Sketch']
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Senior UI/UX Designer');

        // Other Employer cannot show/update/delete
        Sanctum::actingAs($otherEmployer);
        
        $this->getJson("/api/employer/jobs/{$jobId}")->assertStatus(403);
        $this->putJson("/api/employer/jobs/{$jobId}", ['title' => 'Hacked'])->assertStatus(403);
        $this->deleteJson("/api/employer/jobs/{$jobId}")->assertStatus(403);

        // Delete Job
        Sanctum::actingAs($employer);
        $this->deleteJson("/api/employer/jobs/{$jobId}")->assertStatus(200);
        
        Storage::disk('public')->assertMissing('job-logos/' . $logoName);
    }

    public function test_admin_can_list_approve_and_reject_jobs()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $employer = User::create([
            'name' => 'Employer User',
            'email' => 'employer@test.com',
            'password' => bcrypt('password'),
            'role' => 'employer'
        ]);

        $category = Category::create([
            'name' => 'Marketing',
            'slug' => 'marketing'
        ]);

        $job = JobListing::create([
            'employer_id' => $employer->id,
            'category_id' => $category->id,
            'title' => 'SEO Specialist',
            'description' => 'Optimize site ranking',
            'responsibilities' => 'Keyword research',
            'skills_required' => 'Ahrefs',
            'location' => 'Onsite',
            'work_type' => 'onsite',
            'experience_level' => 'any',
            'status' => 'pending'
        ]);

        Sanctum::actingAs($admin);

        // List Admin Jobs
        $response = $this->getJson('/api/admin/jobs?status=pending');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Approve Job
        $response = $this->putJson("/api/admin/jobs/{$job->id}/approve");
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // Reject Job
        $response = $this->putJson("/api/admin/jobs/{$job->id}/reject", [
            'reason' => 'Duplicate listing'
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
            
        $this->assertEquals('rejected', $job->fresh()->status);
    }
}
