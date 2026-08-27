<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RepairMediaPublicPaths extends Command
{
    protected $signature = 'media:repair-public-paths {--dry-run : Show planned changes without writing to DB/filesystem}';

    protected $description = 'Normalize legacy media paths to media/YYYY/MM, copy files to public/media, and switch media records to public_root disk.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $stats = [
            'rows_scanned' => 0,
            'rows_updated' => 0,
            'files_checked' => 0,
            'files_copied' => 0,
            'files_missing' => 0,
        ];

        $this->info($dryRun
            ? 'Running media path repair in dry-run mode (no changes will be written)...'
            : 'Running media path repair and file relocation...');

        $this->repairMediaTable($dryRun, $stats);

        // String columns that may contain media paths.
        $this->repairStringColumn('product_images', 'image', $dryRun, $stats);
        $this->repairStringColumn('product_variants', 'image', $dryRun, $stats);
        $this->repairStringColumn('product_attribute_values', 'image', $dryRun, $stats);
        $this->repairStringColumn('products', 'image', $dryRun, $stats);
        $this->repairStringColumn('flash_sales', 'banner_image', $dryRun, $stats);
        $this->repairStringColumn('loyalty_rewards', 'image', $dryRun, $stats);
        $this->repairStringColumn('loyalty_tiers', 'badge_image', $dryRun, $stats);
        $this->repairStringColumn('payment_gateways', 'icon', $dryRun, $stats);

        // Only image type settings should be normalized as file paths.
        $this->repairStringColumn(
            'settings',
            'value',
            $dryRun,
            $stats,
            static fn (Builder $query) => $query->where('type', 'image')
        );

        // JSON arrays that can contain media paths.
        $this->repairJsonArrayColumn('reviews', 'images', $dryRun, $stats);

        $this->newLine();
        $this->info('Media path repair summary:');
        $this->line('- Rows scanned: '.$stats['rows_scanned']);
        $this->line('- Rows updated: '.$stats['rows_updated']);
        $this->line('- Media files checked: '.$stats['files_checked']);
        $this->line('- Media files copied to public/media: '.$stats['files_copied']);
        $this->line('- Media files missing from old location: '.$stats['files_missing']);

        if ($dryRun) {
            $this->comment('Dry run completed. Re-run without --dry-run to apply changes.');
        } else {
            $this->info('Done. Existing media links should now resolve under /media/...');
        }

        return self::SUCCESS;
    }

    private function repairMediaTable(bool $dryRun, array &$stats): void
    {
        if (! Schema::hasTable('media')) {
            $this->warn('Skipping media table: table not found.');

            return;
        }

        DB::table('media')
            ->select(['id', 'path', 'disk'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($dryRun, &$stats): void {
                foreach ($rows as $row) {
                    $stats['rows_scanned']++;

                    $oldPath = (string) ($row->path ?? '');
                    $newPath = $this->normalizeMediaPath($oldPath);
                    $newDisk = (string) ($row->disk ?? '');

                    if (str_starts_with($newPath, 'media/')) {
                        $newDisk = 'public_root';
                        $this->ensurePublicMediaFile($newPath, $dryRun, $stats);
                    }

                    if ($newPath === $oldPath && $newDisk === (string) $row->disk) {
                        continue;
                    }

                    if (! $dryRun) {
                        DB::table('media')
                            ->where('id', $row->id)
                            ->update([
                                'path' => $newPath,
                                'disk' => $newDisk,
                                'updated_at' => now(),
                            ]);
                    }

                    $stats['rows_updated']++;
                }
            });
    }

    private function repairStringColumn(
        string $table,
        string $column,
        bool $dryRun,
        array &$stats,
        ?callable $scope = null
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $query = DB::table($table)
            ->select(['id', $column])
            ->orderBy('id');

        if ($scope) {
            $scope($query);
        }

        $query->chunkById(200, function ($rows) use ($table, $column, $dryRun, &$stats): void {
            foreach ($rows as $row) {
                $stats['rows_scanned']++;

                $oldValue = $row->{$column};
                if (! is_string($oldValue) || trim($oldValue) === '') {
                    continue;
                }

                $newValue = $this->normalizeMediaPath($oldValue);
                if ($newValue === $oldValue) {
                    continue;
                }

                if (str_starts_with($newValue, 'media/')) {
                    $this->ensurePublicMediaFile($newValue, $dryRun, $stats);
                }

                if (! $dryRun) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$column => $newValue]);
                }

                $stats['rows_updated']++;
            }
        });
    }

    private function repairJsonArrayColumn(string $table, string $column, bool $dryRun, array &$stats): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $column, $dryRun, &$stats): void {
                foreach ($rows as $row) {
                    $stats['rows_scanned']++;

                    $rawValue = $row->{$column};
                    if (! is_string($rawValue) || trim($rawValue) === '') {
                        continue;
                    }

                    $decoded = json_decode($rawValue, true);
                    if (! is_array($decoded)) {
                        continue;
                    }

                    $changed = false;
                    $normalized = [];

                    foreach ($decoded as $item) {
                        if (! is_string($item) || trim($item) === '') {
                            $normalized[] = $item;

                            continue;
                        }

                        $next = $this->normalizeMediaPath($item);
                        if ($next !== $item) {
                            $changed = true;
                        }

                        if (is_string($next) && str_starts_with($next, 'media/')) {
                            $this->ensurePublicMediaFile($next, $dryRun, $stats);
                        }

                        $normalized[] = $next;
                    }

                    if (! $changed) {
                        continue;
                    }

                    if (! $dryRun) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([$column => json_encode($normalized)]);
                    }

                    $stats['rows_updated']++;
                }
            });
    }

    private function normalizeMediaPath(string $value): string
    {
        $original = trim($value);
        if ($original === '') {
            return $value;
        }

        $path = $original;

        if (preg_match('#^https?://#i', $path) === 1) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            if (is_string($urlPath) && $urlPath !== '') {
                $path = $urlPath;
            }
        }

        $path = ltrim($path, '/');

        if ($path === '') {
            return $value;
        }

        if (str_starts_with($path, 'storage/media/')) {
            return 'media/'.ltrim(substr($path, strlen('storage/media/')), '/');
        }

        if (str_starts_with($path, 'media/')) {
            return $path;
        }

        if (preg_match('#(?:^|/)storage/media/(.+)$#', $path, $matches) === 1) {
            return 'media/'.ltrim((string) $matches[1], '/');
        }

        if (preg_match('#(?:^|/)media/(.+)$#', $path, $matches) === 1) {
            return 'media/'.ltrim((string) $matches[1], '/');
        }

        return $value;
    }

    private function ensurePublicMediaFile(string $relativePath, bool $dryRun, array &$stats): void
    {
        if (! str_starts_with($relativePath, 'media/')) {
            return;
        }

        $normalized = ltrim($relativePath, '/');
        $target = public_path($normalized);

        $stats['files_checked']++;

        if (File::exists($target)) {
            return;
        }

        $sourceCandidates = [
            storage_path('app/public/'.$normalized),
            public_path('storage/'.$normalized),
        ];

        $source = null;
        foreach ($sourceCandidates as $candidate) {
            if (File::exists($candidate)) {
                $source = $candidate;
                break;
            }
        }

        if (! $source) {
            $stats['files_missing']++;

            return;
        }

        if ($dryRun) {
            return;
        }

        File::ensureDirectoryExists(dirname($target));

        if (File::copy($source, $target)) {
            $stats['files_copied']++;
        }
    }
}
