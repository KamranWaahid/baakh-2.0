<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Languages;
use Illuminate\Http\Request;

class AdminCountriesController extends Controller
{
    
    public function index()
    {
        $countries = Countries::all();
        $languages = Languages::all();
        return view('admin.locations.country.index', compact('countries', 'languages'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'countryName' => 'required',
            'Abbreviation' => 'required',
            'countryDesc' => 'nullable',
            'Continent' => 'nullable',
            'capital_city' => 'nullable',
            'lang' => 'required',
        ]);

        Countries::create($request->all());

        return redirect()->route('admin.countries.index')
            ->with('success', 'Country created successfully.');
    }

    public function edit($id)
    {
        $country = Countries::findOrFail($id);
        return view('admin.locations.country.edit', compact('country'));
    }

    public function update(Request $request, $id)
    {
        $country = Countries::findOrFail($id);

        $request->validate([
            'countryName' => 'required',
            'Abbreviation' => 'required',
            'countryDesc' => 'nullable',
            'capital_city' => 'nullable',
            'lang' => 'required',
        ]);

        $country->update($request->all());

        return redirect()->route('admin.countries.index')
            ->with('success', 'Country updated successfully.');
    }

    public function destroy($id)
    {
        $country = Countries::findOrFail($id);
        $country->delete();

        return redirect()->route('admin.countries.index')
            ->with('success', 'Country deleted successfully.');
    }
}
