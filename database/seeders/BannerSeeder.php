<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title'       => 'Champions League Final',
                'title_ar'    => 'نهائي دوري أبطال أوروبا',
                'subtitle'    => 'Book your seat now — limited spots available',
                'subtitle_ar' => 'احجز مقعدك الآن — الأماكن محدودة',
                'image_url'   => 'https://picsum.photos/seed/banner-cl/800/400',
                'action_type' => 'match',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'title'       => 'Weekend Special Offer',
                'title_ar'    => 'عرض نهاية الأسبوع الخاص',
                'subtitle'    => '20% off all bookings this weekend',
                'subtitle_ar' => 'خصم ٢٠٪ على جميع الحجوزات هذا الأسبوع',
                'image_url'   => 'https://picsum.photos/seed/banner-offer/800/400',
                'action_type' => 'offer',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'title'       => 'New Cafe: Sky Sports Lounge',
                'title_ar'    => 'مقهى جديد: سكاي سبورتس لاونج',
                'subtitle'    => 'Rooftop views + premium seating in Jeddah',
                'subtitle_ar' => 'إطلالات من السطح + مقاعد مميزة في جدة',
                'image_url'   => 'https://picsum.photos/seed/banner-cafe/800/400',
                'action_type' => 'cafe',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 3,
            ],
            [
                'title'       => 'El Clásico Night',
                'title_ar'    => 'ليلة الكلاسيكو',
                'subtitle'    => 'Watch Barcelona vs Real Madrid live',
                'subtitle_ar' => 'شاهد برشلونة ضد ريال مدريد مباشرة',
                'image_url'   => 'https://picsum.photos/seed/banner-clasico/800/400',
                'action_type' => 'match',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 4,
            ],
        ];

        // Idempotent: match by title so re-running fills Arabic without dupes.
        foreach ($banners as $banner) {
            Banner::updateOrCreate(['title' => $banner['title']], $banner);
        }

        $this->command->info('Banners seeded successfully!');
    }
}
