<?php

namespace Database\Seeders;

use App\Models\JobEducationCatalog;
use App\Models\JobSkillCatalog;
use Illuminate\Database\Seeder;

class JobCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'JavaScript',
            'TypeScript',
            'React',
            'Vue.js',
            'Angular',
            'Node.js',
            'PHP',
            'Laravel',
            'Python',
            'Django',
            'Java',
            'Spring Boot',
            'C#',
            '.NET',
            'Go',
            'Ruby',
            'Rails',
            'SQL',
            'PostgreSQL',
            'MySQL',
            'MongoDB',
            'Redis',
            'Docker',
            'Kubernetes',
            'AWS',
            'Azure',
            'Google Cloud',
            'CI/CD',
            'Git',
            'REST APIs',
            'GraphQL',
            'Microservices',
            'Agile',
            'Scrum',
            'Figma',
            'UI Design',
            'UI',
            'UX Research',
            'Product Management',
            'Data Analysis',
            'Machine Learning',
            'Excel',
            'Salesforce',
            'Customer Success',
            'Technical Writing',
            'DevOps',
            'Cybersecurity',
            'Mobile Development',
            'Flutter',
            'Swift',
            'Kotlin',
        ];

        foreach ($skills as $i => $name) {
            JobSkillCatalog::updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $education = [
            "High school diploma or equivalent",
            "Associate degree",
            "Bachelor's degree",
            "Master's degree",
            "MBA",
            "PhD / Doctorate",
            "Professional certification",
            "Bootcamp certificate",
            "Vocational training",
            "Currently enrolled in degree program",
            "No formal degree required",
            "Field: Computer Science",
            "Field: Engineering",
            "Field: Business",
            "Field: Design",
            "Licensed professional (e.g. CPA, PE)",
        ];

        foreach ($education as $i => $name) {
            JobEducationCatalog::updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }
    }
}
