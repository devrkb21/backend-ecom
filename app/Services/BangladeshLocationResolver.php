<?php

namespace App\Services;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use Illuminate\Support\Collection;

class BangladeshLocationResolver
{
    public function resolve(
        ?string $locationText,
        ?int $divisionId = null,
        ?int $districtId = null,
        ?int $upazilaId = null,
        ?int $unionId = null
    ): array {
        $locationText = trim((string) $locationText);
        $segments = $this->extractSegments($locationText);
        $tokens = $this->extractTokens($locationText);

        $resolvedDivisionId = $divisionId ?: null;
        $resolvedDistrictId = $districtId ?: null;
        $resolvedUpazilaId = $upazilaId ?: null;
        $resolvedUnionId = $unionId ?: null;

        $scores = [];

        $divisions = BdDivision::query()->get(['id', 'name', 'bn_name']);
        $districts = BdDistrict::query()->get(['id', 'division_id', 'name', 'bn_name']);
        $upazilas = BdUpazila::query()->get(['id', 'district_id', 'name', 'bn_name']);
        $unions = BdUnion::query()->get(['id', 'upazila_id', 'name', 'bn_name']);

        $districtMap = $districts->keyBy('id');
        $upazilaMap = $upazilas->keyBy('id');
        $divisionMap = $divisions->keyBy('id');

        if ($resolvedDivisionId === null) {
            $divisionMatch = $this->findBestMatch(
                $divisions,
                $segments,
                $tokens,
                fn (BdDivision $division) => [$division->name, $division->bn_name]
            );

            if ($divisionMatch) {
                $resolvedDivisionId = (int) $divisionMatch['item']->id;
                $scores['division'] = $divisionMatch['score'];
            }
        }

        if ($resolvedDistrictId === null) {
            $districtPool = $resolvedDivisionId
                ? $districts->where('division_id', $resolvedDivisionId)->values()
                : $districts;

            $districtMatch = $this->findBestMatch(
                $districtPool,
                $segments,
                $tokens,
                fn (BdDistrict $district) => [$district->name, $district->bn_name]
            );

            if ($districtMatch) {
                $resolvedDistrictId = (int) $districtMatch['item']->id;
                $scores['district'] = $districtMatch['score'];
            }
        }

        if ($resolvedUpazilaId === null) {
            $upazilaPool = $upazilas;

            if ($resolvedDistrictId) {
                $upazilaPool = $upazilaPool->where('district_id', $resolvedDistrictId)->values();
            } elseif ($resolvedDivisionId) {
                $districtIdsInDivision = $districts
                    ->where('division_id', $resolvedDivisionId)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $upazilaPool = $upazilaPool
                    ->filter(fn (BdUpazila $upazila) => in_array((int) $upazila->district_id, $districtIdsInDivision, true))
                    ->values();
            }

            $upazilaMatch = $this->findBestMatch(
                $upazilaPool,
                $segments,
                $tokens,
                fn (BdUpazila $upazila) => [$upazila->name, $upazila->bn_name]
            );

            if ($upazilaMatch) {
                $resolvedUpazilaId = (int) $upazilaMatch['item']->id;
                $scores['upazila'] = $upazilaMatch['score'];
            }
        }

        if ($resolvedUnionId === null) {
            $unionPool = $unions;

            if ($resolvedUpazilaId) {
                $unionPool = $unionPool->where('upazila_id', $resolvedUpazilaId)->values();
            } elseif ($resolvedDistrictId) {
                $upazilaIdsInDistrict = $upazilas
                    ->where('district_id', $resolvedDistrictId)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $unionPool = $unionPool
                    ->filter(fn (BdUnion $union) => in_array((int) $union->upazila_id, $upazilaIdsInDistrict, true))
                    ->values();
            }

            $unionMatch = $this->findBestMatch(
                $unionPool,
                $segments,
                $tokens,
                fn (BdUnion $union) => [$union->name, $union->bn_name]
            );

            if ($unionMatch) {
                $resolvedUnionId = (int) $unionMatch['item']->id;
                $scores['union'] = $unionMatch['score'];
            }
        }

        if ($resolvedUpazilaId && $resolvedDistrictId === null) {
            $resolvedDistrictId = (int) ($upazilaMap->get($resolvedUpazilaId)?->district_id ?? 0) ?: null;
        }

        if ($resolvedDistrictId && $resolvedDivisionId === null) {
            $resolvedDivisionId = (int) ($districtMap->get($resolvedDistrictId)?->division_id ?? 0) ?: null;
        }

        if ($resolvedUnionId && $resolvedUpazilaId === null) {
            $resolvedUpazilaId = (int) ($unions->keyBy('id')->get($resolvedUnionId)?->upazila_id ?? 0) ?: null;

            if ($resolvedUpazilaId && $resolvedDistrictId === null) {
                $resolvedDistrictId = (int) ($upazilaMap->get($resolvedUpazilaId)?->district_id ?? 0) ?: null;
            }

            if ($resolvedDistrictId && $resolvedDivisionId === null) {
                $resolvedDivisionId = (int) ($districtMap->get($resolvedDistrictId)?->division_id ?? 0) ?: null;
            }
        }

        $division = $resolvedDivisionId ? $divisionMap->get($resolvedDivisionId) : null;
        $district = $resolvedDistrictId ? $districtMap->get($resolvedDistrictId) : null;
        $upazila = $resolvedUpazilaId ? $upazilaMap->get($resolvedUpazilaId) : null;
        $union = $resolvedUnionId ? $unions->keyBy('id')->get($resolvedUnionId) : null;

        $matchedLevels = [];
        if ($resolvedDivisionId) {
            $matchedLevels[] = 'division';
        }
        if ($resolvedDistrictId) {
            $matchedLevels[] = 'district';
        }
        if ($resolvedUpazilaId) {
            $matchedLevels[] = 'upazila';
        }
        if ($resolvedUnionId) {
            $matchedLevels[] = 'union';
        }

        return [
            'matched' => !empty($matchedLevels),
            'location_text' => $locationText !== '' ? $locationText : null,
            'division_id' => $resolvedDivisionId,
            'district_id' => $resolvedDistrictId,
            'upazila_id' => $resolvedUpazilaId,
            'union_id' => $resolvedUnionId,
            'division_name' => $division?->name,
            'district_name' => $district?->name,
            'upazila_name' => $upazila?->name,
            'union_name' => $union?->name,
            'matched_levels' => $matchedLevels,
            'confidence' => empty($scores) ? 0 : (int) max($scores),
        ];
    }

