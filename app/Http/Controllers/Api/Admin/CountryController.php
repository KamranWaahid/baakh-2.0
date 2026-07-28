<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index()
    {
        $countries = $this->groupCountriesForList(
            Countries::with('details')->latest()->get()
        );

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
            'partner_ids' => 'nullable|array',
            'partner_ids.*' => 'integer|exists:location_countries,id',
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
                } else {
                    $country->details()->where('lang', $lang)->delete();
                }
            }

            // Legacy data stored one country row per language — fold partners in.
            foreach ($validatedData['partner_ids'] ?? [] as $partnerId) {
                if ((int) $partnerId === (int) $country->id) {
                    continue;
                }
                $partner = Countries::with('details')->find($partnerId);
                if (!$partner) {
                    continue;
                }
                foreach ($partner->details as $detail) {
                    if ($country->details()->where('lang', $detail->lang)->exists()) {
                        continue;
                    }
                    $country->details()->create([
                        'countryName' => $detail->countryName,
                        'countryDesc' => $detail->countryDesc,
                        'lang' => $detail->lang,
                    ]);
                }
                $partner->details()->delete();
                $partner->delete();
            }
        });

        ActivityLog::log('updated_country', $request->user(), null, "Updated country: " . ($validatedData['details']['sd']['countryName']));

        return response()->json([
            'message' => 'Country updated successfully',
            'country' => $country->fresh()->load('details')
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

    /**
     * Legacy imports created one country row per language (same Abbreviation).
     * Group those into a single list row with combined details.
     */
    private function groupCountriesForList(Collection $countries): Collection
    {
        $used = [];
        $grouped = collect();

        // Prefer grouping by shared abbreviation.
        $byAbbr = $countries->groupBy(function (Countries $country) {
            $abbr = strtolower(trim((string) ($country->Abbreviation ?? '')));
            return $abbr !== '' ? $abbr : 'id:' . $country->id;
        });

        foreach ($byAbbr as $key => $bucket) {
            $bucket = $bucket->values();
            if (str_starts_with((string) $key, 'id:') || $bucket->count() === 1) {
                $country = $bucket->first();
                if (!isset($used[$country->id])) {
                    $grouped->push($this->serializeCountry($country));
                    $used[$country->id] = true;
                }
                continue;
            }

            // Pick primary: prefer row that already has sd, else en, else oldest id.
            $primary = $bucket->first(fn (Countries $c) => $c->details->contains(fn ($d) => $d->lang === 'sd'))
                ?? $bucket->first(fn (Countries $c) => $c->details->contains(fn ($d) => $d->lang === 'en'))
                ?? $bucket->sortBy('id')->first();

            $partners = $bucket->where('id', '!=', $primary->id)->values();
            $extraDetails = $partners->flatMap(fn (Countries $c) => $c->details)->all();
            $partnerIds = $partners->pluck('id')->map(fn ($id) => (int) $id)->all();

            $grouped->push($this->serializeCountry($primary, $extraDetails, $partnerIds));
            foreach ($bucket as $c) {
                $used[$c->id] = true;
            }
        }

        return $grouped->values();
    }

    private function serializeCountry(Countries $country, array $extraDetails = [], array $partnerIds = []): array
    {
        $details = collect($country->details)->concat($extraDetails)->values();

        $detailsArr = $details->map(function ($detail) {
            if (is_array($detail)) {
                return $detail;
            }
            return [
                'id' => $detail->id ?? null,
                'country_id' => $detail->country_id ?? null,
                'countryName' => $detail->countryName,
                'countryDesc' => $detail->countryDesc ?? null,
                'lang' => $detail->lang,
            ];
        })->unique(fn ($d) => strtolower((string) ($d['lang'] ?? '')) . ':' . (string) ($d['countryName'] ?? ''))
            ->values()
            ->all();

        $sdName = collect($detailsArr)->firstWhere('lang', 'sd')['countryName'] ?? null;
        $enName = collect($detailsArr)->firstWhere('lang', 'en')['countryName'] ?? null;
        $anyName = collect($detailsArr)->first()['countryName'] ?? null;

        $names = [];
        foreach ($detailsArr as $detail) {
            $lang = strtolower((string) ($detail['lang'] ?? ''));
            if ($lang !== '' && !empty($detail['countryName'])) {
                $names[$lang] = $detail['countryName'];
            }
        }

        return [
            'id' => $country->id,
            'Abbreviation' => $country->Abbreviation,
            'Continent' => $country->Continent,
            'capital_city' => $country->capital_city,
            'partner_ids' => array_values($partnerIds),
            'name' => $sdName ?? $enName ?? $anyName ?? $country->Abbreviation ?? "Country #{$country->id}",
            'names' => $names,
            'details' => $detailsArr,
            'created_at' => $country->created_at,
            'updated_at' => $country->updated_at,
        ];
    }
}
