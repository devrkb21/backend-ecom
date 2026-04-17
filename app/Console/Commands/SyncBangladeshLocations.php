<?php

namespace App\Console\Commands;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncBangladeshLocations extends Command
{
    protected $signature = 'locations:sync-bangladesh {--timeout=30 : HTTP timeout in seconds}';

    protected $description = 'Fetch Bangladesh administrative location data from bdapi and store it in local tables';

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');

        if ($timeout < 5) {
            $timeout = 5;
        }

        try {
            $this->info('Fetching Bangladesh location datasets...');

            $divisionsData = $this->fetchData('division', $timeout);
            $districtsData = $this->fetchData('district', $timeout);
            $upazilasData = $this->fetchData('upazilla', $timeout);
            $unionsData = $this->fetchData('union', $timeout);

            DB::transaction(function () use ($divisionsData, $districtsData, $upazilasData, $unionsData): void {
                $now = now();

                $divisionRows = collect($divisionsData)
                    ->map(function (array $row) use ($now): array {
                        return [
                            'external_id' => (int) ($row['id'] ?? 0),
                            'name' => (string) ($row['name'] ?? ''),
                            'bn_name' => $row['bn_name'] ?? null,
                            'url' => $row['url'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['external_id'] > 0 && $row['name'] !== '')
                    ->values();

                BdDivision::upsert(
                    $divisionRows->all(),
                    ['external_id'],
                    ['name', 'bn_name', 'url', 'updated_at']
                );

                $divisionIdMap = BdDivision::query()
                    ->whereIn('external_id', $divisionRows->pluck('external_id')->all())
                    ->pluck('id', 'external_id');

                $districtRows = collect($districtsData)
                    ->map(function (array $row) use ($divisionIdMap, $now): ?array {
                        $externalDivisionId = (int) ($row['division_id'] ?? 0);
                        $divisionId = $divisionIdMap->get($externalDivisionId);

                        if (!$divisionId) {
                            return null;
                        }

                        return [
                            'external_id' => (int) ($row['id'] ?? 0),
                            'division_id' => (int) $divisionId,
                            'name' => (string) ($row['name'] ?? ''),
                            'bn_name' => $row['bn_name'] ?? null,
                            'lat' => isset($row['lat']) && $row['lat'] !== '' ? (float) $row['lat'] : null,
                            'lon' => isset($row['lon']) && $row['lon'] !== '' ? (float) $row['lon'] : null,
                            'url' => $row['url'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->filter(fn (?array $row): bool => is_array($row) && $row['external_id'] > 0 && $row['name'] !== '')
                    ->values();

                BdDistrict::upsert(
                    $districtRows->all(),
                    ['external_id'],
                    ['division_id', 'name', 'bn_name', 'lat', 'lon', 'url', 'updated_at']
                );

                $districtIdMap = BdDistrict::query()
                    ->whereIn('external_id', $districtRows->pluck('external_id')->all())
                    ->pluck('id', 'external_id');

                $upazilaRows = collect($upazilasData)
                    ->map(function (array $row) use ($districtIdMap, $now): ?array {
                        $externalDistrictId = (int) ($row['district_id'] ?? 0);
                        $districtId = $districtIdMap->get($externalDistrictId);

                        if (!$districtId) {
                            return null;
                        }

                        return [
                            'external_id' => (int) ($row['id'] ?? 0),
                            'district_id' => (int) $districtId,
                            'name' => (string) ($row['name'] ?? ''),
                            'bn_name' => $row['bn_name'] ?? null,
                            'url' => $row['url'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->filter(fn (?array $row): bool => is_array($row) && $row['external_id'] > 0 && $row['name'] !== '')
                    ->values();

                BdUpazila::upsert(
                    $upazilaRows->all(),
                    ['external_id'],
                    ['district_id', 'name', 'bn_name', 'url', 'updated_at']
                );

                $upazilaIdMap = BdUpazila::query()
                    ->whereIn('external_id', $upazilaRows->pluck('external_id')->all())
                    ->pluck('id', 'external_id');

                $unionRows = collect($unionsData)
                    ->map(function (array $row) use ($upazilaIdMap, $now): ?array {
                        $externalUpazilaId = (int) ($row['upazilla_id'] ?? 0);
                        $upazilaId = $upazilaIdMap->get($externalUpazilaId);

                        if (!$upazilaId) {
                            return null;
                        }

                        return [
                            'external_id' => (int) ($row['id'] ?? 0),
                            'upazila_id' => (int) $upazilaId,
                            'name' => (string) ($row['name'] ?? ''),
                            'bn_name' => $row['bn_name'] ?? null,
                            'url' => $row['url'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->filter(fn (?array $row): bool => is_array($row) && $row['external_id'] > 0 && $row['name'] !== '')
                    ->values();

                BdUnion::upsert(
                    $unionRows->all(),
                    ['external_id'],
                    ['upazila_id', 'name', 'bn_name', 'url', 'updated_at']
                );
            });

            $this->newLine();
            $this->info('Bangladesh location sync completed successfully.');
            $this->line('Divisions: ' . BdDivision::count());
            $this->line('Districts: ' . BdDistrict::count());
            $this->line('Upazilas: ' . BdUpazila::count());
            $this->line('Unions: ' . BdUnion::count());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Location sync failed: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchData(string $endpoint, int $timeout): array
    {
        $url = "https://bdapi.vercel.app/api/v.1/{$endpoint}";

        $response = Http::timeout($timeout)
            ->retry(3, 300)
            ->get($url)
            ->throw();

        $json = $response->json();

        if (!is_array($json) || !isset($json['data']) || !is_array($json['data'])) {
            throw new \RuntimeException("Invalid response payload from endpoint: {$endpoint}");
        }

        return $json['data'];
    }
}
