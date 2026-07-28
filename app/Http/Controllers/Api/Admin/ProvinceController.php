<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Provinces;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvinceController extends Controller
{
    /** Common EN → SD province name hints for legacy split rows. */
    private const NAME_ALIASES = [
        'sindh' => ['سنڌ', 'sindh'],
        'punjab' => ['پنجاب', 'punjab'],
        'balochistan' => ['بلوچستان', 'balochistan', 'baluchistan'],
        'khyber pakhtunkhwa' => ['خيبر پختونخوا', 'khyber pakhtunkhwa', 'kpk'],
        'islamabad' => ['اسلام آباد', 'islamabad'],
    ];

    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index(Request $request)
    {
        $query = Provinces::with(['country.details', 'details'])->latest();

        $filterCountryId = $request->filled('country_id') ? (int) $request->country_id : null;

        $provinces = $this->groupProvincesForList($query->get());

        if ($filterCountryId) {
            $countryIds = $this->expandCountryFilterIds($filterCountryId);
            $provinces = $provinces->filter(function (array $province) use ($countryIds) {
                $ids = $province['country_ids'] ?? [($province['country_id'] ?? null)];
                return count(array_intersect(array_map('intval', $ids), $countryIds)) > 0;
            })->values();
        }

        return response()->json($provinces);
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

        $province = DB::transaction(function () use ($request, $validatedData) {
            $province = Provinces::create([
                'user_id' => $request->user()->id,
                'country_id' => $validatedData['country_id'],
            ]);

            foreach ($validatedData['details'] as $lang => $detail) {
                if (!empty($detail['province_name'])) {
                    $province->details()->create([
                        'province_name' => strip_tags($detail['province_name']),
                        'lang' => $lang
                    ]);
                }
            }
            return $province;
        });

        ActivityLog::log('created_province', $request->user(), null, "Created province: " . ($validatedData['details']['sd']['province_name']));

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
            'partner_ids' => 'nullable|array',
            'partner_ids.*' => 'integer|exists:location_provinces,id',
        ]);

        DB::transaction(function () use ($province, $validatedData) {
            $province->update([
                'country_id' => $validatedData['country_id'],
            ]);

            foreach ($validatedData['details'] as $lang => $detail) {
                if (!empty($detail['province_name'])) {
                    $province->details()->updateOrCreate(
                        ['lang' => $lang],
                        [
                            'province_name' => strip_tags($detail['province_name'])
                        ]
                    );
                } else {
                    $province->details()->where('lang', $lang)->delete();
                }
            }

            foreach ($validatedData['partner_ids'] ?? [] as $partnerId) {
                if ((int) $partnerId === (int) $province->id) {
                    continue;
                }
                $partner = Provinces::with('details')->find($partnerId);
                if (!$partner) {
                    continue;
                }
                foreach ($partner->details as $detail) {
                    if ($province->details()->where('lang', $detail->lang)->exists()) {
                        continue;
                    }
                    $province->details()->create([
                        'province_name' => $detail->province_name,
                        'lang' => $detail->lang,
                    ]);
                }
                $partner->details()->delete();
                $partner->delete();
            }
        });

        ActivityLog::log('updated_province', $request->user(), null, "Updated province: " . ($validatedData['details']['sd']['province_name']));

        return response()->json([
            'message' => 'Province updated successfully',
            'province' => $province->fresh()->load(['details', 'country'])
        ]);
    }

    public function destroy($id)
    {
        $province = Provinces::with('details')->findOrFail($id);
        $sdName = $province->details->where('lang', 'sd')->first()?->province_name ?? 'Unnamed';
        $province->delete();

        ActivityLog::log('deleted_province', request()->user(), null, "Deleted province: {$sdName}");

        return response()->json(['message' => 'Province deleted successfully']);
    }

    private function expandCountryFilterIds(int $countryId): array
    {
        $country = Countries::find($countryId);
        if (!$country) {
            return [$countryId];
        }

        $abbr = strtolower(trim((string) ($country->Abbreviation ?? '')));
        if ($abbr === '') {
            return [$countryId];
        }

        return Countries::query()
            ->whereRaw('LOWER(TRIM(Abbreviation)) = ?', [$abbr])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function groupProvincesForList(Collection $provinces): Collection
    {
        $used = [];
        $grouped = collect();

        foreach ($provinces as $province) {
            if (isset($used[$province->id])) {
                continue;
            }

            $langs = $province->details->pluck('lang')->unique()->filter()->values();
            if ($langs->count() >= 2) {
                $grouped->push($this->serializeProvince($province));
                $used[$province->id] = true;
                continue;
            }

            $partners = $this->findPartners($province, $provinces, $used);
            if ($partners->isNotEmpty()) {
                $all = collect([$province])->concat($partners);
                $primary = $all->first(fn (Provinces $p) => $p->details->contains(fn ($d) => $d->lang === 'sd'))
                    ?? $all->first(fn (Provinces $p) => $p->details->contains(fn ($d) => $d->lang === 'en'))
                    ?? $province;
                $others = $all->where('id', '!=', $primary->id)->values();
                $extra = $others->flatMap(fn (Provinces $p) => $p->details)->all();
                $partnerIds = $others->pluck('id')->map(fn ($id) => (int) $id)->all();

                $grouped->push($this->serializeProvince($primary, $extra, $partnerIds, $others));
                foreach ($all as $p) {
                    $used[$p->id] = true;
                }
            } else {
                $grouped->push($this->serializeProvince($province));
                $used[$province->id] = true;
            }
        }

        return $grouped->values();
    }

    private function findPartners(Provinces $province, Collection $all, array $used): Collection
    {
        $lang = $province->details->first()?->lang;
        if (!$lang) {
            return collect();
        }

        $family = $this->countryFamilyKey($province);
        $candidates = $all->filter(function (Provinces $other) use ($province, $used, $lang, $family) {
            if ($other->id === $province->id || isset($used[$other->id])) {
                return false;
            }
            if ($this->countryFamilyKey($other) !== $family) {
                return false;
            }
            $otherLangs = $other->details->pluck('lang')->unique()->filter();
            if ($otherLangs->count() !== 1) {
                return false;
            }
            // Prefer complementary language, but also allow same-name RO/EN merges.
            return true;
        });

        $matched = collect();

        // 1) Closest complementary-lang pair within 10 minutes.
        $best = null;
        $bestDiff = PHP_INT_MAX;
        foreach ($candidates as $other) {
            $otherLang = $other->details->first()?->lang;
            if ($otherLang === $lang) {
                continue;
            }
            $diff = abs(strtotime((string) $province->created_at) - strtotime((string) $other->created_at));
            if ($diff <= 600 && $diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $other;
            }
        }
        if ($best) {
            $matched->push($best);
        }

        // 2) Exact roman/english name matches (e.g. Sindh EN + Sindh RO).
        $myNames = $province->details->pluck('province_name')->filter()->map(fn ($n) => $this->normalizeName($n));
        foreach ($candidates as $other) {
            if ($matched->contains(fn ($p) => $p->id === $other->id)) {
                continue;
            }
            $otherNames = $other->details->pluck('province_name')->filter()->map(fn ($n) => $this->normalizeName($n));
            if ($myNames->intersect($otherNames)->isNotEmpty()) {
                $matched->push($other);
            }
        }

        // 3) Alias map for known EN↔SD pairs still unpaired.
        foreach ($candidates as $other) {
            if ($matched->contains(fn ($p) => $p->id === $other->id)) {
                continue;
            }
            $otherLang = $other->details->first()?->lang;
            if ($otherLang === $lang) {
                continue;
            }
            if ($this->namesAreAliases($province, $other)) {
                $matched->push($other);
            }
        }

        return $matched->unique('id')->values();
    }

    private function countryFamilyKey(Provinces $province): string
    {
        $abbr = strtolower(trim((string) ($province->country->Abbreviation ?? '')));
        if ($abbr !== '') {
            return 'abbr:' . $abbr;
        }
        return 'country:' . (int) $province->country_id;
    }

    private function normalizeName(?string $name): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', (string) $name) ?? ''));
    }

    private function namesAreAliases(Provinces $a, Provinces $b): bool
    {
        $aNames = $a->details->pluck('province_name')->filter()->map(fn ($n) => $this->normalizeName($n))->all();
        $bNames = $b->details->pluck('province_name')->filter()->map(fn ($n) => $this->normalizeName($n))->all();

        foreach (self::NAME_ALIASES as $aliases) {
            $normalizedAliases = array_map(fn ($n) => $this->normalizeName($n), $aliases);
            $aHit = count(array_intersect($aNames, $normalizedAliases)) > 0;
            $bHit = count(array_intersect($bNames, $normalizedAliases)) > 0;
            if ($aHit && $bHit) {
                return true;
            }
        }

        return false;
    }

    private function serializeProvince(
        Provinces $province,
        array $extraDetails = [],
        array $partnerIds = [],
        ?Collection $partnerProvinces = null
    ): array {
        $this->hydrateCountryName($province);

        $detailsArr = collect($province->details)
            ->concat($extraDetails)
            ->map(function ($detail) {
                if (is_array($detail)) {
                    return $detail;
                }
                return [
                    'id' => $detail->id ?? null,
                    'province_id' => $detail->province_id ?? null,
                    'province_name' => $detail->province_name,
                    'lang' => $detail->lang,
                ];
            })
            ->unique(fn ($d) => strtolower((string) ($d['lang'] ?? '')) . ':' . (string) ($d['province_name'] ?? ''))
            ->values()
            ->all();

        $names = [];
        foreach ($detailsArr as $detail) {
            $lang = strtolower((string) ($detail['lang'] ?? ''));
            if ($lang !== '' && !empty($detail['province_name'])) {
                $names[$lang] = $detail['province_name'];
            }
        }

        $sdName = $names['sd'] ?? null;
        $enName = $names['en'] ?? null;
        $anyName = collect($detailsArr)->first()['province_name'] ?? null;

        $countryIds = [(int) $province->country_id];
        if ($partnerProvinces) {
            foreach ($partnerProvinces as $partner) {
                $countryIds[] = (int) $partner->country_id;
                $this->hydrateCountryName($partner);
            }
        }

        $countryName = $province->country?->name;
        if ($partnerProvinces) {
            $countryNames = collect([$countryName])
                ->concat($partnerProvinces->map(fn (Provinces $p) => $p->country?->name))
                ->filter()
                ->unique()
                ->values();
            if ($countryNames->count() > 1) {
                $countryName = $countryNames->implode(' / ');
            } elseif (!$countryName) {
                $countryName = $countryNames->first();
            }
        }

        $countryPayload = $province->country ? [
            'id' => $province->country->id,
            'Abbreviation' => $province->country->Abbreviation,
            'Continent' => $province->country->Continent,
            'name' => $countryName ?: ($province->country->Abbreviation ?? "Country #{$province->country->id}"),
        ] : null;

        return [
            'id' => $province->id,
            'country_id' => $province->country_id,
            'country_ids' => array_values(array_unique(array_filter($countryIds))),
            'partner_ids' => array_values($partnerIds),
            'name' => $sdName ?? $enName ?? $anyName ?? "Province #{$province->id}",
            'names' => $names,
            'details' => $detailsArr,
            'country' => $countryPayload,
            'created_at' => $province->created_at,
            'updated_at' => $province->updated_at,
        ];
    }

    private function hydrateCountryName(Provinces $province): void
    {
        if (!$province->country) {
            return;
        }
        $c_sdName = $province->country->details->where('lang', 'sd')->first()?->countryName;
        $c_enName = $province->country->details->where('lang', 'en')->first()?->countryName;
        $province->country->name = $c_sdName
            ?? $c_enName
            ?? $province->country->Abbreviation
            ?? "Country #{$province->country->id}";
    }
}
