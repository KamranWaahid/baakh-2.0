<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TopicCategory;
use App\Models\Tags;
use Illuminate\Support\Str;

class TopicAndTagSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Topic Categories
        $topicCategories = [
            'Love & Intimacy',
            'Politics, Resistance & Power',
            'Land, Nature & Ecology',
            'Identity, Self & Belonging',
            'Grief, Loss & Death',
            'Spiritual & Mystical',
            'Women, Gender & Body',
            'Exile, Migration & Diaspora',
            'Memory, History & Time',
            'Everyday Life & Society',
        ];

        foreach ($topicCategories as $category) {
            $tc = TopicCategory::updateOrCreate(
                ['slug' => Str::slug($category)]
            );
            $tc->details()->updateOrCreate(
                ['lang' => 'sd'],
                ['name' => $category] // Assuming English names for now, but in Sindhi if available
            );
        }

        // 2. Wipe existing tags and seed new ones
        Tags::query()->forceDelete();

        $tags = [
            'Theme' => [
                'love',
                'intimacy',
                'desire',
                'separation',
                'longing',
                'betrayal',
                'union',
                'memory',
                'forgetting',
                'identity',
                'selfhood',
                'belonging',
                'silence',
                'voice',
                'speech',
                'truth',
                'lies',
                'power',
                'authority',
                'oppression',
                'injustice',
                'resistance',
                'revolt',
                'freedom',
                'captivity',
                'violence',
                'war',
                'peace',
                'loss',
                'grief',
                'mourning',
                'death',
                'survival',
                'hope',
                'despair',
                'faith',
                'doubt',
                'time',
                'history',
                'fate',
                'destiny',
                'dream',
                'night',
                'exile',
                'migration',
                'displacement',
                'home',
                'homeland',
                'land',
                'soil',
                'inheritance',
                'erasure'
            ],
            'Emotion' => [
                'melancholic',
                'mournful',
                'tender',
                'intimate',
                'nostalgic',
                'angry',
                'bitter',
                'defiant',
                'rebellious',
                'hopeful',
                'anxious',
                'fearful',
                'lonely',
                'yearning',
                'serene',
                'quiet',
                'violent',
                'restless',
                'reflective'
            ],
            'Occasion' => [
                'Festival',
                'Protest',
                'Celebration',
                'Funeral',
                'Gathering'
            ],
            'Status' => [
                'Classical',
                'Modern',
                'Contemporary',
                'Experimental'
            ],
            'Time Layer' => [
                'Childhood',
                'Youth',
                'Aging',
                'Old Age',
                'Past',
                'Present',
                'Future',
                'Memory',
                'Ancestry',
                'Legacy'
            ]
        ];

        foreach ($tags as $type => $tagNames) {
            foreach ($tagNames as $name) {
                $tag = Tags::create([
                    'slug' => Str::slug($name),
                    'type' => $type
                ]);
                $tag->details()->create([
                    'name' => ucfirst($name),
                    'lang' => 'sd' // Default to SD for now, translations can be added later
                ]);
            }
        }
    }
}
