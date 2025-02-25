<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

trait Show
{
    private const CHECK_MARK = "\u{2714}"; // ✔

    private const CROSS_MARK = "\u{2718}"; // ✘

    private function show(): void
    {
        $fullStatus = $this->check();

        $headers = ['Package', 'Type', 'Enabled', 'Valid', 'Active', 'Version', 'Path'];
        $rows = array_map(function ($row) {
            $type = match ($row['type']) {
                'local' => '<fg=yellow>local</>',
                'private' => '<fg=red>private</>',
                'public' => '<fg=green>public</>',
                default => $row['type'],
            };

            $version = $this->getInstalledVersion($row['name'], $row['config']);
            $path = $this->getShortPath($row);

            return [
                $row['name'],
                $type,
                $row['active'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['valid'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['linked'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $version ?: '-',
                $path,
            ];
        }, $fullStatus['packages']);

        table($headers, $rows);

        $badge = '<fg=black;bg=yellow;options=bold> ';
        $updateBadge = '<fg=black;bg=yellow;options=bold> ';

        if ($fullStatus['status'] === 'error') {
            $badge = '<fg=black;bg=red;options=bold> ';
        }

        if ($fullStatus['status'] === 'unlinked') {
            $badge = '<fg=black;bg=gray;options=bold> ';
        }

        if ($fullStatus['status'] === 'linked') {
            $badge = '<fg=black;bg=green;options=bold> ';
        }

        if ($fullStatus['status'] === 'deployed') {
            $badge = '<fg=black;bg=green;options=bold> ';
        }

        if ($fullStatus['updated']) {
            $updateBadge = '<fg=black;bg=green;options=bold> ';
        } else {
            $updateBadge = '<fg=black;bg=red;options=bold> ';
        }

        info('  '.$badge.strtoupper($fullStatus['status']).' </> '.$fullStatus['message']);
        info('  '.$updateBadge.' UPDATE </> '.($fullStatus['updated'] ? 'All packages are in sync with composer.json' : 'You need to run `php artisan devlink:link` to update the packages'));
        info(' ');
    }

    private function getInstalledVersion(string $name, array $package): ?string
    {
        $packageName = $this->getPackageName($name, $package);
        if (! $packageName) {
            return null;
        }

        $composerLock = base_path('composer.lock');
        if (! file_exists($composerLock)) {
            return null;
        }

        $lockData = json_decode(file_get_contents($composerLock), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            info("Invalid composer.lock JSON for $name");

            return null;
        }

        foreach ([$lockData['packages'] ?? [], $lockData['packages-dev'] ?? []] as $packages) {
            foreach ($packages as $pkg) {
                if (($pkg['name'] ?? '') === $packageName) {
                    return $pkg['version'] ?? null;
                }
            }
        }

        // If not found in lock file but composer.json exists, assume dev-main
        $path = $package['path'] ?? '';
        if ($path && ! str_contains($path, 'disabled/')) {
            $composerJson = realpath(base_path($path)).'/composer.json';
            if (file_exists($composerJson)) {
                return 'dev-main';
            }
        }

        return null;
    }

    private function getShortPath(array $row): string
    {
        if (($row['type'] ?? '') === 'local') {
            return '-';
        }

        $privateBasePath = config('devlink.private_base_path');
        if (($row['type'] ?? '') === 'private' && $privateBasePath === 'disabled') {
            return '- enable private path in config -';
        }

        $path = $this->packages[$row['name']]['path'] ?? '';
        if (empty($path)) {
            return '-';
        }

        if (str_starts_with($path, '../')) {
            return $path;
        }

        $basePath = base_path();
        if (str_starts_with($path, $basePath)) {
            return substr($path, strlen($basePath) + 1);
        }

        return $path;
    }

    private function getPackageName(string $name, array $package): ?string
    {
        $isLocal = ($package['type'] ?? '') === 'local';
        $path = $isLocal ? "packages/$name" : ($package['path'] ?? '');

        if (! $path || str_contains($path, 'disabled/')) {
            return null;
        }

        if (str_starts_with($path, '../')) {
            $path = realpath(base_path($path));
        }

        $composerJson = "$path/composer.json";
        if (! file_exists($composerJson)) {
            return null;
        }

        $data = json_decode(file_get_contents($composerJson), true);

        return $data['name'] ?? null;
    }
}
