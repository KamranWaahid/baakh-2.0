<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provinces;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index(Request $request)
    {
        $query = Provinces::with(['country.details', 'details'])->latest();

        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'country_id' => 'required|exists:location_countries,id',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.province_name' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.province_name' => 'nullable|string|max:255',
        ]);

        $province = \DB::transaction(function () use ($request, $validatedData) {
            $province = Provinces::create([
                'user_id' => $request->user()->id,
                'country_id' => $validatedData['country_id'],
            ]);

            foreach ($request->details as $lang => $detail) {
                if (!empty($detail['province_name'])) {
                    $province->details()->create([
                        'province_name' => $detail['province_name'],
                        'lang' => $lang
                    ]);
                }
            }
            return $province;
        });

        ActivityLog::log('created_province', $request->user(), null, "Created province: " . ($request->details['sd']['province_name']));

        return response()->json([
            'message' => 'Province created successfully',
            'province' => $province->load(['details', 'country'])
        ]);
    }

    public function show($id)
    {
        $province = Provinces::with(['country', 'details'])->findOrFail($id);
        return response()->json($province);
    }

    public function update(Request $request, $id)
    {
        $province = Provinces::findOrFail($id);

        $validatedData = $request->validate([
            'country_id' => 'required|exists:location_countries,id',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.province_name' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.province_name' => 'nullable|string|max:255',
        ]);

        \DB::transaction(function () use ($province, $validatedData, $request) {
            $province->update([
                'country_id' => $validatedData['country_id'],
            ]);

            foreach ($request->details as $lang => $detail) {
                if (!empty($detail['province_name'])) {
                    $province->details()->updateOrCreate(
                        ['lang' => $lang],
                        [
                            'province_name' => $detail['province_name']
                        ]
                    );
                }
            }
        });

        ActivityLog::log('updated_province', $request->user(), null, "Updated province: " . ($request->details['sd']['province_name']));

        return response()->json([
            'message' => 'Province updated successfully',
            'province' => $province->load(['details', 'country'])
        ]);
    }


    public function destroy($id)
    {
        $province = Provinces::findOrFail($id);
        $province->delete();

        ActivityLog::log('deleted_province', request()->user(), null, "Deleted province: {$province->province_name}");

        return response()->json(['message' => 'Province deleted successfully']);
    }
}
