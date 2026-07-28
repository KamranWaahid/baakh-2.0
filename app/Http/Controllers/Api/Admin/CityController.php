<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cities;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index(Request $request)
    {
        $query = Cities::with(['province.country.details', 'province.details', 'details'])->latest();

        // Load all cities for pairing; filter applied after grouping so EN/SD twins stay together.
        $filterProvinceId = $request->filled('province_id') ? (int) $request->province_id : null;

        $cities = $this->groupCitiesForList($query->get());

        if ($filterProvinceId) {
            $cities = $cities->filter(function (array $city) use ($filterProvinceId) {
                $provinceIds = $city['province_ids'] ?? [($city['province_id'] ?? null)];
                return in_array($filterProvinceId, array_map('intval', $provinceIds), true);
            })->values();
        }

        return response()->json($cities);
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
                'geo_lat' => strip_tags($validatedData['geo_lat'] ?? null),
                'geo_long' => strip_tags($validatedData['geo_long'] ?? null),
                'province_id' => $validatedData['province_id'],
            ]);

            foreach ($validatedData['details'] as $lang => $detail) {
                if (!empty($detail['city_name'])) {
                    $city->details()->create([
                        'city_name' => strip_tags($detail['city_name']),
                        'lang' => $lang
                    ]);
                }
            }
            return $city;
        });

        ActivityLog::log('created_city', $request->user(), null, "Created city: " . ($validatedData['details']['sd']['city_name']));

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
            'partner_ids' => 'nullable|array',
            'partner_ids.*' => 'integer|exists:location_cities,id',
        ]);

        DB::transaction(function () use ($city, $validatedData) {
            $city->update([
                'geo_lat' => strip_tags($validatedData['geo_lat'] ?? null),
                'geo_long' => strip_tags($validatedData['geo_long'] ?? null),
                'province_id' => $validatedData['province_id'],
            ]);

            foreach ($validatedData['details'] as $lang => $detail) {
                if (!empty($detail['city_name'])) {
                    $city->details()->updateOrCreate(
                        ['lang' => $lang],
                        [
                            'city_name' => strip_tags($detail['city_name'])
                        ]
                    );
                } else {
                    $city->details()->where('lang', $lang)->delete();
                }
            }

            // Legacy data stored EN/SD as separate city rows — fold partners into this city.
            foreach ($validatedData['partner_ids'] ?? [] as $partnerId) {
                if ((int) $partnerId === (int) $city->id) {
                    continue;
                }
                $partner = Cities::with('details')->find($partnerId);
                if (!$partner) {
                    continue;
                }
                foreach ($partner->details as $detail) {
                    if ($city->details()->where('lang', $detail->lang)->exists()) {
                        continue;
                    }
                    $city->details()->create([
                        'city_name' => $detail->city_name,
                        'lang' => $detail->lang,
                    ]);
                }
                $partner->details()->delete();
                $partner->delete();
            }
        });

        ActivityLog::log('updated_city', $request->user(), null, "Updated city: " . ($validatedData['details']['sd']['city_name']));

        return response()->json([
            'message' => 'City updated successfully',
            'city' => $city->fresh()->load(['details', 'province'])
        ]);
    }

    public function destroy($id)
    {
        $city = Cities::with('details')->findOrFail($id);
        $sdName = $city->details->where('lang', 'sd')->first()?->city_name ?? 'Unnamed';
        $city->delete();

        ActivityLog::log('deleted_city', request()->user(), null, "Deleted city: {$sdName}");

        return response()->json(['message' => 'City deleted successfully']);
    }

    /**
     * Legacy imports created one city row per language. Pair close EN/SD orphans into one list row.
     */
    private function groupCitiesForList(Collection $cities): Collection
    {
        $used = [];
        $grouped = collect();

        foreach ($cities as $city) {
            if (isset($used[$city->id])) {
                continue;
            }

            $langs = $city->details->pluck('lang')->unique()->filter()->values();
            if ($langs->count() >= 2) {
                $grouped->push($this->serializeCity($city));
                $used[$city->id] = true;
                continue;
            }

            $lang = $langs->first();
            $partner = null;
            $bestDiff = PHP_INT_MAX;

            if ($lang) {
                foreach ($cities as $other) {
                    if ($other->id === $city->id || isset($used[$other->id])) {
                        continue;
                    }
                    $otherLangs = $other->details->pluck('lang')->unique()->filter();
                    if ($otherLangs->count() !== 1) {
                        continue;
                    }
                    $otherLang = $otherLangs->first();
                    if ($otherLang === $lang) {
                        continue;
                    }

                    $diff = abs(strtotime((string) $city->created_at) - strtotime((string) $other->created_at));
                    if ($diff <= 600 && $diff < $bestDiff) {
                        $bestDiff = $diff;
                        $partner = $other;
                    }
                }
            }

            if ($partner) {
                $grouped->push($this->serializeMergedCity($city, $partner));
                $used[$city->id] = true;
                $used[$partner->id] = true;
            } else {
                $grouped->push($this->serializeCity($city));
                $used[$city->id] = true;
            }
        }

        return $grouped->values();
    }

    private function serializeCity(Cities $city, array $extraDetails = [], array $partnerIds = []): array
    {
        $this->hydrateProvinceNames($city);

        $details = collect($city->details)->concat($extraDetails)->values();
        $sdName = $details->firstWhere('lang', 'sd')?->city_name
            ?? (is_array($details->firstWhere('lang', 'sd')) ? $details->firstWhere('lang', 'sd')['city_name'] : null);
        $enName = $details->firstWhere('lang', 'en')?->city_name
            ?? (is_array($details->firstWhere('lang', 'en')) ? $details->firstWhere('lang', 'en')['city_name'] : null);

        // Normalize details to plain arrays
        $detailsArr = $details->map(function ($detail) {
            if (is_array($detail)) {
                return $detail;
            }
            return [
                'id' => $detail->id ?? null,
                'city_id' => $detail->city_id ?? null,
                'city_name' => $detail->city_name,
                'lang' => $detail->lang,
            ];
        })->values()->all();

        $sdName = collect($detailsArr)->firstWhere('lang', 'sd')['city_name'] ?? null;
        $enName = collect($detailsArr)->firstWhere('lang', 'en')['city_name'] ?? null;
        $anyName = collect($detailsArr)->first()['city_name'] ?? null;

        return [
            'id' => $city->id,
            'province_id' => $city->province_id,
            'province_ids' => array_values(array_unique(array_filter([(int) $city->province_id]))),
            'partner_ids' => array_values($partnerIds),
            'geo_lat' => $city->geo_lat,
            'geo_long' => $city->geo_long,
            'name' => $sdName ?? $enName ?? $anyName ?? "City #{$city->id}",
            'names' => [
                'sd' => $sdName,
                'en' => $enName,
            ],
            'details' => $detailsArr,
            'province' => $city->province,
            'created_at' => $city->created_at,
            'updated_at' => $city->updated_at,
        ];
    }

    private function serializeMergedCity(Cities $a, Cities $b): array
    {
        $this->hydrateProvinceNames($a);
        $this->hydrateProvinceNames($b);

        // Prefer the Sindhi row as the editable primary id when present.
        $aLang = $a->details->first()?->lang;
        $primary = $aLang === 'sd' ? $a : ($b->details->first()?->lang === 'sd' ? $b : $a);
        $partner = $primary->id === $a->id ? $b : $a;

        $payload = $this->serializeCity($primary, $partner->details->all(), [(int) $partner->id]);
        $payload['province_ids'] = array_values(array_unique(array_filter([
            (int) $primary->province_id,
            (int) $partner->province_id,
        ])));
        $payload['geo_lat'] = $primary->geo_lat ?: $partner->geo_lat;
        $payload['geo_long'] = $primary->geo_long ?: $partner->geo_long;

        // Combine province label when EN/SD provinces were split historically.
        $pNames = collect([
            $primary->province?->name,
            $partner->province?->name,
        ])->filter()->unique()->values();
        if ($payload['province'] && $pNames->count() > 1) {
            $payload['province']->name = $pNames->implode(' / ');
        } elseif (!$payload['province'] && $partner->province) {
            $payload['province'] = $partner->province;
        }

        return $payload;
    }

    private function hydrateProvinceNames(Cities $city): void
    {
        if (!$city->province) {
            return;
        }

        $p_sdName = $city->province->details->where('lang', 'sd')->first()?->province_name;
        $p_enName = $city->province->details->where('lang', 'en')->first()?->province_name;
        $p_anyName = $city->province->details->first()?->province_name;
        $city->province->name = $p_sdName ?? $p_enName ?? $p_anyName ?? "Province #{$city->province->id}";

        if ($city->province->country) {
            $c_sdName = $city->province->country->details->where('lang', 'sd')->first()?->countryName;
            $c_enName = $city->province->country->details->where('lang', 'en')->first()?->countryName;
            $c_anyName = $city->province->country->details->first()?->countryName;
            $city->province->country->name = $c_sdName
                ?? $c_enName
                ?? $c_anyName
                ?? $city->province->country->Abbreviation
                ?? "Country #{$city->province->country->id}";
        }
    }
}