    protected function findBestMatch(Collection $items, array $segments, array $tokens, callable $nameResolver): ?array
    {
        if ($items->isEmpty()) {
            return null;
        }

        $best = null;

        foreach ($items as $item) {
            $score = $this->scoreNameMatch((array) $nameResolver($item), $segments, $tokens);

            if ($score <= 0) {
                continue;
            }

            if (!$best || $score > $best['score']) {
                $best = ['item' => $item, 'score' => $score];
            }
        }

        if (!$best || $best['score'] < 60) {
            return null;
        }

        return $best;
    }

    protected function scoreNameMatch(array $names, array $segments, array $tokens): int
    {
        $maxScore = 0;

        foreach ($names as $name) {
            $normalizedName = $this->normalizeText((string) $name);
            if ($normalizedName === '') {
                continue;
            }

            if (in_array($normalizedName, $segments, true)) {
                $maxScore = max($maxScore, 120);
                continue;
            }

            if (in_array($normalizedName, $tokens, true)) {
                $maxScore = max($maxScore, 100);
                continue;
            }

            $nameTokens = array_values(array_filter(explode(' ', $normalizedName)));
            if (!empty($nameTokens) && count(array_intersect($nameTokens, $tokens)) === count($nameTokens)) {
                $maxScore = max($maxScore, 85);
                continue;
            }

            if (mb_strlen($normalizedName) >= 4) {
                foreach ($segments as $segment) {
                    if (str_contains($segment, $normalizedName) || str_contains($normalizedName, $segment)) {
                        $maxScore = max($maxScore, 70);
                        break;
                    }
                }
            }
        }

        return $maxScore;
    }

    protected function extractSegments(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $segments = preg_split('/[,;|\\/\\n\\r]+/u', $text) ?: [];

        return collect($segments)
            ->map(fn ($segment) => $this->normalizeText($segment))
            ->filter(fn ($segment) => $segment !== '')
            ->values()
            ->all();
    }

    protected function extractTokens(string $text): array
    {
        $normalized = $this->normalizeText($text);
        if ($normalized === '') {
            return [];
        }

        return collect(explode(' ', $normalized))
            ->filter(fn ($token) => mb_strlen($token) >= 2)
            ->values()
            ->all();
    }

    protected function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }
}
