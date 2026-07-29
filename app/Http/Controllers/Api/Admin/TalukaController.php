<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Talukas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TalukaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index(Request $request)
    {
        $query = Talukas::with(['district.details', 'district.province.details', 'details'])->latest();

        if ($request->filled('district_id')) {
            $query->where('district_id', (int) $request->district_id);
        }
        if ($request->filled('province_id')) {
            $query->whereHas('district', fn ($q) => $q->where('province_id', (int) $request->province_id));
        }

        $talukas = $query->get()->map(fn (Talukas $t) => $this->serialize($t));

        return response()->json($talukas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:location_districts,id',
            'details' => 'required|array',
            'details.sd.taluka_name' => 'required|string|max:255',
            'details.en.taluka_name' => 'nullable|string|max:255',
        ]);

        $taluka = DB::transaction(function () use ($request, $validated) {
            $taluka = Talukas::create([
                'user_id' => $request->user()->id,
                'district_id' => $validated['district_id'],
            ]);

            foreach ($validated['details'] as $lang => $detail) {
                if (!empty($detail['taluka_name'])) {
                    $taluka->details()->create([
                        'taluka_name' => strip_tags($detail['taluka_name']),
                        'lang' => $lang,
                    ]);
                }
            }

            return $taluka;
        });

        ActivityLog::log('created_taluka', $request->user(), null, 'Created taluka: ' . $validated['details']['sd']['taluka_name']);

        return response()->json([
            'message' => 'Taluka created successfully',
            'taluka' => $this->serialize($taluka->load(['details', 'district.details', 'district.province.details'])),
        ], 201);
    }

    public function show($id)
    {
        $taluka = Talukas::with(['details', 'district.details', 'district.province.details'])->findOrFail($id);

        return response()->json($this->serialize($taluka));
    }

    public function update(Request $request, $id)
    {
        $taluka = Talukas::findOrFail($id);

        $validated = $request->validate([
            'district_id' => 'required|exists:location_districts,id',
            'details' => 'required|array',
            'details.sd.taluka_name' => 'required|string|max:255',
            'details.en.taluka_name' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($taluka, $validated) {
            $taluka->update(['district_id' => $validated['district_id']]);
            foreach ($validated['details'] as $lang => $detail) {
                if (!empty($detail['taluka_name'])) {
                    $taluka->details()->updateOrCreate(
                        ['lang' => $lang],
                        ['taluka_name' => strip_tags($detail['taluka_name'])]
                    );
                } else {
                    $taluka->details()->where('lang', $lang)->delete();
                }
            }
        });

        return response()->json([
            'message' => 'Taluka updated successfully',
            'taluka' => $this->serialize($taluka->fresh(['details', 'district.details', 'district.province.details'])),
        ]);
    }

    public function destroy($id)
    {
        $taluka = Talukas::findOrFail($id);
        $taluka->delete();

        return response()->json(['message' => 'Taluka deleted']);
    }

    private function serialize(Talukas $taluka): array
    {
        $names = [];
        foreach ($taluka->details as $detail) {
            $names[$detail->lang] = $detail->taluka_name;
        }

        return [
            'id' => $taluka->id,
            'district_id' => $taluka->district_id,
            'name' => $names['sd'] ?? $names['en'] ?? "Taluka #{$taluka->id}",
            'names' => $names,
            'details' => $taluka->details,
            'district' => $taluka->district,
        ];
    }
}
