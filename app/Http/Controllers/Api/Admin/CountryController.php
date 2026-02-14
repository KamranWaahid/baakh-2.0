<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

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
                'Abbreviation' => strip_tags($validatedData['Abbreviation'] ?? null),
                'Continent' => strip_tags($validatedData['Continent'] ?? null),
                'capital_city' => $validatedData['capital_city'] ?? null,
            ]);

            foreach ($validatedData['details'] as $lang => $detail) {
                if (!empty($detail['countryName'])) {
                    $country->details()->create([
                        'countryName' => strip_tags($detail['countryName']),
                        'countryDesc' => strip_tags($detail['countryDesc'] ?? null),
                        'lang' => $lang
                    ]);
                }
            }
            return $country;
        });

        ActivityLog::log('created_country', $request->user(), null, "Created country: " . ($validatedData['details']['sd']['countryName']));

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

        DB::transaction(function () use ($country, $validatedData) {
            $country->update([
                'Abbreviation' => strip_tags($validatedData['Abbreviation'] ?? null),
                'Continent' => strip_tags($validatedData['Continent'] ?? null),
                'capital_city' => $validatedData['capital_city'] ?? null,
            ]);

            foreach ($validatedData['details'] as $lang => $detail) {
                if (!empty($detail['countryName'])) {
                    $country->details()->updateOrCreate(
                        ['lang' => $lang],
                        [
                            'countryName' => strip_tags($detail['countryName']),
                            'countryDesc' => strip_tags($detail['countryDesc'] ?? null)
                        ]
                    );
                }
            }
        });

        ActivityLog::log('updated_country', $request->user(), null, "Updated country: " . ($validatedData['details']['sd']['countryName']));

        return response()->json([
            'message' => 'Country updated successfully',
            'country' => $country->load('details')
        ]);
    }

    public function destroy($id)
    {
        $country = Countries::with('details')->findOrFail($id);
        $sdName = $country->details->where('lang', 'sd')->first()?->countryName ?? 'Unnamed';
        $country->delete();

        ActivityLog::log('deleted_country', request()->user(), null, "Deleted country: {$sdName}");

        return response()->json(['message' => 'Country deleted successfully']);
    }
}
