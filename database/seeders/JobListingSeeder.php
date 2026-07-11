<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JobListingSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve employer — use Member 1's user if exists, otherwise create a fallback
        $employer = User::where('email', 'employer@test.com')->first();

        if (!$employer) {
            $employer = User::create([
                'name'     => 'Demo Employer',
                'email'    => 'employer@test.com',
                'password' => Hash::make('password'),
                'role'     => 'employer',
            ]);
            $employer->employerProfile()->create([
                'company_name' => 'Demo Corp',
                'logo'         => null,
                'website'      => 'https://democorp.example',
                'description'  => 'A demo employer created by the seeder.',
            ]);
        }

        // Make sure categories exist
        if (Category::count() === 0) {
            $this->call(CategorySeeder::class);
        }

        $categories = Category::all()->keyBy('name');

        // 20 job templates with explicit category, work_type, and experience_level
        $jobs = [
            ['title' => 'Senior Laravel Developer',         'category' => 'Programming',       'work_type' => 'remote',  'level' => 'senior', 'salary_min' => 6000,  'salary_max' => 10000],
            ['title' => 'Junior PHP Developer',             'category' => 'Programming',       'work_type' => 'onsite',  'level' => 'junior', 'salary_min' => 2000,  'salary_max' => 4000],
            ['title' => 'React Frontend Engineer',          'category' => 'Programming',       'work_type' => 'hybrid',  'level' => 'mid',    'salary_min' => 4000,  'salary_max' => 7000],
            ['title' => 'Python Data Engineer',             'category' => 'Data Science',      'work_type' => 'remote',  'level' => 'senior', 'salary_min' => 7000,  'salary_max' => 12000],
            ['title' => 'Machine Learning Engineer',        'category' => 'Data Science',      'work_type' => 'remote',  'level' => 'senior', 'salary_min' => 8000,  'salary_max' => 15000],
            ['title' => 'DevOps Engineer',                  'category' => 'DevOps',            'work_type' => 'remote',  'level' => 'mid',    'salary_min' => 5000,  'salary_max' => 9000],
            ['title' => 'Cloud Infrastructure Engineer',    'category' => 'DevOps',            'work_type' => 'hybrid',  'level' => 'senior', 'salary_min' => 7000,  'salary_max' => 11000],
            ['title' => 'Product Manager',                  'category' => 'Management',        'work_type' => 'hybrid',  'level' => 'mid',    'salary_min' => 5000,  'salary_max' => 9000],
            ['title' => 'Engineering Manager',              'category' => 'Management',        'work_type' => 'onsite',  'level' => 'senior', 'salary_min' => 9000,  'salary_max' => 14000],
            ['title' => 'UX/UI Designer',                   'category' => 'Design',            'work_type' => 'remote',  'level' => 'mid',    'salary_min' => 3500,  'salary_max' => 6000],
            ['title' => 'Graphic Designer',                 'category' => 'Design',            'work_type' => 'onsite',  'level' => 'junior', 'salary_min' => 2000,  'salary_max' => 3500],
            ['title' => 'Digital Marketing Specialist',     'category' => 'Marketing',         'work_type' => 'hybrid',  'level' => 'mid',    'salary_min' => 3000,  'salary_max' => 5500],
            ['title' => 'SEO Manager',                      'category' => 'Marketing',         'work_type' => 'remote',  'level' => 'senior', 'salary_min' => 4500,  'salary_max' => 7000],
            ['title' => 'Sales Representative',             'category' => 'Sales',             'work_type' => 'onsite',  'level' => 'any',    'salary_min' => 2500,  'salary_max' => 5000],
            ['title' => 'Account Executive',                'category' => 'Sales',             'work_type' => 'hybrid',  'level' => 'mid',    'salary_min' => 4000,  'salary_max' => 7000],
            ['title' => 'Customer Support Specialist',      'category' => 'Customer Support',  'work_type' => 'remote',  'level' => 'junior', 'salary_min' => 2000,  'salary_max' => 3500],
            ['title' => 'Financial Analyst',                'category' => 'Finance',           'work_type' => 'onsite',  'level' => 'mid',    'salary_min' => 4500,  'salary_max' => 7500],
            ['title' => 'Senior Accountant',                'category' => 'Finance',           'work_type' => 'onsite',  'level' => 'senior', 'salary_min' => 6000,  'salary_max' => 9500],
            ['title' => 'HR Business Partner',              'category' => 'Human Resources',   'work_type' => 'hybrid',  'level' => 'mid',    'salary_min' => 4000,  'salary_max' => 6500],
            ['title' => 'Talent Acquisition Specialist',   'category' => 'Human Resources',   'work_type' => 'remote',  'level' => 'junior', 'salary_min' => 2500,  'salary_max' => 4500],
        ];

        // Technologies pool per category
        $techMap = [
            'Programming'      => ['Laravel', 'PHP', 'MySQL', 'Vue.js', 'React', 'TypeScript', 'REST APIs', 'Redis'],
            'Data Science'     => ['Python', 'TensorFlow', 'PyTorch', 'Pandas', 'SQL', 'Spark', 'Jupyter'],
            'DevOps'           => ['Docker', 'Kubernetes', 'AWS', 'Terraform', 'CI/CD', 'Linux', 'GitHub Actions'],
            'Management'       => ['Jira', 'Agile', 'Scrum', 'OKRs', 'Confluence', 'Roadmapping'],
            'Design'           => ['Figma', 'Adobe XD', 'Sketch', 'Photoshop', 'Illustrator', 'Prototyping'],
            'Marketing'        => ['Google Analytics', 'SEO', 'HubSpot', 'Mailchimp', 'Meta Ads', 'Copywriting'],
            'Sales'            => ['Salesforce', 'CRM', 'Cold Outreach', 'Negotiation', 'LinkedIn Sales Navigator'],
            'Customer Support' => ['Zendesk', 'Freshdesk', 'Intercom', 'Ticketing Systems', 'Communication'],
            'Finance'          => ['Excel', 'Financial Modeling', 'QuickBooks', 'SAP', 'GAAP', 'Forecasting'],
            'Human Resources'  => ['HRIS', 'BambooHR', 'Recruiting', 'Onboarding', 'Compliance', 'ATS'],
        ];

        // Assign statuses: 15 approved, 3 pending, 2 rejected — in fixed positions so the mix is deterministic
        $statuses = array_merge(
            array_fill(0, 15, 'approved'),
            array_fill(0, 3, 'pending'),
            array_fill(0, 2, 'rejected')
        );

        foreach ($jobs as $index => $jobData) {
            $categoryName = $jobData['category'];
            $category     = $categories->get($categoryName);

            if (!$category) {
                continue;
            }

            $status = $statuses[$index];

            $job = JobListing::create([
                'employer_id'      => $employer->id,
                'category_id'      => $category->id,
                'title'            => $jobData['title'],
                'description'      => "We are looking for a talented {$jobData['title']} to join our team. "
                    . "You will work on exciting projects and collaborate with a cross-functional team to deliver impactful results.",
                'responsibilities' => "- Lead the design and implementation of key features.\n"
                    . "- Collaborate with stakeholders to gather and refine requirements.\n"
                    . "- Conduct code reviews and mentor junior team members.\n"
                    . "- Maintain documentation and uphold engineering standards.",
                'skills_required'  => "Problem-solving, Communication, Teamwork, Agile/Scrum",
                'salary_min'       => $jobData['salary_min'],
                'salary_max'       => $jobData['salary_max'],
                'location'         => match ($jobData['work_type']) {
                    'remote'  => 'Remote (Worldwide)',
                    'hybrid'  => 'Cairo, Egypt (Hybrid)',
                    default   => 'Cairo, Egypt (On-site)',
                },
                'work_type'        => $jobData['work_type'],
                'experience_level' => $jobData['level'],
                'status'           => $status,
                'deadline'         => now()->addDays(rand(14, 90))->toDateString(),
                'logo'             => null,
            ]);

            // Assign 2–4 technologies from the category's pool
            $pool       = $techMap[$categoryName] ?? ['Git', 'Communication', 'Teamwork'];
            $techCount  = min(rand(2, 4), count($pool));
            $keys       = array_rand($pool, $techCount);
            $techs      = is_array($keys) ? array_map(fn($k) => $pool[$k], $keys) : [$pool[$keys]];

            foreach ($techs as $techName) {
                $job->technologies()->create(['name' => $techName]);
            }
        }
    }
}
