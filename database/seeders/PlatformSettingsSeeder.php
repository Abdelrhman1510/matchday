<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Commission Settings
            ['key' => 'commission_rate', 'value' => '12', 'type' => 'float', 'group' => 'commission'],
            ['key' => 'dynamic_pricing', 'value' => '0', 'type' => 'bool', 'group' => 'commission'],
            
            // Currency Settings
            ['key' => 'default_currency', 'value' => 'SAR', 'type' => 'string', 'group' => 'currency'],
            ['key' => 'multi_currency', 'value' => '0', 'type' => 'bool', 'group' => 'currency'],
            ['key' => 'auto_conversion', 'value' => '0', 'type' => 'bool', 'group' => 'currency'],
            
            // Language & Region Settings
            ['key' => 'platform_language', 'value' => 'en', 'type' => 'string', 'group' => 'language'],
            ['key' => 'timezone', 'value' => 'Asia/Riyadh', 'type' => 'string', 'group' => 'language'],
            ['key' => 'multi_language', 'value' => '0', 'type' => 'bool', 'group' => 'language'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Platform settings seeded successfully!');
    }
}
