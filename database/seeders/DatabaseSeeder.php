<?php

namespace Database\Seeders;

use App\Models\Photo;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $photos = [
            ['title' => 'Beach Sunset', 'url' => 'https://picsum.photos/seed/beach/400/300', 'thumbnail' => 'https://picsum.photos/seed/beach/200/150'],
            ['title' => 'Mountain View', 'url' => 'https://picsum.photos/seed/mountain/400/300', 'thumbnail' => 'https://picsum.photos/seed/mountain/200/150'],
            ['title' => 'City Skyline', 'url' => 'https://picsum.photos/seed/city/400/300', 'thumbnail' => 'https://picsum.photos/seed/city/200/150'],
            ['title' => 'Forest Path', 'url' => 'https://picsum.photos/seed/forest/400/300', 'thumbnail' => 'https://picsum.photos/seed/forest/200/150'],
            ['title' => 'Ocean Waves', 'url' => 'https://picsum.photos/seed/ocean/400/300', 'thumbnail' => 'https://picsum.photos/seed/ocean/200/150'],
            ['title' => 'Desert Dunes', 'url' => 'https://picsum.photos/seed/desert/400/300', 'thumbnail' => 'https://picsum.photos/seed/desert/200/150'],
            ['title' => 'Snowy Peaks', 'url' => 'https://picsum.photos/seed/snow/400/300', 'thumbnail' => 'https://picsum.photos/seed/snow/200/150'],
            ['title' => 'Autumn Leaves', 'url' => 'https://picsum.photos/seed/autumn/400/300', 'thumbnail' => 'https://picsum.photos/seed/autumn/200/150'],
            ['title' => 'Spring Flowers', 'url' => 'https://picsum.photos/seed/flowers/400/300', 'thumbnail' => 'https://picsum.photos/seed/flowers/200/150'],
            ['title' => 'Night Sky', 'url' => 'https://picsum.photos/seed/night/400/300', 'thumbnail' => 'https://picsum.photos/seed/night/200/150'],
        ];

        foreach ($photos as $photo) {
            Photo::create($photo);
        }
    }
}