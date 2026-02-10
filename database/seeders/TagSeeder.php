<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tags;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'theme' => [
                'love',
                'separation',
                'longing',
                'oppression',
                'displacement',
                'memory',
                'identity',
                'colonialism',
                'war',
                'hope'
            ],
            'emotion' => [
                'melancholic',
                'angry',
                'tender',
                'rebellious',
                'hopeful',
                'nostalgic',
                'mourful'
            ],
            'time' => [
                // context tags can be added later
            ]
        ];

        foreach ($tags as $type => $names) {
            foreach ($names as $name) {
                Tags::updateOrCreate(
                    ['slug' => Str::slug($name), 'lang' => 'en'],
                    [
                        'tag' => $name,
                        'type' => $type,
                    ]
                );
            }
        }
    }
}
