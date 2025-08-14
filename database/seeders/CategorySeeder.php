<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Science',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/atom-2.svg',
            ],
            [
                'name' => 'History',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/building-monument.svg',
            ],
            [
                'name' => 'Technology',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/cpu.svg',
            ],
            [
                'name' => 'Nature',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/leaf.svg',
            ],
            [
                'name' => 'Space',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/planet.svg',
            ],
            [
                'name' => 'Animals',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/paw.svg',
            ],
            [
                'name' => 'Geography',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/world.svg',
            ],
            [
                'name' => 'Sports',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/ball-football.svg',
            ],
            [
                'name' => 'Art & Culture',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/palette.svg',
            ],
            [
                'name' => 'Food & Cooking',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/chef-hat.svg',
            ],
            [
                'name' => 'Music',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/music.svg',
            ],
            [
                'name' => 'Literature',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/book.svg',
            ],
            [
                'name' => 'Health & Medicine',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/heart-rate-monitor.svg',
            ],
            [
                'name' => 'Psychology',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/brain.svg',
            ],
            [
                'name' => 'Economics',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/chart-line.svg',
            ],
            [
                'name' => 'Movies & Cinema',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/movie.svg',
            ],
            [
                'name' => 'Architecture',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/building-skyscraper.svg',
            ],
            [
                'name' => 'Transportation',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/car.svg',
            ],
            [
                'name' => 'Language',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/abc.svg',
            ],
            [
                'name' => 'Environment',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/plant.svg',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
