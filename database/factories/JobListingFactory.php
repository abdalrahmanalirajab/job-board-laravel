<?php

namespace Database\Factories;

use App\Models\JobListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobListingFactory extends Factory
{
    protected $model = JobListing::class;

    private static $jobTemplates = [
        ['title' => 'Senior Laravel Developer', 'category' => 'Programming'],
        ['title' => 'Full Stack PHP Developer', 'category' => 'Programming'],
        ['title' => 'Backend Engineer (Node.js)', 'category' => 'Programming'],
        ['title' => 'Junior PHP Developer', 'category' => 'Programming'],
        ['title' => 'Python Backend Developer', 'category' => 'Programming'],
        ['title' => 'Golang Microservices Developer', 'category' => 'Programming'],
        ['title' => 'Senior Java Developer', 'category' => 'Programming'],
        ['title' => 'React Frontend Engineer', 'category' => 'Frontend'],
        ['title' => 'Vue.js Frontend Developer', 'category' => 'Frontend'],
        ['title' => 'Angular UI Developer', 'category' => 'Frontend'],
        ['title' => 'Frontend Lead (React/TypeScript)', 'category' => 'Frontend'],
        ['title' => 'Next.js Developer', 'category' => 'Frontend'],
        ['title' => 'Flutter Mobile Developer', 'category' => 'Mobile'],
        ['title' => 'React Native Developer', 'category' => 'Mobile'],
        ['title' => 'iOS Swift Developer', 'category' => 'Mobile'],
        ['title' => 'Android Kotlin Developer', 'category' => 'Mobile'],
        ['title' => 'Machine Learning Engineer', 'category' => 'AI / ML'],
        ['title' => 'AI Research Scientist', 'category' => 'AI / ML'],
        ['title' => 'NLP Engineer', 'category' => 'AI / ML'],
        ['title' => 'Computer Vision Engineer', 'category' => 'AI / ML'],
        ['title' => 'Data Scientist', 'category' => 'Data Science'],
        ['title' => 'Data Analyst', 'category' => 'Data Science'],
        ['title' => 'Data Engineer', 'category' => 'Data Science'],
        ['title' => 'BI Analyst', 'category' => 'Data Science'],
        ['title' => 'DevOps Engineer', 'category' => 'DevOps'],
        ['title' => 'Site Reliability Engineer', 'category' => 'DevOps'],
        ['title' => 'Platform Engineer', 'category' => 'DevOps'],
        ['title' => 'Cloud Architect (AWS)', 'category' => 'Cloud'],
        ['title' => 'Cloud Solutions Engineer', 'category' => 'Cloud'],
        ['title' => 'Cloud Security Engineer', 'category' => 'Cloud'],
        ['title' => 'Cybersecurity Analyst', 'category' => 'Cybersecurity'],
        ['title' => 'Security Engineer', 'category' => 'Cybersecurity'],
        ['title' => 'Penetration Tester', 'category' => 'Cybersecurity'],
        ['title' => 'QA Automation Engineer', 'category' => 'QA'],
        ['title' => 'Manual QA Tester', 'category' => 'QA'],
        ['title' => 'Performance Test Engineer', 'category' => 'QA'],
        ['title' => 'Embedded Systems Engineer', 'category' => 'Embedded Systems'],
        ['title' => 'Firmware Engineer', 'category' => 'Embedded Systems'],
        ['title' => 'IoT Engineer', 'category' => 'Embedded Systems'],
        ['title' => 'UI/UX Designer', 'category' => 'UI / UX'],
        ['title' => 'Product Designer', 'category' => 'UI / UX'],
        ['title' => 'UX Researcher', 'category' => 'UI / UX'],
        ['title' => 'Product Manager', 'category' => 'Product Management'],
        ['title' => 'Technical Product Manager', 'category' => 'Product Management'],
        ['title' => 'Associate Product Manager', 'category' => 'Product Management'],
        ['title' => 'Digital Marketing Manager', 'category' => 'Marketing'],
        ['title' => 'SEO Specialist', 'category' => 'Marketing'],
        ['title' => 'Content Marketing Lead', 'category' => 'Marketing'],
        ['title' => 'Growth Marketing Manager', 'category' => 'Marketing'],
        ['title' => 'Sales Account Executive', 'category' => 'Sales'],
        ['title' => 'B2B Sales Representative', 'category' => 'Sales'],
        ['title' => 'Enterprise Sales Director', 'category' => 'Sales'],
        ['title' => 'HR Business Partner', 'category' => 'HR'],
        ['title' => 'Talent Acquisition Specialist', 'category' => 'HR'],
        ['title' => 'HR Operations Manager', 'category' => 'HR'],
        ['title' => 'Financial Analyst', 'category' => 'Finance'],
        ['title' => 'Senior Accountant', 'category' => 'Finance'],
        ['title' => 'Finance Manager', 'category' => 'Finance'],
    ];

    private static $locations = [
        'remote' => ['Remote (Worldwide)', 'Remote (EMEA)', 'Remote (Americas)', 'Remote (APAC)'],
        'hybrid' => ['Cairo, Egypt (Hybrid)', 'Dubai, UAE (Hybrid)', 'London, UK (Hybrid)', 'Berlin, Germany (Hybrid)', 'Amsterdam, Netherlands (Hybrid)'],
        'onsite' => ['Cairo, Egypt', 'Alexandria, Egypt', 'Dubai, UAE', 'Riyadh, Saudi Arabia', 'Manama, Bahrain', 'Doha, Qatar', 'Amman, Jordan'],
    ];

    private static $experienceLevels = ['junior', 'mid', 'senior', 'any'];
    private static $workTypes = ['remote', 'hybrid', 'onsite'];
    private static $benefitsPool = [
        'Health insurance', 'Flexible working hours', 'Remote work options', 'Annual bonus',
        'Stock options', 'Learning & development budget', 'Paid time off', 'Parental leave',
        'Gym membership', 'Company retreats', 'Free lunch', 'Transportation allowance',
        'Conference attendance budget', 'Mental health support', 'Equipment budget',
    ];
    private static $responsibilitiesPool = [
        'Design, develop, and maintain high-quality software solutions.',
        'Collaborate with cross-functional teams to define and implement new features.',
        'Write clean, maintainable, and well-documented code.',
        'Participate in code reviews and mentor junior developers.',
        'Troubleshoot, debug, and resolve production issues.',
        'Contribute to architectural decisions and technical roadmap.',
        'Write and maintain unit and integration tests.',
        'Document technical designs and system architecture.',
        'Optimize application performance and scalability.',
        'Stay up-to-date with emerging technologies and industry trends.',
    ];

    public function definition(): array
    {
        $template = static::$jobTemplates[array_rand(static::$jobTemplates)];
        $title = $template['title'];
        $workType = static::$workTypes[array_rand(static::$workTypes)];
        $level = static::$experienceLevels[array_rand(static::$experienceLevels)];
        $salary = $this->generateSalary($title, $level);

        $locPool = static::$locations[$workType];
        $location = $locPool[array_rand($locPool)];

        $numResponsibilities = rand(4, 7);
        $respKeys = array_rand(static::$responsibilitiesPool, $numResponsibilities);
        $respKeys = is_array($respKeys) ? $respKeys : [$respKeys];
        $responsibilities = '';
        foreach ($respKeys as $k) {
            $responsibilities .= '- ' . static::$responsibilitiesPool[$k] . "\n";
        }

        $numBenefits = rand(3, 6);
        $benefitKeys = array_rand(static::$benefitsPool, $numBenefits);
        $benefitKeys = is_array($benefitKeys) ? $benefitKeys : [$benefitKeys];
        $benefits = '';
        foreach ($benefitKeys as $k) {
            $benefits .= '- ' . static::$benefitsPool[$k] . "\n";
        }

        $description = "We are looking for a talented {$title} to join our team. "
            . fake()->paragraphs(3, true) . "\n\n"
            . "At our company, you'll work on cutting-edge projects that impact millions of users worldwide. "
            . "We foster a culture of innovation, collaboration, and continuous learning.";

        $skills = $this->generateSkills($template['category']);

        $daysFromNow = rand(5, 90);

        return [
            'title' => $title,
            'description' => $description,
            'responsibilities' => $responsibilities,
            'benefits' => $benefits,
            'skills_required' => implode(', ', $skills),
            'salary_min' => $salary['min'],
            'salary_max' => $salary['max'],
            'location' => $location,
            'work_type' => $workType,
            'experience_level' => $level,
            'deadline' => now()->addDays($daysFromNow)->toDateString(),
            'logo' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'approved']);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    private function generateSalary(string $title, string $level): array
    {
        $base = match (true) {
            str_contains($title, 'Senior') || str_contains($title, 'Lead') || str_contains($title, 'Director') || $level === 'senior' => [6000, 14000],
            $level === 'mid' || str_contains($title, 'Manager') => [3500, 8000],
            $level === 'junior' || str_contains($title, 'Junior') || str_contains($title, 'Associate') => [1500, 4000],
            default => [2500, 6000],
        };
        $min = rand($base[0], (int)($base[1] * 0.6));
        $max = rand((int)($base[0] * 1.4), $base[1]);
        return ['min' => $min, 'max' => max($min + 500, $max)];
    }

    private function generateSkills(string $category): array
    {
        $pools = [
            'Programming' => ['PHP', 'Laravel', 'MySQL', 'PostgreSQL', 'Redis', 'REST APIs', 'Git', 'Docker', 'CI/CD', 'Unit Testing', 'SOLID Principles', 'Design Patterns'],
            'Frontend' => ['React', 'Vue.js', 'TypeScript', 'JavaScript', 'HTML/CSS', 'Tailwind CSS', 'Next.js', 'GraphQL', 'REST APIs', 'Webpack', 'Jest'],
            'Mobile' => ['Flutter', 'Dart', 'Swift', 'Kotlin', 'React Native', 'Firebase', 'REST APIs', 'Git', 'App Store Deployment'],
            'AI / ML' => ['Python', 'TensorFlow', 'PyTorch', 'Scikit-learn', 'NLP', 'Computer Vision', 'Deep Learning', 'MLOps', 'Docker', 'SQL'],
            'Data Science' => ['Python', 'Pandas', 'NumPy', 'SQL', 'Tableau', 'Power BI', 'R', 'Spark', 'Statistical Analysis', 'A/B Testing'],
            'DevOps' => ['Docker', 'Kubernetes', 'Terraform', 'Ansible', 'CI/CD', 'Linux', 'AWS', 'Monitoring', 'Git', 'Shell Scripting'],
            'Cloud' => ['AWS', 'Azure', 'GCP', 'Terraform', 'Kubernetes', 'Docker', 'Serverless', 'CloudFormation', 'Networking', 'Security'],
            'Cybersecurity' => ['Network Security', 'Penetration Testing', 'SIEM', 'Firewalls', 'Encryption', 'Risk Assessment', 'Python', 'Linux'],
            'QA' => ['Selenium', 'Cypress', 'PHPUnit', 'Pest', 'Jest', 'Test Automation', 'CI/CD', 'API Testing', 'SQL', 'Agile'],
            'Embedded Systems' => ['C', 'C++', 'ARM', 'RTOS', 'Linux Kernel', 'I2C/SPI', 'Firmware', 'Python', 'MATLAB', 'Verilog'],
            'UI / UX' => ['Figma', 'Adobe XD', 'Sketch', 'User Research', 'Wireframing', 'Prototyping', 'Design Systems', 'Usability Testing', 'HTML/CSS'],
            'Product Management' => ['Product Strategy', 'Roadmapping', 'User Stories', 'A/B Testing', 'Analytics', 'Agile', 'Jira', 'Stakeholder Management', 'Market Research'],
            'Marketing' => ['SEO', 'SEM', 'Google Analytics', 'Content Strategy', 'Social Media', 'Email Marketing', 'CRM', 'Copywriting', 'A/B Testing'],
            'Sales' => ['Salesforce', 'CRM', 'Cold Outreach', 'Negotiation', 'Account Management', 'Lead Generation', 'B2B Sales', 'Presentation Skills'],
            'HR' => ['Recruiting', 'Onboarding', 'HRIS', 'BambooHR', 'Compliance', 'Performance Management', 'Payroll', 'Employee Relations', 'ATS'],
            'Finance' => ['Financial Modeling', 'Excel', 'QuickBooks', 'GAAP', 'Forecasting', 'Budgeting', 'SAP', 'Auditing', 'Tax Planning'],
        ];

        $pool = $pools[$category] ?? ['Communication', 'Teamwork', 'Problem Solving', 'Leadership', 'Project Management'];
        $count = min(rand(3, 7), count($pool));
        $keys = array_rand($pool, $count);
        $keys = is_array($keys) ? $keys : [$keys];
        return array_map(fn($k) => $pool[$k], $keys);
    }
}