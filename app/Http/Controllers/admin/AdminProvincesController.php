<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Languages;
use App\Models\Provinces;
use Illuminate\Http\Request;

class AdminProvincesController extends Controller
{
    public function index()
    {
        $provinces = Provinces::with('country')->get();
        $countries = Countries::all();
        $languages = Languages::all();
        return view('admin.locations.province.index', compact('provinces','countries', 'languages'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'province_name' => 'required',
            'country_id' => 'required',
            'lang' => 'required',
        ]);

        Provinces::create($request->all());

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Province created successfully.');
    }

    public function edit($id)
    {
        $province = Provinces::findOrFail($id);
        $countries = Countries::all();
        $languages = Languages::all();
        return view('admin.locations.province.edit', compact('province', 'countries', 'languages'));
    }

    public function update(Request $request, $id)
    {
        $province = Provinces::findOrFail($id);

        $request->validate([
            'province_name' => 'required',
            'country_id' => 'required',
            'lang' => 'required',
        ]);

        $province->update($request->all());

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Province updated successfully.');
    }

    public function destroy($id)
    {
        $province = Provinces::findOrFail($id);
        $province->delete();

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Province deleted successfully.');
    }
}
