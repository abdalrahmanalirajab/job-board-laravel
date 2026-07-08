<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\JobListing;
use App\Models\User;
use App\Models\Application;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationsAndCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected $candidate;
    protected $employer;
    protected $admin;
    protected $category;
    protected $job;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Create a candidate
        $this->candidate = User::create([
            'name' => 'John Candidate',
            'email' => 'candidate@test.com',
            'password' => bcrypt('password'),
            'role' => 'candidate'
        ]);
        $this->candidate->candidateProfile()->create([
            'bio' => 'Candidate Bio',
            'skills' => ['Laravel', 'PHP'],
            'linkedin_url' => 'https://linkedin.com/in/john'
        ]);

        // Create an employer
        $this->employer = User::create([
            'name' => 'Jane Employer',
            'email' => 'employer@test.com',
            'password' => bcrypt('password'),
            'role' => 'employer'
        ]);
        $this->employer->employerProfile()->create([
            'company_name' => 'Employer Inc.',
            'description' => 'A great company'
        ]);

        // Create an admin
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // Create category
        $this->category = Category::create([
            'name' => 'Development',
            'slug' => 'development'
        ]);

        // Create approved job listing
        $this->job = JobListing::create([
            'employer_id' => $this->employer->id,
            'category_id' => $this->category->id,
            'title' => 'Software Engineer',
            'description' => 'Write code',
            'responsibilities' => 'Solve problems',
            'skills_required' => 'Laravel',
            'location' => 'Remote',
            'work_type' => 'remote',
            'status' => 'approved',
            'deadline' => now()->addDays(5)->toDateString()
        ]);
    }

    /**
     * Test Apply for Job constraints
     */
    public function test_candidate_can_apply_for_job_with_valid_details()
    {
        Sanctum::actingAs($this->candidate);

        $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/jobs/{$this->job->id}/apply", [
            'resume' => $resume,
            'contact_email' => 'candidate_contact@test.com',
            'contact_phone' => '1234567890'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'contact_email',
                    'contact_phone',
                    'resume_url',
                    'applied_at',
                    'created_at',
                    'job',
                    'candidate'
                ]
            ]);

        // Assert resume stored
        $this->assertCount(1, Storage::disk('public')->allFiles('resumes'));
    }

    public function test_apply_throws_404_if_job_not_found()
    {
        Sanctum::actingAs($this->candidate);

        $response = $this->postJson("/api/jobs/999/apply", [
            'contact_email' => 'candidate_contact@test.com'
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_apply_throws_422_if_job_not_approved()
    {
        Sanctum::actingAs($this->candidate);

        $pendingJob = JobListing::create([
            'employer_id' => $this->employer->id,
            'category_id' => $this->category->id,
            'title' => 'Pending Job',
            'description' => 'Desc',
            'responsibilities' => 'Resp',
            'skills_required' => 'Laravel',
            'location' => 'Remote',
            'work_type' => 'remote',
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/jobs/{$pendingJob->id}/apply", [
            'contact_email' => 'candidate_contact@test.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You can only apply for approved job listings');
    }

    public function test_apply_throws_422_if_deadline_passed()
    {
        Sanctum::actingAs($this->candidate);

        $expiredJob = JobListing::create([
            'employer_id' => $this->employer->id,
            'category_id' => $this->category->id,
            'title' => 'Expired Job',
            'description' => 'Desc',
            'responsibilities' => 'Resp',
            'skills_required' => 'Laravel',
            'location' => 'Remote',
            'work_type' => 'remote',
            'status' => 'approved',
            'deadline' => now()->subDays(1)->toDateString()
        ]);

        $response = $this->postJson("/api/jobs/{$expiredJob->id}/apply", [
            'contact_email' => 'candidate_contact@test.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The application deadline for this job has passed');
    }

    public function test_apply_throws_422_if_duplicate()
    {
        Sanctum::actingAs($this->candidate);

        // Apply first time
        $this->postJson("/api/jobs/{$this->job->id}/apply", [
            'contact_email' => 'candidate_contact@test.com'
        ])->assertStatus(201);

        // Apply second time
        $response = $this->postJson("/api/jobs/{$this->job->id}/apply", [
            'contact_email' => 'candidate_contact@test.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You have already applied for this job');
    }

    public function test_apply_throws_422_if_neither_resume_nor_email_provided()
    {
        Sanctum::actingAs($this->candidate);

        $response = $this->postJson("/api/jobs/{$this->job->id}/apply", [
            'contact_phone' => '1234567890'
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test Cancel Application
     */
    public function test_candidate_can_cancel_pending_application()
    {
        Sanctum::actingAs($this->candidate);

        $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');
        $app = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'resume_path' => $resume->store('resumes', 'public'),
            'status' => 'pending',
            'applied_at' => now()
        ]);

        $response = $this->deleteJson("/api/applications/{$app->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Application cancelled successfully.');

        $this->assertDatabaseMissing('applications', ['id' => $app->id]);
        $this->assertCount(0, Storage::disk('public')->allFiles('resumes'));
    }

    public function test_candidate_cannot_cancel_reviewed_application()
    {
        Sanctum::actingAs($this->candidate);

        $app = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'accepted',
            'applied_at' => now()
        ]);

        $response = $this->deleteJson("/api/applications/{$app->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You cannot cancel an application that has already been reviewed');
    }

    public function test_candidate_cannot_cancel_others_application()
    {
        $otherCandidate = User::create([
            'name' => 'Other Cand',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'candidate'
        ]);

        $app = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'pending',
            'applied_at' => now()
        ]);

        Sanctum::actingAs($otherCandidate);

        $response = $this->deleteJson("/api/applications/{$app->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * Test list my applications
     */
    public function test_candidate_can_list_own_applications_with_status_filter()
    {
        Sanctum::actingAs($this->candidate);

        Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'pending',
            'applied_at' => now()
        ]);

        $response = $this->getJson("/api/candidate/applications?status=pending");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $responseEmpty = $this->getJson("/api/candidate/applications?status=accepted");
        $responseEmpty->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test Employer Application listing & actions
     */
    public function test_employer_can_list_applications_for_their_jobs()
    {
        Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'pending',
            'applied_at' => now()
        ]);

        Sanctum::actingAs($this->employer);

        $response = $this->getJson("/api/employer/applications");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_employer_can_accept_application()
    {
        $app = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'pending',
            'applied_at' => now()
        ]);

        Sanctum::actingAs($this->employer);

        $response = $this->putJson("/api/applications/{$app->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Application accepted successfully.')
            ->assertJsonPath('data.status', 'accepted');
    }

    public function test_employer_cannot_accept_already_reviewed()
    {
        $app = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'accepted',
            'applied_at' => now()
        ]);

        Sanctum::actingAs($this->employer);

        $response = $this->putJson("/api/applications/{$app->id}/accept");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This application has already been reviewed');
    }

    /**
     * Comments Tests
     */
    public function test_comments_flow()
    {
        // Public list comments
        $response = $this->getJson("/api/jobs/{$this->job->id}/comments");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');

        // Add a comment
        Sanctum::actingAs($this->candidate);
        $response = $this->postJson("/api/jobs/{$this->job->id}/comments", [
            'body' => 'This is a test comment.'
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'This is a test comment.');

        $commentId = $response->json('data.id');

        // Check list comments now has 1 visible comment
        $response = $this->getJson("/api/jobs/{$this->job->id}/comments");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Admin hides comment
        Sanctum::actingAs($this->admin);
        $responseHide = $this->deleteJson("/api/comments/{$commentId}");
        $responseHide->assertStatus(200)
            ->assertJsonPath('message', 'Comment hidden successfully.');

        // Now visible count should be 0
        $response = $this->getJson("/api/jobs/{$this->job->id}/comments");
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Restore comment visibility manually to test owner deletion
        Comment::find($commentId)->update(['is_visible' => true]);

        // Candidate (owner) deletes comment
        Sanctum::actingAs($this->candidate);
        $responseDelete = $this->deleteJson("/api/comments/{$commentId}");
        $responseDelete->assertStatus(200)
            ->assertJsonPath('message', 'Comment deleted successfully.');

        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
    }
}
