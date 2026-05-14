<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Gmail',           'icon' => 'mail',           'color' => '#EA4335', 'description' => 'Gmail and Google Workspace accounts'],
            ['name' => 'Server',          'icon' => 'server',         'color' => '#10B981', 'description' => 'SSH, RDP, VPS and cloud servers'],
            ['name' => 'Website',         'icon' => 'globe',          'color' => '#6366F1', 'description' => 'CMS and website admin panels'],
            ['name' => 'Database',        'icon' => 'database',       'color' => '#F59E0B', 'description' => 'MySQL, Postgres, Mongo and other database credentials'],
            ['name' => 'FTP/SFTP',        'icon' => 'folder-up',      'color' => '#06B6D4', 'description' => 'File-transfer credentials'],
            ['name' => 'API Keys',        'icon' => 'key-round',      'color' => '#8B5CF6', 'description' => 'Third-party API keys and tokens'],
            ['name' => 'Social Media',    'icon' => 'at-sign',        'color' => '#EC4899', 'description' => 'Twitter/X, LinkedIn, Facebook, Instagram, etc.'],
            ['name' => 'Banking',         'icon' => 'landmark',       'color' => '#16A34A', 'description' => 'Bank accounts, payment gateways and finance portals'],
            ['name' => 'Software License','icon' => 'badge-check',    'color' => '#F97316', 'description' => 'Software license keys and activation codes'],
            ['name' => 'Other',           'icon' => 'folder',         'color' => '#71717A', 'description' => 'Everything else'],
        ];

        foreach ($categories as $i => $data) {
            Category::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['sort_order' => $i]),
            );
        }
    }
}
