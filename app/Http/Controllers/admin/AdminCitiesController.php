<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Cities;
use App\Models\Countries;
use App\Models\Languages;
use App\Models\Provinces;
use Illuminate\Http\Request;

class AdminCitiesController extends Controller
{
    public function index()
    {
        $cities = Cities::with('province')->get();
        $languages = Languages::all();
        $countries = Countries::all();
        return view('admin.locations.cities.index', compact('cities', 'languages', 'countries'));
    }

    public function create()
    {
        $provinces = Provinces::all();
        return view('admin.cities.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'city_name' => 'required',
            'province_id' => 'required',
            'lang' => 'required',
        ]);

        Cities::create($request->all());

        return redirect()->route('admin.cities.index')
            ->with('success', 'City created successfully.');
    }

    public function edit($id)
    {
        $city = Cities::findOrFail($id);
        $provinces = Provinces::all();
        return view('admin.cities.edit', compact('city', 'provinces'));
    }

    public function update(Request $request, $id)
    {
        $city = Cities::findOrFail($id);

        $request->validate([
            'city_name' => 'required',
            'province_id' => 'required',
            'lang' => 'required',
        ]);

        $city->update($request->all());

        return redirect()->route('admin.cities.index')
            ->with('success', 'City updated successfully.');
    }

    public function destroy($id)
    {
        $city = Cities::findOrFail($id);
        $city->delete();
 
        $message = [
            'message' => 'City deleted',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }

    /*
    | Other Related Method
    */ 
    public function getProvinces($countryId)
    {
        $provinces = Provinces::where('country_id', $countryId)->get();
        return response()->json($provinces);
    }

    public function getCitiesByLang($lang)
    {
        $cities = Cities::where('lang', $lang)->get();
        return response()->json($cities);
    }
}
