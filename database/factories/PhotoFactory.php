<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        $titles = [
            'Mountain Landscape', 'City Skyline', 'Sunset Beach', 'Forest Path', 'Desert Dunes',
            'Ocean View', 'Snowy Peak', 'Autumn Leaves', 'Spring Bloom', 'Winter Wonderland',
            'Tropical Paradise', 'Urban Street', 'Historic Castle', 'Modern Architecture', 'Ancient Temple',
            'Wildlife Safari', 'Underwater Coral', 'Night Skyline', 'Desert Sunset', 'Mountain Lake',
            'Waterfall', 'Glacier', 'Volcano', 'Rainforest', 'Savanna',
            'Northern Lights', 'Rainbow Bridge', 'Canyon', 'Cave', 'Island',
            'Coastal Cliff', 'Alpine Meadow', 'Bamboo Forest', 'Cherry Blossom', 'Redwood Forest',
            'Prairie', 'Tundra', 'Fjords', 'Lighthouse', 'Windmill',
            'Barn', 'Lighthouse', 'Bridge', 'Stadium', 'Monument',
            'Fountain', 'Garden', 'Park', 'Plaza', 'Market',
        ];

        $descriptions = [
            'A breathtaking view capturing the essence of nature',
            'Perfect lighting during golden hour',
            'Stunning perspective of this iconic location',
            'Natural beauty in its purest form',
            'A moment worth remembering',
            'The beauty of the world around us',
            'Captured at the perfect moment',
            'Nature at its finest',
            'A sight to behold',
            'Simply magnificent',
        ];

        $cameras = ['Canon EOS R5', 'Sony A7IV', 'Nikon Z9', 'Fujifilm X-T5', 'Leica M11'];
        $locations = ['North America', 'Europe', 'Asia', 'South America', 'Africa', 'Oceania'];

        return [
            'title' => $this->faker->randomElement($titles) . ' ' . $this->faker->unique()->numberBetween(1, 300),
            'url' => 'https://picsum.photos/seed/' . $this->faker->unique()->uuid . '/800/600',
            'description' => $this->faker->randomElement($descriptions),
            'metadata' => json_encode([
                'camera' => $this->faker->randomElement($cameras),
                'location' => $this->faker->randomElement($locations),
                'ISO' => $this->faker->randomElement([100, 200, 400, 800, 1600]),
                'aperture' => $this->faker->randomElement(['f/1.4', 'f/2.8', 'f/4', 'f/5.6', 'f/8']),
                'shutter_speed' => $this->faker->randomElement(['1/1000', '1/500', '1/250', '1/125', '1/60']),
            ]),
        ];
    }
}