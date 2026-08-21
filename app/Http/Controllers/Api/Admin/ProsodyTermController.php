<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProsodyTerm;
use App\Services\StaticCacheService;
use Illuminate\Http\Request;

class ProsodyTermController extends Controller
{
    /**
     * @var list<string>
     */
    public const ICONS = [
        'Scale', 'Ruler', 'Music', 'Info', 'Scissors', 'Columns', 'Wrench',
        'Scroll', 'Footprints', 'Infinity', 'Anchor', 'Sunrise', 'Sunset',
    ];

    /**
     * @var list<string>
     */
    public const LOGIC_TYPES = ['chhand', 'arooz', 'both', 'generic'];

    public function __construct(private StaticCacheService $cache)
    {
    }

    public function index(Request $request)
    {
        $query = ProsodyTerm::query()->orderBy('order')->orderBy('id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title_sd', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%")
                    ->orWhere('desc_sd', 'like', "%{$search}%")
                    ->orWhere('desc_en', 'like', "%{$search}%");
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $term = ProsodyTerm::create($this->validated($request));
        $this->forgetPublicCache();

        return response()->json([
            'message' => 'Prosody term created',
            'data' => $term,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(ProsodyTerm::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $term = ProsodyTerm::findOrFail($id);
        $term->update($this->validated($request));
        $this->forgetPublicCache();

        return response()->json([
            'message' => 'Prosody term updated',
            'data' => $term->fresh(),
        ]);
    }

    public function destroy($id)
    {
        ProsodyTerm::findOrFail($id)->delete();
        $this->forgetPublicCache();

        return response()->json(['message' => 'Prosody term deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title_sd' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'desc_sd' => 'nullable|string',
            'desc_en' => 'nullable|string',
            'tech_detail_sd' => 'nullable|string',
            'tech_detail_en' => 'nullable|string',
            'logic_type' => 'nullable|in:'.implode(',', self::LOGIC_TYPES),
            'icon' => 'nullable|in:'.implode(',', self::ICONS),
            'order' => 'nullable|integer|min:0|max:9999',
        ]);
    }

    private function forgetPublicCache(): void
    {
        $this->cache->forget('prosody_list_sd');
        $this->cache->forget('prosody_list_en');
    }
}
