<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\LocationImportService;
use Illuminate\Http\Request;

class LocationImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function importCountries(Request $request, LocationImportService $import)
    {
        $payload = $this->payload($request);
        $result = $import->importCountries($payload);

        return response()->json([
            'message' => sprintf(
                'Countries import done: %d created, %d updated, %d skipped.',
                $result['created'],
                $result['updated'],
                $result['skipped']
            ),
            ...$result,
        ]);
    }

    public function importProvinces(Request $request, LocationImportService $import)
    {
        $payload = $this->payload($request);
        $result = $import->importProvinces($payload);

        return response()->json([
            'message' => sprintf(
                'Provinces import done: %d created, %d updated, %d skipped.',
                $result['created'],
                $result['updated'],
                $result['skipped']
            ),
            ...$result,
        ]);
    }

    public function importCities(Request $request, LocationImportService $import)
    {
        $payload = $this->payload($request);
        $result = $import->importCities($payload);

        return response()->json([
            'message' => sprintf(
                'Cities import done: %d created, %d updated, %d skipped.',
                $result['created'],
                $result['updated'],
                $result['skipped']
            ),
            ...$result,
        ]);
    }

    public function importDistricts(Request $request, LocationImportService $import)
    {
        $payload = $this->payload($request);
        $result = $import->importDistricts($payload);

        return response()->json([
            'message' => sprintf(
                'Districts import done: %d created, %d updated, %d skipped.',
                $result['created'],
                $result['updated'],
                $result['skipped']
            ),
            ...$result,
        ]);
    }

    public function importTalukas(Request $request, LocationImportService $import)
    {
        $payload = $this->payload($request);
        $result = $import->importTalukas($payload);

        return response()->json([
            'message' => sprintf(
                'Talukas import done: %d created, %d updated, %d skipped.',
                $result['created'],
                $result['updated'],
                $result['skipped']
            ),
            ...$result,
        ]);
    }

    private function payload(Request $request): array
    {
        $data = $request->all();
        // Allow raw JSON body that is already the object.
        if (isset($data['countries']) || isset($data['provinces']) || isset($data['cities'])
            || isset($data['districts']) || isset($data['talukas']) || isset($data['items'])) {
            return $data;
        }

        if (isset($data['payload']) && is_array($data['payload'])) {
            return $data['payload'];
        }

        return $data;
    }
}
