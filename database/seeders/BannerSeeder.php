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
                'subtitle'    => 'Book your seat now — limited spots available',
                'image_url'   => 'https://picsum.photos/seed/banner-cl/800/400',
                'action_type' => 'match',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'title'       => 'Weekend Special Offer',
                'subtitle'    => '20% off all bookings this weekend',
                'image_url'   => 'https://picsum.photos/seed/banner-offer/800/400',
                'action_type' => 'offer',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'title'       => 'New Cafe: Sky Sports Lounge',
                'subtitle'    => 'Rooftop views + premium seating in Jeddah',
                'image_url'   => 'https://picsum.photos/seed/banner-cafe/800/400',
                'action_type' => 'cafe',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 3,
            ],
            [
                'title'       => 'El Clásico Night',
                'subtitle'    => 'Watch Barcelona vs Real Madrid live',
                'image_url'   => 'https://picsum.photos/seed/banner-clasico/800/400',
                'action_type' => 'match',
                'action_id'   => null,
                'is_active'   => true,
                'sort_order'  => 4,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }

        $this->command->info('Banners seeded successfully!');
    }
}
