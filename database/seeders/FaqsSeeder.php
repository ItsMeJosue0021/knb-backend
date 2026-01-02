<?php

namespace Database\Seeders;

use App\Models\Faqs;
use Illuminate\Database\Seeder;

class FaqsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Who is in charge of Kalinga ng Kababaihan Women’s League?',
                'answer' => 'The community is led by President Beavin Soriano and Vice President Juliet Eronico.',
                'category' => 'general'
            ],
            [
                'question' => 'What would I gain from becoming a member?',
                'answer' => 'Kalinga ng Kababaihan Women’s League Las Piñas is a group that helps people who need it the most...',
                'category' => 'general'
            ],
            [
                'question' => 'What is the Kalinga ng Kababaihan Women’s League community like?',
                'answer' => 'Kalinga ng Kababaihan Women’s League Las Piñas is a global community of individuals...',
                'category' => 'general'
            ],
            [
                'question' => 'How do I join Kalinga ng Kababaihan Women’s League?',
                'answer' => 'To become a member, you may volunteer or follow us on Facebook.',
                'category' => 'general'
            ],
            [
                'question' => 'What are the services you provide and how often?',
                'answer' => 'We offer food distribution, feeding programs, and a youth basketball league.',
                'category' => 'general'
            ],
            [
                'question' => 'How many of each area do you support?',
                'answer' => 'We support the entire area of Las Piñas, reaching all barangays.',
                'category' => 'general'
            ],
            [
                'question' => 'Do you collect volunteer information?',
                'answer' => 'Yes. We gather basic info such as name, contact, skills, and availability.',
                'category' => 'general'
            ],
            [
                'question' => 'What is your donation process?',
                'answer' => 'You can support us by giving cash contributions that help sustain our charity programs.',
                'category' => 'donation'
            ],
            [
                'question' => 'Do you accept donations online?',
                'answer' => 'Not yet. We are securing the required permits to accept online donations.',
                'category' => 'donation'
            ],
            [
                'question' => 'Do donors need to share personal info?',
                'answer' => 'We respect donor privacy. Anonymous donations are allowed and appreciated.',
                'category' => 'donation'
            ],
        ];

        foreach ($faqs as $faq) {
            Faqs::create($faq);
        }
    }
}

