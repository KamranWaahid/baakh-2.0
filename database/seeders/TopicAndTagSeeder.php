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
            TopicCategory::updateOrCreate(
                ['slug' => Str::slug($category)],
                ['name' => $category]
            );
        }

        // 2. Wipe existing tags and seed new ones
        Tags::query()->forceDelete();

        $tags = [
            'THEME TAGS' => [
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
            'EMOTION / MOOD TAGS' => [
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
            'PLACE & GEOGRAPHY TAGS' => [
                'Sindh',
                'Thar',
                'Karachi',
                'Hyderabad',
                'Larkana',
                'Indus',
                'Desert',
                'River',
                'Sea',
                'Village',
                'City',
                'Border',
                'Homeland',
                'South Asia'
            ],
            'IDENTITY & SOCIAL CONTEXT TAGS' => [
                'indigenous',
                'peasant',
                'farmer',
                'working class',
                'labour',
                'womanhood',
                'motherhood',
                'girlhood',
                'patriarchy',
                'masculinity',
                'caste',
                'tribe',
                'minority',
                'diaspora',
                'refugee',
                'statelessness'
            ],
            'NATURE & ECOLOGY TAGS' => [
                'earth',
                'water',
                'fire',
                'wind',
                'rain',
                'drought',
                'flood',
                'tree',
                'forest',
                'animal',
                'bird',
                'season',
                'climate',
                'ecology',
                'destruction',
                'extraction'
            ],
            'SPIRITUAL & PHILOSOPHICAL TAGS' => [
                'spirituality',
                'mysticism',
                'sufism',
                'prayer',
                'god',
                'soul',
                'body',
                'sacrifice',
                'transcendence',
                'awakening',
                'illusion',
                'truth',
                'mortality'
            ],
            'POLITICAL / HISTORICAL CONTEXT TAGS' => [
                'colonialism',
                'colonial rule',
                'imperialism',
                'development',
                'capitalism',
                'extraction',
                'militarization',
                'state violence',
                'enforced disappearance',
                'censorship',
                'surveillance',
                'elections',
                'dictatorship',
                'partition',
                'revolution'
            ],
            'RELATIONSHIPS & SOCIAL LIFE TAGS' => [
                'mother',
                'father',
                'child',
                'lover',
                'beloved',
                'friendship',
                'family',
                'community',
                'society',
                'marriage',
                'separation',
                'absence'
            ],
            'TIME & TEMPORAL TAGS' => [
                'childhood',
                'youth',
                'aging',
                'old age',
                'past',
                'present',
                'future',
                'memory',
                'ancestry',
                'legacy'
            ],
        ];

        foreach ($tags as $type => $tagNames) {
            foreach ($tagNames as $name) {
                Tags::create([
                    'tag' => $name,
                    'slug' => Str::slug($name),
                    'type' => $type,
                    'lang' => 'en' // Default to English for these tags as requested in English
                ]);
            }
        }
    }
}
