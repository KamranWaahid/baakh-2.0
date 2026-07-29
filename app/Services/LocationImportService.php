<?php

namespace App\Services;

use App\Models\Cities;
use App\Models\Countries;
use App\Models\Districts;
use App\Models\Provinces;
use App\Models\Talukas;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LocationImportService
{
    /**
     * @return array{created:int, updated:int, skipped:int, items:list<array>}
     */
    public function importCountries(array $payload): array
    {
        $rows = $payload['countries'] ?? $payload['items'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        DB::transaction(function () use ($rows, &$created, &$updated, &$skipped, &$items) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }

                $names = $this->extractNames($row, ['countryName', 'name', 'country']);
                if (($names['sd'] ?? null) === null && ($names['en'] ?? null) === null) {
                    $skipped++;
                    continue;
                }

                $abbr = $this->firstString($row, ['abbreviation', 'Abbreviation', 'abbr', 'code']);
                $continent = $this->firstString($row, ['continent', 'Continent']);

                $existing = $this->findCountry($names, $abbr);
                if ($existing) {
                    $this->syncCountry($existing, $names, $abbr, $continent, $row);
                    $updated++;
                    $items[] = ['action' => 'updated', 'id' => $existing->id, 'name' => $names['en'] ?? $names['sd']];
                } else {
                    $country = Countries::create([
                        'user_id' => Auth::id(),
                        'Abbreviation' => $abbr,
                        'Continent' => $continent,
                    ]);
                    $this->writeCountryDetails($country, $names, $row);
                    $created++;
                    $items[] = ['action' => 'created', 'id' => $country->id, 'name' => $names['en'] ?? $names['sd']];
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'items');
    }

    /**
     * Provinces for a country (default: Pakistan).
     *
     * @return array{created:int, updated:int, skipped:int, country_id:int, items:list<array>}
     */
    public function importProvinces(array $payload): array
    {
        $country = $this->resolveCountry($payload['country'] ?? ['abbreviation' => 'pk', 'name_en' => 'Pakistan']);
        $rows = $payload['provinces'] ?? $payload['items'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        DB::transaction(function () use ($rows, $country, &$created, &$updated, &$skipped, &$items) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }
                $names = $this->extractNames($row, ['province_name', 'name', 'province']);
                if (($names['sd'] ?? null) === null && ($names['en'] ?? null) === null) {
                    $skipped++;
                    continue;
                }

                $existing = $this->findProvince($country->id, $names);
                if ($existing) {
                    $this->writeProvinceDetails($existing, $names);
                    $updated++;
                    $items[] = ['action' => 'updated', 'id' => $existing->id, 'name' => $names['en'] ?? $names['sd']];
                } else {
                    $province = Provinces::create([
                        'user_id' => Auth::id(),
                        'country_id' => $country->id,
                    ]);
                    $this->writeProvinceDetails($province, $names);
                    $created++;
                    $items[] = ['action' => 'created', 'id' => $province->id, 'name' => $names['en'] ?? $names['sd']];
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'country_id' => $country->id,
            'items' => $items,
        ];
    }

    /**
     * Cities for a province (default: Sindh).
     *
     * @return array{created:int, updated:int, skipped:int, province_id:int, items:list<array>}
     */
    public function importCities(array $payload): array
    {
        $province = $this->resolveProvince($payload['province'] ?? ['name_en' => 'Sindh', 'name_sd' => 'سنڌ']);
        $rows = $payload['cities'] ?? $payload['items'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        DB::transaction(function () use ($rows, $province, &$created, &$updated, &$skipped, &$items) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }
                $names = $this->extractNames($row, ['city_name', 'name', 'city']);
                if (($names['sd'] ?? null) === null && ($names['en'] ?? null) === null) {
                    $skipped++;
                    continue;
                }

                $district = null;
                $taluka = null;
                if (!empty($row['district']) || !empty($row['district_name'])) {
                    $district = $this->upsertDistrict($province->id, $this->extractNames(
                        is_array($row['district'] ?? null) ? $row['district'] : ['name' => $row['district_name'] ?? $row['district']],
                        ['district_name', 'name', 'district']
                    ));
                }
                if ($district && (!empty($row['taluka']) || !empty($row['taluka_name']))) {
                    $taluka = $this->upsertTaluka($district->id, $this->extractNames(
                        is_array($row['taluka'] ?? null) ? $row['taluka'] : ['name' => $row['taluka_name'] ?? $row['taluka']],
                        ['taluka_name', 'name', 'taluka']
                    ));
                }

                $existing = $this->findCity($province->id, $names);
                if ($existing) {
                    $existing->update([
                        'district_id' => $district?->id ?? $existing->district_id,
                        'taluka_id' => $taluka?->id ?? $existing->taluka_id,
                        'geo_lat' => $this->firstString($row, ['geo_lat', 'lat']) ?? $existing->geo_lat,
                        'geo_long' => $this->firstString($row, ['geo_long', 'lng', 'long']) ?? $existing->geo_long,
                    ]);
                    $this->writeCityDetails($existing, $names);
                    $updated++;
                    $items[] = ['action' => 'updated', 'id' => $existing->id, 'name' => $names['en'] ?? $names['sd']];
                } else {
                    $city = Cities::create([
                        'user_id' => Auth::id(),
                        'province_id' => $province->id,
                        'district_id' => $district?->id,
                        'taluka_id' => $taluka?->id,
                        'geo_lat' => $this->firstString($row, ['geo_lat', 'lat']),
                        'geo_long' => $this->firstString($row, ['geo_long', 'lng', 'long']),
                    ]);
                    $this->writeCityDetails($city, $names);
                    $created++;
                    $items[] = ['action' => 'created', 'id' => $city->id, 'name' => $names['en'] ?? $names['sd']];
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'province_id' => $province->id,
            'items' => $items,
        ];
    }

    /**
     * Districts for Sindh (or given province).
     *
     * @return array{created:int, updated:int, skipped:int, province_id:int, items:list<array>}
     */
    public function importDistricts(array $payload): array
    {
        $province = $this->resolveProvince($payload['province'] ?? ['name_en' => 'Sindh', 'name_sd' => 'سنڌ']);
        $rows = $payload['districts'] ?? $payload['items'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        DB::transaction(function () use ($rows, $province, &$created, &$updated, &$skipped, &$items) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }
                $names = $this->extractNames($row, ['district_name', 'name', 'district']);
                if (($names['sd'] ?? null) === null && ($names['en'] ?? null) === null) {
                    $skipped++;
                    continue;
                }

                $before = $this->findDistrict($province->id, $names);
                $district = $this->upsertDistrict($province->id, $names);
                if ($before) {
                    $updated++;
                    $items[] = ['action' => 'updated', 'id' => $district->id, 'name' => $names['en'] ?? $names['sd']];
                } else {
                    $created++;
                    $items[] = ['action' => 'created', 'id' => $district->id, 'name' => $names['en'] ?? $names['sd']];
                }

                // Nested talukas optional
                $talukas = $row['talukas'] ?? [];
                if (is_array($talukas)) {
                    foreach ($talukas as $talukaRow) {
                        if (!is_array($talukaRow)) {
                            continue;
                        }
                        $tNames = $this->extractNames($talukaRow, ['taluka_name', 'name', 'taluka']);
                        if (($tNames['sd'] ?? null) || ($tNames['en'] ?? null)) {
                            $this->upsertTaluka($district->id, $tNames);
                        }
                    }
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'province_id' => $province->id,
            'items' => $items,
        ];
    }

    /**
     * Talukas for a district (or resolve district by name under Sindh).
     *
     * @return array{created:int, updated:int, skipped:int, district_id:?int, items:list<array>}
     */
    public function importTalukas(array $payload): array
    {
        $province = $this->resolveProvince($payload['province'] ?? ['name_en' => 'Sindh', 'name_sd' => 'سنڌ']);
        $districtRef = $payload['district'] ?? null;
        $district = null;
        if (is_array($districtRef)) {
            $dNames = $this->extractNames($districtRef, ['district_name', 'name', 'district']);
            $district = $this->upsertDistrict($province->id, $dNames);
        } elseif (is_string($districtRef) && trim($districtRef) !== '') {
            $district = $this->upsertDistrict($province->id, ['en' => trim($districtRef), 'sd' => null]);
        }

        $rows = $payload['talukas'] ?? $payload['items'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        DB::transaction(function () use ($rows, $province, &$district, &$created, &$updated, &$skipped, &$items) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }

                $rowDistrict = $district;
                if (!$rowDistrict && (!empty($row['district']) || !empty($row['district_name']))) {
                    $rowDistrict = $this->upsertDistrict($province->id, $this->extractNames(
                        is_array($row['district'] ?? null) ? $row['district'] : ['name' => $row['district_name'] ?? $row['district']],
                        ['district_name', 'name', 'district']
                    ));
                }
                if (!$rowDistrict) {
                    $skipped++;
                    continue;
                }

                $names = $this->extractNames($row, ['taluka_name', 'name', 'taluka']);
                if (($names['sd'] ?? null) === null && ($names['en'] ?? null) === null) {
                    $skipped++;
                    continue;
                }

                $before = $this->findTaluka($rowDistrict->id, $names);
                $taluka = $this->upsertTaluka($rowDistrict->id, $names);
                if ($before) {
                    $updated++;
                    $items[] = ['action' => 'updated', 'id' => $taluka->id, 'name' => $names['en'] ?? $names['sd']];
                } else {
                    $created++;
                    $items[] = ['action' => 'created', 'id' => $taluka->id, 'name' => $names['en'] ?? $names['sd']];
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'district_id' => $district?->id,
            'items' => $items,
        ];
    }

    private function resolveCountry(array $ref): Countries
    {
        $abbr = $this->firstString($ref, ['abbreviation', 'Abbreviation', 'abbr', 'code']) ?: 'pk';
        $names = $this->extractNames($ref, ['countryName', 'name', 'country']);
        if (empty($names['en']) && empty($names['sd'])) {
            $names['en'] = $this->firstString($ref, ['name_en']) ?: 'Pakistan';
            $names['sd'] = $this->firstString($ref, ['name_sd']) ?: 'پاڪستان';
        }

        $existing = $this->findCountry($names, $abbr);
        if ($existing) {
            $this->syncCountry($existing, $names, $abbr, $this->firstString($ref, ['continent', 'Continent']) ?: $existing->Continent, $ref);
            return $existing->fresh('details');
        }

        $country = Countries::create([
            'user_id' => Auth::id(),
            'Abbreviation' => $abbr,
            'Continent' => $this->firstString($ref, ['continent', 'Continent']) ?: 'South Asia',
        ]);
        $this->writeCountryDetails($country, $names, $ref);

        return $country->fresh('details');
    }

    private function resolveProvince(array $ref): Provinces
    {
        $country = $this->resolveCountry($ref['country'] ?? ['abbreviation' => 'pk', 'name_en' => 'Pakistan']);
        $names = $this->extractNames($ref, ['province_name', 'name', 'province']);
        if (empty($names['en']) && empty($names['sd'])) {
            $names['en'] = $this->firstString($ref, ['name_en']) ?: 'Sindh';
            $names['sd'] = $this->firstString($ref, ['name_sd']) ?: 'سنڌ';
        }

        $existing = $this->findProvince($country->id, $names);
        if ($existing) {
            $this->writeProvinceDetails($existing, $names);
            return $existing->fresh('details');
        }

        $province = Provinces::create([
            'user_id' => Auth::id(),
            'country_id' => $country->id,
        ]);
        $this->writeProvinceDetails($province, $names);

        return $province->fresh('details');
    }

    private function upsertDistrict(int $provinceId, array $names): Districts
    {
        $existing = $this->findDistrict($provinceId, $names);
        if ($existing) {
            $this->writeDistrictDetails($existing, $names);
            return $existing;
        }

        $district = Districts::create([
            'user_id' => Auth::id(),
            'province_id' => $provinceId,
        ]);
        $this->writeDistrictDetails($district, $names);

        return $district;
    }

    private function upsertTaluka(int $districtId, array $names): Talukas
    {
        $existing = $this->findTaluka($districtId, $names);
        if ($existing) {
            $this->writeTalukaDetails($existing, $names);
            return $existing;
        }

        $taluka = Talukas::create([
            'user_id' => Auth::id(),
            'district_id' => $districtId,
        ]);
        $this->writeTalukaDetails($taluka, $names);

        return $taluka;
    }

    private function findCountry(array $names, ?string $abbr): ?Countries
    {
        if ($abbr) {
            $byAbbr = Countries::query()
                ->whereRaw('LOWER(Abbreviation) = ?', [mb_strtolower($abbr)])
                ->first();
            if ($byAbbr) {
                return $byAbbr;
            }
        }

        return $this->findByDetailNames(Countries::query()->with('details'), 'details', 'countryName', $names);
    }

    private function findProvince(int $countryId, array $names): ?Provinces
    {
        return $this->findByDetailNames(
            Provinces::query()->with('details')->where('country_id', $countryId),
            'details',
            'province_name',
            $names
        );
    }

    private function findCity(int $provinceId, array $names): ?Cities
    {
        return $this->findByDetailNames(
            Cities::query()->with('details')->where('province_id', $provinceId),
            'details',
            'city_name',
            $names
        );
    }

    private function findDistrict(int $provinceId, array $names): ?Districts
    {
        return $this->findByDetailNames(
            Districts::query()->with('details')->where('province_id', $provinceId),
            'details',
            'district_name',
            $names
        );
    }

    private function findTaluka(int $districtId, array $names): ?Talukas
    {
        return $this->findByDetailNames(
            Talukas::query()->with('details')->where('district_id', $districtId),
            'details',
            'taluka_name',
            $names
        );
    }

    private function findByDetailNames($query, string $relation, string $nameColumn, array $names)
    {
        $candidates = array_values(array_filter([
            $names['en'] ?? null,
            $names['sd'] ?? null,
        ]));
        if ($candidates === []) {
            return null;
        }

        $normalized = array_map(fn ($n) => DictionaryText::normalizeForLookup($n), $candidates);

        return $query->whereHas($relation, function ($q) use ($nameColumn, $candidates, $normalized) {
            $q->where(function ($inner) use ($nameColumn, $candidates, $normalized) {
                foreach ($candidates as $i => $candidate) {
                    $inner->orWhere($nameColumn, $candidate)
                        ->orWhereRaw('LOWER(' . $nameColumn . ') = ?', [mb_strtolower($candidate)]);
                    if (!empty($normalized[$i])) {
                        $inner->orWhereRaw(
                            "LOWER(REPLACE(REPLACE({$nameColumn}, 'ِ', ''), 'َ', '')) LIKE ?",
                            ['%' . mb_strtolower($normalized[$i]) . '%']
                        );
                    }
                }
            });
        })->first();
    }

    private function syncCountry(Countries $country, array $names, ?string $abbr, ?string $continent, array $row): void
    {
        $country->update([
            'Abbreviation' => $abbr ?: $country->Abbreviation,
            'Continent' => $continent ?: $country->Continent,
        ]);
        $this->writeCountryDetails($country, $names, $row);
    }

    private function writeCountryDetails(Countries $country, array $names, array $row): void
    {
        $descs = [
            'en' => $this->nestedString($row, ['descriptions', 'en']) ?? $this->nestedString($row, ['details', 'en', 'countryDesc']),
            'sd' => $this->nestedString($row, ['descriptions', 'sd']) ?? $this->nestedString($row, ['details', 'sd', 'countryDesc']),
        ];

        foreach (['en', 'sd'] as $lang) {
            if (empty($names[$lang])) {
                continue;
            }
            $country->details()->updateOrCreate(
                ['lang' => $lang],
                [
                    'countryName' => strip_tags($names[$lang]),
                    'countryDesc' => $descs[$lang] ? strip_tags($descs[$lang]) : null,
                ]
            );
        }
    }

    private function writeProvinceDetails(Provinces $province, array $names): void
    {
        foreach (['en', 'sd'] as $lang) {
            if (empty($names[$lang])) {
                continue;
            }
            $province->details()->updateOrCreate(
                ['lang' => $lang],
                ['province_name' => strip_tags($names[$lang])]
            );
        }
    }

    private function writeCityDetails(Cities $city, array $names): void
    {
        foreach (['en', 'sd'] as $lang) {
            if (empty($names[$lang])) {
                continue;
            }
            $city->details()->updateOrCreate(
                ['lang' => $lang],
                ['city_name' => strip_tags($names[$lang])]
            );
        }
    }

    private function writeDistrictDetails(Districts $district, array $names): void
    {
        foreach (['en', 'sd'] as $lang) {
            if (empty($names[$lang])) {
                continue;
            }
            $district->details()->updateOrCreate(
                ['lang' => $lang],
                ['district_name' => strip_tags($names[$lang])]
            );
        }
    }

    private function writeTalukaDetails(Talukas $taluka, array $names): void
    {
        foreach (['en', 'sd'] as $lang) {
            if (empty($names[$lang])) {
                continue;
            }
            $taluka->details()->updateOrCreate(
                ['lang' => $lang],
                ['taluka_name' => strip_tags($names[$lang])]
            );
        }
    }

    /**
     * @param  list<string>  $keys
     * @return array{en:?string, sd:?string}
     */
    private function extractNames(array $row, array $keys): array
    {
        $en = $this->nestedString($row, ['names', 'en'])
            ?? $this->nestedString($row, ['details', 'en', $keys[0]])
            ?? $this->firstString($row, array_map(fn ($k) => $k . '_en', $keys))
            ?? $this->firstString($row, ['name_en', 'en']);

        $sd = $this->nestedString($row, ['names', 'sd'])
            ?? $this->nestedString($row, ['details', 'sd', $keys[0]])
            ?? $this->firstString($row, array_map(fn ($k) => $k . '_sd', $keys))
            ?? $this->firstString($row, ['name_sd', 'sd']);

        // Flat "name" often English; if Arabic script, treat as sd.
        $flat = $this->firstString($row, $keys);
        if ($flat) {
            if ($this->looksArabic($flat)) {
                $sd = $sd ?: $flat;
            } else {
                $en = $en ?: $flat;
            }
        }

        // If only one language provided, mirror to the other for required sd forms.
        if (!$sd && $en) {
            $sd = $en;
        }
        if (!$en && $sd) {
            $en = $sd;
        }

        return [
            'en' => $en ? trim($en) : null,
            'sd' => $sd ? trim($sd) : null,
        ];
    }

    private function firstString(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $value = $row[$key];
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function nestedString(array $row, array $path): ?string
    {
        $cursor = $row;
        foreach ($path as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return is_string($cursor) && trim($cursor) !== '' ? trim($cursor) : null;
    }

    private function looksArabic(string $value): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $value);
    }
}
