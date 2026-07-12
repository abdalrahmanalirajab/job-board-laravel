<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    private static $skillPools = [
        ['PHP', 'Laravel', 'Vue.js', 'MySQL', 'Git', 'REST APIs', 'Redis', 'Docker'],
        ['React', 'TypeScript', 'Next.js', 'Node.js', 'GraphQL', 'Tailwind CSS'],
        ['Python', 'Django', 'FastAPI', 'PostgreSQL', 'AWS', 'Celery'],
        ['Java', 'Spring Boot', 'Kubernetes', 'Kafka', 'Microservices', 'MongoDB'],
        ['Data Science', 'Python', 'TensorFlow', 'PyTorch', 'Pandas', 'SQL', 'Spark'],
        ['DevOps', 'Docker', 'Kubernetes', 'Terraform', 'AWS', 'CI/CD', 'Linux'],
        ['UI/UX', 'Figma', 'Adobe XD', 'User Research', 'Prototyping', 'Design Systems'],
        ['Product Management', 'Agile', 'Jira', 'Roadmapping', 'A/B Testing', 'Analytics'],
        ['Mobile', 'Flutter', 'Dart', 'Firebase', 'iOS', 'Android'],
        ['QA', 'Selenium', 'Cypress', 'PHPUnit', 'Pest', 'Automation Testing'],
    ];

    private static $locations = [
        'Cairo, Egypt',
        'Alexandria, Egypt',
        'Giza, Egypt',
        'Dubai, UAE',
        'Riyadh, Saudi Arabia',
        'Amman, Jordan',
        'Beirut, Lebanon',
        'Casablanca, Morocco',
        'Tunis, Tunisia',
        'Manama, Bahrain',
    ];

    public function definition(): array
    {
        $skills = static::$skillPools[array_rand(static::$skillPools)];
        $selectedKeys = array_rand($skills, min(rand(3, count($skills)), count($skills)));
        $selectedSkills = is_array($selectedKeys)
            ? array_map(fn($k) => $skills[$k], $selectedKeys)
            : [$skills[$selectedKeys]];

        return [
            'bio' => fake()->paragraphs(rand(1, 3), true),
            'phone' => fake()->phoneNumber(),
            'skills' => $selectedSkills,
            'resume_path' => null,
            'linkedin_url' => 'https://linkedin.com/in/' . fake()->userName(),
        ];
    }
}