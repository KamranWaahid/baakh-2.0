<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cities;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Added this use statement

class CityController extends Controller
{
    public function index(Request $request)
    {
        $query = Cities::with(['province.details', 'details'])->latest();

        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'geo_lat' => 'nullable|string|max:50',
            'geo_long' => 'nullable|string|max:50',
            'province_id' => 'required|exists:location_provinces,id',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.city_name' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.city_name' => 'nullable|string|max:255',
        ]);

        $city = DB::transaction(function () use ($request, $validatedData) {
            $city = Cities::create([
                'user_id' => $request->user()->id,
                'geo_lat' => $validatedData['geo_lat'] ?? null,
                'geo_long' => $validatedData['geo_long'] ?? null,
                'province_id' => $validatedData['province_id'],
            ]);

            foreach ($request->details as $lang => $detail) {
                if (!empty($detail['city_name'])) {
                    $city->details()->create([
                        'city_name' => $detail['city_name'],
                        'lang' => $lang
                    ]);
                }
            }
            return $city;
        });

        ActivityLog::log('created_city', $request->user(), null, "Created city: " . ($request->details['sd']['city_name']));

        return response()->json([
            'message' => 'City created successfully',
            'city' => $city->load(['details', 'province'])
        ]);
    }

    public function show($id)
    {
        $city = Cities::with(['province', 'details'])->findOrFail($id);
        return response()->json($city);
    }

    public function update(Request $request, $id)
    {
        $city = Cities::findOrFail($id);

        $validatedData = $request->validate([
            'geo_lat' => 'nullable|string|max:50',
            'geo_long' => 'nullable|string|max:50',
            'province_id' => 'required|exists:location_provinces,id',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.city_name' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.city_name' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($city, $validatedData, $request) {
            $city->update([
                'geo_lat' => $validatedData['geo_lat'] ?? null,
                'geo_long' => $validatedData['geo_long'] ?? null,
                'province_id' => $validatedData['province_id'],
            ]);

            foreach ($request->details as $lang => $detail) {
                if (!empty($detail['city_name'])) {
                    $city->details()->updateOrCreate(
                        ['lang' => $lang],
                        [
                            'city_name' => $detail['city_name']
                        ]
                    );
                }
            }
        });

        ActivityLog::log('updated_city', $request->user(), null, "Updated city: " . ($request->details['sd']['city_name']));

        return response()->json([
            'message' => 'City updated successfully',
            'city' => $city->load(['details', 'province'])
        ]);
    }

    public function destroy($id)
    {
        $city = Cities::findOrFail($id);
        $city->delete();

        ActivityLog::log('deleted_city', request()->user(), null, "Deleted city: {$city->city_name}");

        return response()->json(['message' => 'City deleted successfully']);
    }
}
