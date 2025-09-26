<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class SampleArticlesSeeder extends Seeder
{
    public function run(): void
    {
        Article::firstOrCreate([
            'title' => 'Resetting Your Password',
        ], [
            'category' => 'Accounts',
            'content' => "## Resetting Your Password\n\n1. Go to the password reset page.\n2. Enter your company email.\n3. Check your inbox for the reset link.\n\nIf you still cannot log in, contact IT support.",
        ]);

        Article::firstOrCreate([
            'title' => 'Wi-Fi Troubleshooting Guide',
        ], [
            'category' => 'Network',
            'content' => "## Wi-Fi Troubleshooting\n\n- Ensure airplane mode is off.\n- Toggle Wi‑Fi off/on.\n- Forget and reconnect to `CorpNet`.\n- Reboot your device.\n\nIf issues persist, open a ticket with IT.",
        ]);
    }
}



