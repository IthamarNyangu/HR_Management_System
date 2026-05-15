<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class OfficialJobTitleSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $titles = [
        'Accountant',
        'Admin Assistant',
        'Administrative Officer',
        'Application Developer',
        'Application Developer Intern',
        'Application Development Manager',
        'Back Office Engineer',
        'Business Development Assistant',
        'Business Development Specialist',
        'Clinical Officer',
        'Communication Assistant',
        'Communications Specialist',
        'Contracts Officer',
        'Country Director',
        'Data and GIS Manager',
        'Deputy Chief of Party',
        'District Technical Officer',
        'Driver',
        'Finance Manager',
        'Finance Manager - Treasury & Reporting',
        'Finance Officer',
        'Financial Management & Operations Director',
        'Financial Management Specialist',
        'Fleet and Logistics Manager',
        'Head-Information Technology',
        'Head of Finance',
        'Head of People and Culture',
        'Head of Procurement and Operations',
        'Head of Risk',
        'HIV/TB Hub Medical Mentor',
        'HIV/TB Provincial Medical Lead Mentor',
        'HTS Hub Coordinator',
        'Hub Strategic Information Officer',
        'Hub Supply Chain Coordinator',
        'IT Help Desk Support Officer',
        'IT Help Desk Team Lead',
        'IT Infrastracture & Operations Manager',
        'IT Security Officer',
        'IT Technician',
        'Laboratory Manager',
        'Laboratory Technologist',
        'Lay Counsellor',
        'Legal Officer',
        'Motorbike Rider',
        'Office Assistant',
        'Payroll Accountant',
        'Payroll Officer',
        'People & Culture Administrator',
        'People & Culture Manager',
        'People & Culture Officer',
        'Pharmaceutical  Supply Chain Advisor',
        'Pharmacy Technologist',
        'Procurement Intern',
        'Procurement Manager',
        'Procurement Officer',
        'Professional Counsellor',
        'Project Support Manager',
        'Provincial Administrative and Logistics Coordinator',
        'Provincial HTS Coordinator',
        'Provincial Laboratory Coordinator',
        'Provincial Manager',
        'Provincial Strategic Information Coordinator',
        'Provincial Strategic Information Officer',
        'Provincial Supply Chain Coordinator',
        'Provincial Technical Officer',
        'Receptionist',
        'Registered Nurse/Nurse Midwife',
        'Risk and Compliance Officer',
        'Risk and Compliance Officer Intern',
        'Senior Procurement Officer',
        'Senior Strategic Information Advisor',
        'Strategic Information Assistant',
        'Strategic Information Director',
        'Technical Director',
        'Technical Specialist-HIV Services & Retention',
        'Technical Specialist-Prevention Services',
        'Technical Specialist-Vulnerable Population',
    ];

    public function run(): void
    {
        foreach ($this->titles as $title) {
            $jobTitle = JobTitle::firstOrNew(['name' => $title]);

            if (! $jobTitle->code) {
                $jobTitle->code = $this->uniqueCode($title);
            }

            $jobTitle->description = $jobTitle->description ?: null;
            $jobTitle->is_active = true;
            $jobTitle->save();
        }
    }

    private function uniqueCode(string $title): string
    {
        $base = $this->codeFromTitle($title);
        $code = $base;
        $counter = 2;

        while (JobTitle::where('code', $code)->where('name', '<>', $title)->exists()) {
            $code = $base.$counter;
            $counter++;
        }

        return $code;
    }

    private function codeFromTitle(string $title): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', strtoupper($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) === 1) {
            return substr($words[0], 0, 6);
        }

        $code = implode('', array_map(fn (string $word) => $word[0], $words));

        return substr($code, 0, 12);
    }
}
