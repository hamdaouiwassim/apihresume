<?php

namespace Database\Seeders;

use App\Models\CoverLetterTemplate;
use Illuminate\Database\Seeder;

class CoverLetterTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // English Templates
            [
                'name' => 'Software Engineer (EN)',
                'job_type' => 'software_engineer',
                'language' => 'en',
                'subject' => 'Application for [Position Name] - [Your Name]',
                'content' => "Dear [Hiring Manager Name],\n\nI am writing to express my strong interest in the [Position Name] role at [Company Name]. With a solid background in full-stack development and a passion for building scalable applications, I am confident that my skills in [Tech Stack] make me an ideal candidate for your team.\n\nAt my previous role at [Previous Company], I led the development of a [Project Name] that resulted in a [Percentage]% increase in system performance. I enjoy tackling complex architectural challenges and am a firm believer in clean, maintainable code.\n\nI have followed [Company Name]’s recent work on [Specific Project] and am impressed by your commitment to innovation. I am eager to bring my technical expertise to help [Company Name] achieve its goals.\n\nThank you for your time and consideration. I look forward to the possibility of discussing how my background can contribute to your team.\n\nSincerely,\n[Your Name]",
                'is_active' => true,
            ],
            [
                'name' => 'Digital Marketing (EN)',
                'job_type' => 'digital_marketing',
                'language' => 'en',
                'subject' => 'Application for Digital Marketing Specialist - [Your Name]',
                'content' => "Dear [Hiring Manager Name],\n\nI am excited to submit my application for the Digital Marketing Specialist position at [Company Name]. As a data-driven marketer with experience in managing multi-channel campaigns, I have a proven track record of increasing brand visibility and driving measurable ROI.\n\nIn my most recent position at [Previous Company], I spearheaded a strategy that doubled our engagement rate within six months. I am proficient in SEO, SEM, and performance analytics, and I pride myself on my ability to translate complex data into actionable insights.\n\n[Company Name]’s reputation for [Specific Value] resonates deeply with my professional philosophy. I am eager to leverage my strategic mindset to reach new audiences and strengthen [Company Name]’s market position.\n\nI would welcome the opportunity to discuss how my expertise in digital strategy can help [Company Name] reach its ambitious growth targets.\n\nBest regards,\n[Your Name]",
                'is_active' => true,
            ],
            [
                'name' => 'Customer Success (EN)',
                'job_type' => 'customer_success',
                'language' => 'en',
                'subject' => 'Application for Customer Success Manager - [Your Name]',
                'content' => "Dear [Hiring Manager Name],\n\nI was thrilled to see the opening for a Customer Success Manager at [Company Name]. With a background in client-facing roles and a deep-seated passion for ensuring user satisfaction, I am eager to help [Company Name]’s clients achieve their fullest potential.\n\nThroughout my career at [Previous Company], I maintained a high client retention rate and successfully onboarded numerous enterprise accounts. I believe that true customer success comes from proactive communication and a thorough understanding of the client’s unique business needs.\n\nI have been a long-time admirer of [Company Name]’s approach to [Specific Feature], and I am confident that my ability to build strong, lasting relationships will be a valuable asset to your team.\n\nThank you for considering my application. I look forward to hearing from you.\n\nSincerely,\n[Your Name]",
                'is_active' => true,
            ],
            // French Templates
            [
                'name' => 'Ingénieur Logiciel (FR)',
                'job_type' => 'software_engineer',
                'language' => 'fr',
                'subject' => 'Candidature pour le poste de [Nom du Poste] - [Votre Nom]',
                'content' => "Madame, Monsieur,\n\nC'est avec un vif intérêt que je vous soumets ma candidature pour le poste de [Nom du Poste] au sein de [Nom de l'Entreprise]. Fort d'une solide expérience en développement full-stack et passionné par la création d'applications évolutives, je suis convaincu que mes compétences en [Technologies] font de moi un candidat idéal pour votre équipe.\n\nLors de ma précédente expérience chez [Entreprise Précédente], j'ai dirigé le développement de [Nom du Projet], ce qui a permis d'augmenter les performances du système de [Pourcentage]%. J'apprécie relever des défis architecturaux complexes et je suis un fervent défenseur du code propre et maintenable.\n\nJe suis avec attention les récents travaux de [Nom de l'Entreprise] sur [Projet Spécifique] et je suis impressionné par votre engagement envers l'innovation. Je suis impatient d'apporter mon expertise technique pour aider [Nom de l'Entreprise] à atteindre ses objectifs.\n\nJe vous remercie de l'attention que vous porterez à ma candidature et reste à votre entière disposition pour un entretien.\n\nJe vous prie d'agréer, Madame, Monsieur, l'expression de mes salutations distinguées.\n\n[Votre Nom]",
                'is_active' => true,
            ],
            [
                'name' => 'Marketing Digital (FR)',
                'job_type' => 'digital_marketing',
                'language' => 'fr',
                'subject' => 'Candidature pour le poste de Spécialiste Marketing Digital - [Votre Nom]',
                'content' => "Madame, Monsieur,\n\nJe suis ravi de vous présenter ma candidature pour le poste de Spécialiste Marketing Digital au sein de [Nom de l'Entreprise]. En tant que marketeur axé sur les données avec une expérience confirmée dans la gestion de campagnes multicanales, j'ai l'habitude d'accroître la visibilité des marques et de générer un ROI mesurable.\n\nDans mon dernier poste chez [Entreprise Précédente], j'ai piloté une stratégie qui a permis de doubler notre taux d'engagement en six mois. Je maîtrise le SEO, le SEM et l'analyse de performance, et je suis fier de ma capacité à traduire des données complexes en informations exploitables.\n\nLa réputation de [Nom de l'Entreprise] pour [Valeur Spécifique] résonne profondément avec ma philosophie professionnelle. Je suis impatient de mettre à profit mon esprit stratégique pour atteindre de nouveaux publics et renforcer la position de marché de [Nom de l'Entreprise].\n\nJe serais ravi d'échanger avec vous sur la manière dont mon expertise en stratégie digitale peut aider [Nom de l'Entreprise] à atteindre ses ambitieux objectifs de croissance.\n\nBien cordialement,\n\n[Votre Nom]",
                'is_active' => true,
            ],
            [
                'name' => 'Responsable Succès Client (FR)',
                'job_type' => 'customer_success',
                'language' => 'fr',
                'subject' => 'Candidature pour le poste de Responsable Succès Client - [Votre Nom]',
                'content' => "Madame, Monsieur,\n\nJe suis enchanté par l'opportunité de rejoindre [Nom de l'Entreprise] en tant que Responsable Succès Client. Riche d'une expérience dans des rôles en contact direct avec la clientèle et animé par une passion profonde pour la satisfaction des utilisateurs, j'ai à cœur d'aider les clients de [Nom de l'Entreprise] à exploiter tout leur potentiel.\n\nTout au long de ma carrière chez [Entreprise Précédente], j'ai maintenu un taux de fidélisation client élevé et j'ai géré avec succès l'intégration de nombreux comptes stratégiques. Je suis convaincu que le véritable succès client passe par une communication proactive et une compréhension approfondie des besoins business uniques de chaque client.\n\nAdmirateur de longue date de l'approche de [Nom de l'Entreprise] concernant [Fonctionnalité Spécifique], je suis certain que ma capacité à bâtir des relations solides et durables sera un atout précieux pour votre équipe.\n\nJe vous remercie de l'intérêt porté à ma candidature et espère avoir l'occasion de vous rencontrer prochainement.\n\nJe vous prie d'agréer, Madame, Monsieur, l'expression de mes salutations distinguées.\n\n[Votre Nom]",
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            CoverLetterTemplate::updateOrCreate(
                ['language' => $template['language'], 'job_type' => $template['job_type']],
                $template
            );
        }
    }
}
