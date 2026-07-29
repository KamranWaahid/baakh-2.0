<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Districts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistrictController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index(Request $request)
    {
        $query = Districts::with(['province.details', 'details', 'talukas.details'])->latest();

        if ($request->filled('province_id')) {
            $query->where('province_id', (int) $request->province_id);
        }

        $districts = $query->get()->map(fn (Districts $d) => $this->serialize($d));

        return response()->json($districts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'required|exists:location_provinces,id',
            'details' => 'required|array',
            'details.sd.district_name' => 'required|string|max:255',
            'details.en.district_name' => 'nullable|string|max:255',
        ]);

        $district = DB::transaction(function () use ($request, $validated) {
            $district = Districts::create([
                'user_id' => $request->user()->id,
                'province_id' => $validated['province_id'],
            ]);

            foreach ($validated['details'] as $lang => $detail) {
                if (!empty($detail['district_name'])) {
                    $district->details()->create([
                        'district_name' => strip_tags($detail['district_name']),
                        'lang' => $lang,
                    ]);
                }
            }

            return $district;
        });

        ActivityLog::log('created_district', $request->user(), null, 'Created district: ' . $validated['details']['sd']['district_name']);

        return response()->json([
            'message' => 'District created successfully',
            'district' => $this->serialize($district->load(['details', 'province.details', 'talukas.details'])),
        ], 201);
    }

    public function show($id)
    {
        $district = Districts::with(['details', 'province.details', 'talukas.details'])->findOrFail($id);

        return response()->json($this->serialize($district));
    }

    public function update(Request $request, $id)
    {
        $district = Districts::findOrFail($id);

        $validated = $request->validate([
            'province_id' => 'required|exists:location_provinces,id',
            'details' => 'required|array',
            'details.sd.district_name' => 'required|string|max:255',
            'details.en.district_name' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($district, $validated) {
            $district->update(['province_id' => $validated['province_id']]);
            foreach ($validated['details'] as $lang => $detail) {
                if (!empty($detail['district_name'])) {
                    $district->details()->updateOrCreate(
                        ['lang' => $lang],
                        ['district_name' => strip_tags($detail['district_name'])]
                    );
                } else {
                    $district->details()->where('lang', $lang)->delete();
                }
            }
        });

        return response()->json([
            'message' => 'District updated successfully',
            'district' => $this->serialize($district->fresh(['details', 'province.details', 'talukas.details'])),
        ]);
    }

    public function destroy($id)
    {
        $district = Districts::findOrFail($id);
        $district->delete();

        return response()->json(['message' => 'District deleted']);
    }

    private function serialize(Districts $district): array
    {
        $names = [];
        foreach ($district->details as $detail) {
            $names[$detail->lang] = $detail->district_name;
        }

        return [
            'id' => $district->id,
            'province_id' => $district->province_id,
            'name' => $names['sd'] ?? $names['en'] ?? "District #{$district->id}",
            'names' => $names,
            'details' => $district->details,
            'province' => $district->province,
            'talukas_count' => $district->relationLoaded('talukas') ? $district->talukas->count() : null,
            'talukas' => $district->relationLoaded('talukas')
                ? $district->talukas->map(function ($t) {
                    $tNames = [];
                    foreach ($t->details as $d) {
                        $tNames[$d->lang] = $d->taluka_name;
                    }

                    return [
                        'id' => $t->id,
                        'name' => $tNames['sd'] ?? $tNames['en'] ?? "Taluka #{$t->id}",
                        'names' => $tNames,
                    ];
                })->values()
                : [],
        ];
    }
}
