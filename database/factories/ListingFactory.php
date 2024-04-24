<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'authors' => $this->faker->name . ', ' . $this->faker->name,
            'title' => $this->faker->sentence,
            'subtitle' => $this->faker->paragraph,
            'description' => $this->faker->paragraph,
            'categories' => $this->faker->paragraph,
            'canonicalVolumeLink' => $this->faker->paragraph,
            'infoLink' => $this->faker->paragraph,
            'previewLink' => $this->faker->paragraph,
            'imageLink_thumbnail' => $this->faker->paragraph,
            'publishedDate' => $this->faker->paragraph,
        ];
    }
}
