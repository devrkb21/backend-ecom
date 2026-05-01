<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use Illuminate\Support\Str;

class PagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            [
                'title' => 'About Us',
                'content' => '<h1>About Us</h1><p>Welcome to our store. We are dedicated to providing the best products and services.</p>'
            ],
            [
                'title' => 'Contact',
                'content' => '<h1>Contact Us</h1><p>If you have any questions, please feel free to reach out to our customer support.</p>'
            ],
            [
                'title' => 'Refund Policy',
                'content' => '<h1>Refund Policy</h1><p>We offer a 30-day money-back guarantee for all our products.</p>'
            ],
            [
                'title' => 'Privacy Policy',
                'content' => '<h1>Privacy Policy</h1><p>Your privacy is important to us. We do not share your personal information with third parties.</p>'
            ],
            [
                'title' => 'Terms of Service',
                'content' => '<h1>Terms of Service</h1><p>By using our website, you agree to these terms and conditions.</p>'
            ]
        ];

        foreach ($pages as $page) {
            $slug = Str::slug($page['title']);
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'meta_title' => $page['title'],
                    'meta_description' => 'Read our ' . $page['title'] . ' to learn more.',
                ]
            );
        }
    }
}
