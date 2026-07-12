<?php

namespace Database\Factories;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployerFactory extends Factory
{
    protected $model = Employer::class;

    protected static $companies = [
        ['name' => 'TechNova Solutions', 'industry' => 'Software', 'size' => '501-1000', 'founded' => 2015],
        ['name' => 'DataPulse AI', 'industry' => 'Artificial Intelligence', 'size' => '201-500', 'founded' => 2018],
        ['name' => 'CloudScape Systems', 'industry' => 'Cloud Computing', 'size' => '1001-5000', 'founded' => 2012],
        ['name' => 'CyberShield Corp', 'industry' => 'Cybersecurity', 'size' => '51-200', 'founded' => 2019],
        ['name' => 'FinEdge Technologies', 'industry' => 'FinTech', 'size' => '201-500', 'founded' => 2016],
        ['name' => 'HealthBridge Digital', 'industry' => 'HealthTech', 'size' => '501-1000', 'founded' => 2014],
        ['name' => 'EduVate Learning', 'industry' => 'EdTech', 'size' => '51-200', 'founded' => 2020],
        ['name' => 'GreenGrid Energy', 'industry' => 'Clean Energy', 'size' => '201-500', 'founded' => 2017],
        ['name' => 'PixelForge Studio', 'industry' => 'Gaming', 'size' => '51-200', 'founded' => 2021],
        ['name' => 'LogiChain Solutions', 'industry' => 'Logistics', 'size' => '1001-5000', 'founded' => 2010],
        ['name' => 'MediSync Labs', 'industry' => 'Healthcare', 'size' => '501-1000', 'founded' => 2013],
        ['name' => 'RetailNext Inc', 'industry' => 'E-Commerce', 'size' => '201-500', 'founded' => 2018],
        ['name' => 'AgriTech Global', 'industry' => 'Agriculture', 'size' => '51-200', 'founded' => 2019],
        ['name' => 'SpaceLens Inc', 'industry' => 'Aerospace', 'size' => '201-500', 'founded' => 2016],
        ['name' => 'QuantumLeap Labs', 'industry' => 'Quantum Computing', 'size' => '11-50', 'founded' => 2022],
        ['name' => 'SecureVault Systems', 'industry' => 'Cybersecurity', 'size' => '51-200', 'founded' => 2017],
        ['name' => 'AutoDrive Technologies', 'industry' => 'Automotive', 'size' => '501-1000', 'founded' => 2015],
        ['name' => 'MediaForge Digital', 'industry' => 'Media & Entertainment', 'size' => '201-500', 'founded' => 2014],
        ['name' => 'BioNexus Labs', 'industry' => 'Biotechnology', 'size' => '51-200', 'founded' => 2020],
        ['name' => 'SmartCity Solutions', 'industry' => 'IoT / Smart Cities', 'size' => '201-500', 'founded' => 2018],
    ];

    public function definition(): array
    {
        $company = static::$companies[array_rand(static::$companies)];
        return [
            'company_name' => $company['name'],
            'website' => 'https://' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $company['name'])) . '.com',
            'description' => fake()->paragraphs(3, true),
            'logo' => null,
        ];
    }
}