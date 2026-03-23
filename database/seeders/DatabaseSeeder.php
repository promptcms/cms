<?php

namespace Database\Seeders;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Global Settings
        Setting::set('site_name', 'PromptCMS');
        Setting::set('site_tagline', 'Deine prompt-gesteuerte Website');
        Setting::set('contact_email', 'info@example.com');

        // Header/Footer Menu Nodes
        Node::query()->create([
            'type' => NodeType::Menu,
            'slug' => 'header-menu',
            'title' => 'Header Menu',
            'status' => NodeStatus::Published,
        ]);

        Node::query()->create([
            'type' => NodeType::Menu,
            'slug' => 'footer-menu',
            'title' => 'Footer Menu',
            'status' => NodeStatus::Published,
        ]);

        // Home Page
        $home = Node::query()->create([
            'type' => NodeType::Page,
            'slug' => 'home',
            'title' => 'Startseite',
            'status' => NodeStatus::Published,
        ]);

        $home->setMeta('template', 'home');
        $home->setMeta('meta_description', 'PromptCMS – What you prompt is what you get. AI-powered CMS.');
        $home->setMeta('content', '');

        $home->createRevision('Initial seed');
    }
}
