<?php
/**
 * Assign topic_category_id to all tags.
 *
 * Topic Categories:
 *  1 = Love & Intimacy             (محبت ۽ چاھت)
 *  2 = Politics, Resistance & Power (سياست، مزاحمت ۽ طاقت)
 *  3 = Land, Nature & Ecology      (زمين، قدرت ۽ ماحوليات)
 *  4 = Identity, Self & Belonging  (تشخص، آتم ۽ تعلق)
 *  5 = Grief, Loss & Death         (ڏک، وڇوڙو ۽ موت)
 *  6 = Spiritual & Mystical        (روحانيت ۽ تصوف)
 *  7 = Women, Gender & Body        (عورت، جنس ۽ جسم)
 *  8 = Exile, Migration & Diaspora (جلاوطني، هجرت ۽ پکيڙيل برادري)
 *  9 = Memory, History & Time      (ياد، تاريخ ۽ وقت)
 * 10 = Everyday Life & Society     (روزمره جي زندگي ۽ سماج)
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Tags;
use Illuminate\Support\Facades\DB;

// ── Explicit per-tag mapping: slug => topic_category_id ──

$map = [
    // ───── THEME tags ─────
    // Love & Intimacy (1)
    'love' => 1,
    'intimacy' => 1,
    'desire' => 1,
    'union' => 1,
    'yearning' => 1,  // Emotion but belongs to Love thematically

    // Grief, Loss & Death (5)
    'separation' => 5,
    'longing' => 5,
    'betrayal' => 5,
    'forgetting' => 5,
    'loss' => 5,
    'grief' => 5,
    'mourning' => 5,
    'death' => 5,
    'survival' => 5,
    'despair' => 5,
    'erasure' => 5,

    // Identity, Self & Belonging (4)
    'identity' => 4,
    'selfhood' => 4,
    'belonging' => 4,
    'inheritance' => 4,

    // Politics, Resistance & Power (2)
    'power' => 2,
    'authority' => 2,
    'oppression' => 2,
    'injustice' => 2,
    'resistance' => 2,
    'revolt' => 2,
    'freedom' => 2,
    'captivity' => 2,
    'violence' => 2,
    'war' => 2,
    'peace' => 2,

    // Everyday Life & Society (10)
    'silence' => 10,
    'voice' => 10,
    'speech' => 10,
    'truth' => 10,
    'lies' => 10,
    'home' => 10,

    // Land, Nature & Ecology (3)
    'land' => 3,
    'soil' => 3,
    'night' => 3,

    // Exile, Migration & Diaspora (8)
    'exile' => 8,
    'migration' => 8,
    'displacement' => 8,
    'homeland' => 8,

    // Spiritual & Mystical (6)
    'faith' => 6,
    'doubt' => 6,
    'hope' => 6,
    'dream' => 6,

    // Memory, History & Time (9)
    'time' => 9,
    'history' => 9,
    'fate' => 9,
    'destiny' => 9,

    // ───── EMOTION tags ─────
    // Grief, Loss & Death (5)
    'melancholic' => 5,
    'mournful' => 5,
    'nostalgic' => 5,
    'lonely' => 5,
    'fearful' => 5,
    'anxious' => 5,

    // Love & Intimacy (1)
    'tender' => 1,
    'intimate' => 1,
    'serene' => 1,

    // Politics, Resistance & Power (2)
    'angry' => 2,
    'bitter' => 2,
    'defiant' => 2,
    'rebellious' => 2,
    'violent' => 2,
    'restless' => 2,

    // Spiritual & Mystical (6)
    'hopeful' => 6,
    'reflective' => 6,
    'quiet' => 6,

    // ───── OCCASION tags ─────
    // Everyday Life & Society (10)
    'festival' => 10,
    'celebration' => 10,
    'gathering' => 10,

    // Politics, Resistance & Power (2)
    'protest' => 2,

    // Grief, Loss & Death (5)
    'funeral' => 5,

    // ───── STATUS tags ─────
    // Memory, History & Time (9)
    'classical' => 9,
    'modern' => 9,
    'contemporary' => 9,
    'experimental' => 9,

    // ───── TIME LAYER tags ─────
    // Memory, History & Time (9)
    'childhood' => 9,
    'youth' => 9,
    'aging' => 9,
    'old-age' => 9,
    'past' => 9,
    'present' => 9,
    'future' => 9,
    'ancestry' => 9,
    'legacy' => 9,
];

echo "=== Tag Categorization Script ===\n";
echo "Mapping " . count($map) . " tags to topic categories...\n\n";

$updated = 0;
$errors = [];

DB::beginTransaction();

try {
    // Bypass TagObserver to avoid SQLite unified_tags conflicts
    Tags::withoutEvents(function () use ($map, &$updated, &$errors) {
        foreach ($map as $slug => $topicCategoryId) {
            $tag = Tags::where('slug', $slug)->first();
            if (!$tag) {
                $errors[] = "Tag not found: $slug";
                continue;
            }
            $tag->topic_category_id = $topicCategoryId;
            $tag->save();
            $updated++;

            $enName = $tag->details->where('lang', 'en')->first()?->name ?? $slug;
            echo "  ✓ [$updated] $enName → Category $topicCategoryId\n";
        }
    });

    DB::commit();
    echo "\n✅ Successfully updated $updated tags.\n";

    if (!empty($errors)) {
        echo "\n⚠️  Errors:\n";
        foreach ($errors as $e)
            echo "  - $e\n";
    }

    // Summary
    $unmapped = Tags::whereNull('topic_category_id')->get();
    if ($unmapped->count() > 0) {
        echo "\n⚠️  " . $unmapped->count() . " tags still have no topic category:\n";
        foreach ($unmapped as $t) {
            echo "  - ID {$t->id}: {$t->slug}\n";
        }
    } else {
        echo "\n🎉 All tags are now categorized!\n";
    }

} catch (\Throwable $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
