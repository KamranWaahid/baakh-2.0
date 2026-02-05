<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Countries::with('details')->latest()->get();
        return response()->json($countries);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'Abbreviation' => 'nullable|string|max:10',
            'Continent' => 'nullable|string|max:50',
            'capital_city' => 'nullable|integer',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.countryName' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.countryName' => 'nullable|string|max:255',
        ]);

        $country = DB::transaction(function () use ($request, $validatedData) {
            $country = Countries::create([
                'user_id' => $request->user()->id,
                'Abbreviation' => $validatedData['Abbreviation'] ?? null,
                'Continent' => $validatedData['Continent'] ?? null,
                'capital_city' => $validatedData['capital_city'] ?? null,
            ]);

            foreach ($request->details as $lang => $detail) {
                if (!empty($detail['countryName'])) {
                    $country->details()->create([
                        'countryName' => $detail['countryName'],
                        'countryDesc' => $detail['countryDesc'] ?? null, // Optional description
                        'lang' => $lang
                    ]);
                }
            }
            return $country;
        });

        ActivityLog::log('created_country', $request->user(), null, "Created country: " . ($request->details['sd']['countryName']));

        return response()->json([
            'message' => 'Country created successfully',
            'country' => $country->load('details')
        ]);
    }

    public function show($id)
    {
        $country = Countries::with('details')->findOrFail($id);
        return response()->json($country);
    }

    public function update(Request $request, $id)
    {
        $country = Countries::findOrFail($id);

        $validatedData = $request->validate([
            'Abbreviation' => 'nullable|string|max:10',
            'Continent' => 'nullable|string|max:50',
            'capital_city' => 'nullable|integer',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.countryName' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.countryName' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($country, $validatedData, $request) {
            $country->update([
                'Abbreviation' => $validatedData['Abbreviation'] ?? null,
                'Continent' => $validatedData['Continent'] ?? null,
                'capital_city' => $validatedData['capital_city'] ?? null,
            ]);

            foreach ($request->details as $lang => $detail) {
                if (!empty($detail['countryName'])) {
                    $country->details()->updateOrCreate(
                        ['lang' => $lang],
                        [
                            'countryName' => $detail['countryName'],
                            'countryDesc' => $detail['countryDesc'] ?? null
                        ]
                    );
                }
            }
        });

        ActivityLog::log('updated_country', $request->user(), null, "Updated country: " . ($request->details['sd']['countryName']));

        return response()->json([
            'message' => 'Country updated successfully',
            'country' => $country->load('details')
        ]);
    }

    public function destroy($id)
    {
        $country = Countries::findOrFail($id);
        $country->delete();

        ActivityLog::log('deleted_country', request()->user(), null, "Deleted country: {$country->countryName}");

        return response()->json(['message' => 'Country deleted successfully']);
    }
}
