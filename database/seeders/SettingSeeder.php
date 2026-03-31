<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Hero Section
            ['group' => 'hero', 'key' => 'title', 'type' => 'text', 'label' => 'Hero Title', 'value' => 'Welcome to Inner Collection', 'sort_order' => 1],
            ['group' => 'hero', 'key' => 'subtitle', 'type' => 'text', 'label' => 'Hero Subtitle', 'value' => 'Discover Amazing Products', 'sort_order' => 2],
            ['group' => 'hero', 'key' => 'description', 'type' => 'textarea', 'label' => 'Hero Description', 'value' => 'Shop the latest trends and discover quality products at unbeatable prices.', 'sort_order' => 3],
            ['group' => 'hero', 'key' => 'image', 'type' => 'image', 'label' => 'Hero Background Image', 'value' => '', 'sort_order' => 4],
            ['group' => 'hero', 'key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'value' => 'Shop Now', 'sort_order' => 5],
            ['group' => 'hero', 'key' => 'button_link', 'type' => 'text', 'label' => 'Button Link', 'value' => '/products', 'sort_order' => 6],
            ['group' => 'hero', 'key' => 'enabled', 'type' => 'boolean', 'label' => 'Show Hero Section', 'value' => '1', 'sort_order' => 7],

            // General Settings
            ['group' => 'general', 'key' => 'site_name', 'type' => 'text', 'label' => 'Site Name', 'value' => 'Inner Collection', 'sort_order' => 1],
            ['group' => 'general', 'key' => 'site_logo', 'type' => 'image', 'label' => 'Site Logo', 'value' => '', 'sort_order' => 2],
            ['group' => 'general', 'key' => 'site_favicon', 'type' => 'image', 'label' => 'Favicon', 'value' => '', 'sort_order' => 3],
            ['group' => 'general', 'key' => 'contact_email', 'type' => 'text', 'label' => 'Contact Email', 'value' => 'hello@innercollection.com', 'sort_order' => 4],
            ['group' => 'general', 'key' => 'contact_phone', 'type' => 'text', 'label' => 'Contact Phone', 'value' => '+880 1XXX-XXXXXX', 'sort_order' => 5],
            ['group' => 'general', 'key' => 'address', 'type' => 'textarea', 'label' => 'Address', 'value' => 'Dhaka, Bangladesh', 'sort_order' => 6],
            ['group' => 'general', 'key' => 'currency', 'type' => 'text', 'label' => 'Currency Code', 'value' => 'BDT', 'sort_order' => 7],
            ['group' => 'general', 'key' => 'currency_symbol', 'type' => 'text', 'label' => 'Currency Symbol', 'value' => '৳', 'sort_order' => 8],

            // Social Media
            ['group' => 'social', 'key' => 'facebook', 'type' => 'text', 'label' => 'Facebook URL', 'value' => '', 'sort_order' => 1],
            ['group' => 'social', 'key' => 'instagram', 'type' => 'text', 'label' => 'Instagram URL', 'value' => '', 'sort_order' => 2],
            ['group' => 'social', 'key' => 'twitter', 'type' => 'text', 'label' => 'Twitter URL', 'value' => '', 'sort_order' => 3],
            ['group' => 'social', 'key' => 'youtube', 'type' => 'text', 'label' => 'YouTube URL', 'value' => '', 'sort_order' => 4],
            ['group' => 'social', 'key' => 'linkedin', 'type' => 'text', 'label' => 'LinkedIn URL', 'value' => '', 'sort_order' => 5],
            ['group' => 'social', 'key' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Number', 'value' => '', 'sort_order' => 6],

            // SEO
            ['group' => 'seo', 'key' => 'meta_title', 'type' => 'text', 'label' => 'Meta Title', 'value' => 'Inner Collection - Your One Stop Shop', 'sort_order' => 1],
            ['group' => 'seo', 'key' => 'meta_description', 'type' => 'textarea', 'label' => 'Meta Description', 'value' => 'Shop the latest products at Inner Collection. Quality products, great prices, fast delivery.', 'sort_order' => 2],
            ['group' => 'seo', 'key' => 'meta_keywords', 'type' => 'text', 'label' => 'Meta Keywords', 'value' => 'ecommerce, shop, online store, bangladesh', 'sort_order' => 3],
            ['group' => 'seo', 'key' => 'og_image', 'type' => 'image', 'label' => 'OG Image', 'value' => '', 'sort_order' => 4],

            // Footer
            ['group' => 'footer', 'key' => 'copyright_text', 'type' => 'text', 'label' => 'Copyright Text', 'value' => '© 2026 Inner Collection. All rights reserved.', 'sort_order' => 1],
            ['group' => 'footer', 'key' => 'footer_description', 'type' => 'textarea', 'label' => 'Footer Description', 'value' => 'Your trusted online shopping destination in Bangladesh.', 'sort_order' => 2],
            ['group' => 'footer', 'key' => 'show_newsletter', 'type' => 'boolean', 'label' => 'Show Newsletter', 'value' => '1', 'sort_order' => 3],
            ['group' => 'footer', 'key' => 'newsletter_title', 'type' => 'text', 'label' => 'Newsletter Title', 'value' => 'Subscribe to our newsletter', 'sort_order' => 4],
            ['group' => 'footer', 'key' => 'newsletter_subtitle', 'type' => 'text', 'label' => 'Newsletter Subtitle', 'value' => 'Get the latest updates on new products and upcoming sales', 'sort_order' => 5],

            // Banner/Promo
            ['group' => 'banner', 'key' => 'promo_enabled', 'type' => 'boolean', 'label' => 'Show Promo Banner', 'value' => '1', 'sort_order' => 1],
            ['group' => 'banner', 'key' => 'promo_text', 'type' => 'text', 'label' => 'Promo Text', 'value' => '🎉 Free shipping on orders over ৳2000!', 'sort_order' => 2],
            ['group' => 'banner', 'key' => 'promo_link', 'type' => 'text', 'label' => 'Promo Link', 'value' => '/products', 'sort_order' => 3],
            ['group' => 'banner', 'key' => 'promo_bg_color', 'type' => 'text', 'label' => 'Promo Background Color', 'value' => '#1a1a2e', 'sort_order' => 4],
            ['group' => 'banner', 'key' => 'promo_text_color', 'type' => 'text', 'label' => 'Promo Text Color', 'value' => '#ffffff', 'sort_order' => 5],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                array_merge($setting, ['is_public' => true])
            );
        }
    }
}
