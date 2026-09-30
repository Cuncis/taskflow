<?php

namespace Database\Factories;

use App\Domain\Collaboration\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /** @var class-string<Attachment> */
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unique()->word().'.png';

        return [
            'filename' => $filename,
            'path' => 'attachments/'.$filename,
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1_000, 5_000_000),
        ];
    }
}
