<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class AssetExportService
{
    public function __construct(
        protected DatabaseExportService $databaseExportService
    ) {}

    /**
     * Get statistics on assets stored on disk.
     *
     * @return array<string, mixed>
     */
    public function getAssetStats(): array
    {
        $categories = [
            [
                'key' => 'avatars',
                'name' => 'User & Brand Avatars',
                'paths' => [storage_path('app/public/avatars'), storage_path('app/public/brand-avatars')],
                'icon' => 'user',
            ],
            [
                'key' => 'blog',
                'name' => 'Blog Media & Images',
                'paths' => [storage_path('app/public/blog')],
                'icon' => 'image',
            ],
            [
                'key' => 'templates',
                'name' => 'Template Previews',
                'paths' => [storage_path('app/public/templates')],
                'icon' => 'layout',
            ],
            [
                'key' => 'fonts',
                'name' => 'Custom PDF Fonts',
                'paths' => [storage_path('app/fonts'), storage_path('fonts')],
                'icon' => 'type',
            ],
            [
                'key' => 'other_public',
                'name' => 'Other Public Storage',
                'paths' => [storage_path('app/public')],
                'icon' => 'folder',
            ],
        ];

        $categoryResults = [];
        $seenFiles = [];
        $totalBytes = 0;
        $totalFiles = 0;

        foreach ($categories as $cat) {
            $catFiles = 0;
            $catBytes = 0;

            foreach ($cat['paths'] as $path) {
                if (! File::isDirectory($path)) {
                    continue;
                }

                $finder = new Finder;
                $finder->files()->in($path)->ignoreDotFiles(true);

                foreach ($finder as $file) {
                    $real = $file->getRealPath();
                    // Avoid counting files in subcategories twice if path is parent
                    if (isset($seenFiles[$real])) {
                        continue;
                    }
                    if ($cat['key'] === 'other_public') {
                        // Skip subdirectories already counted in avatars, blog, templates
                        $relative = $file->getRelativePath();
                        if (str_starts_with($relative, 'avatars') || str_starts_with($relative, 'brand-avatars')
                            || str_starts_with($relative, 'blog') || str_starts_with($relative, 'templates')) {
                            continue;
                        }
                    }

                    $seenFiles[$real] = true;
                    $size = $file->getSize();
                    $catFiles++;
                    $catBytes += $size;
                    $totalBytes += $size;
                    $totalFiles++;
                }
            }

            $categoryResults[] = [
                'key' => $cat['key'],
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'files_count' => $catFiles,
                'size_bytes' => $catBytes,
                'size_formatted' => $this->databaseExportService->formatBytes($catBytes),
            ];
        }

        return [
            'total_files' => $totalFiles,
            'total_bytes' => $totalBytes,
            'total_formatted' => $this->databaseExportService->formatBytes($totalBytes),
            'categories' => $categoryResults,
        ];
    }

    /**
     * Export assets to a ZIP archive.
     *
     * @param  array<string>  $scopes
     * @return array{path: string, filename: string, size_bytes: int, size_formatted: string, mime: string, files_count: int}
     */
    public function exportAssets(array $scopes = ['all']): array
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupsDir = storage_path('app/backups');
        if (! File::isDirectory($backupsDir)) {
            File::makeDirectory($backupsDir, 0755, true);
        }

        $filename = "hresume_assets_{$timestamp}.zip";
        $zipPath = $backupsDir.'/'.$filename;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create zip file at: {$zipPath}");
        }

        $directoriesToInclude = $this->resolveDirectoriesForScopes($scopes);
        $addedFilesCount = 0;
        $manifestFiles = [];

        foreach ($directoriesToInclude as $sourceDir => $zipPrefix) {
            if (! File::isDirectory($sourceDir)) {
                continue;
            }

            $finder = new Finder;
            $finder->files()->in($sourceDir)->ignoreDotFiles(true);

            foreach ($finder as $file) {
                $real = $file->getRealPath();
                // Never package existing backups or git files
                if (str_contains($real, '/app/backups/') || str_contains($real, '/.git/')) {
                    continue;
                }

                $relativeInsideDir = $file->getRelativePathname();
                $archivePath = trim($zipPrefix.'/'.$relativeInsideDir, '/');

                $zip->addFile($real, $archivePath);
                $addedFilesCount++;
                $manifestFiles[] = $archivePath;
            }
        }

        // Add manifest JSON inside zip
        $manifest = [
            'app' => config('app.name', 'HResume'),
            'type' => 'assets_backup',
            'exported_at' => Carbon::now()->toIso8601String(),
            'scopes' => $scopes,
            'total_files' => $addedFilesCount,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip->close();

        $size = File::exists($zipPath) ? File::size($zipPath) : 0;

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'size_bytes' => $size,
            'size_formatted' => $this->databaseExportService->formatBytes($size),
            'mime' => 'application/zip',
            'files_count' => $addedFilesCount,
        ];
    }

    /**
     * Create a complete system backup: Database SQL dump + Raw SQLite + Storage Assets in a single ZIP.
     *
     * @return array{path: string, filename: string, size_bytes: int, size_formatted: string, mime: string, files_count: int}
     */
    public function exportFullBackup(): array
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupsDir = storage_path('app/backups');
        if (! File::isDirectory($backupsDir)) {
            File::makeDirectory($backupsDir, 0755, true);
        }

        $filename = "hresume_full_backup_{$timestamp}.zip";
        $zipPath = $backupsDir.'/'.$filename;

        // 1. Generate SQL dump in a temporary file
        $tempSqlFile = $backupsDir."/temp_db_{$timestamp}.sql";
        $allTables = $this->databaseExportService->getAllTables();
        $this->databaseExportService->dumpToSql($allTables, $tempSqlFile);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::delete($tempSqlFile);
            throw new \RuntimeException("Unable to create zip file at: {$zipPath}");
        }

        // Add database SQL dump
        $zip->addFile($tempSqlFile, 'database/database_dump.sql');

        // If SQLite, also add raw sqlite file if present
        $dbDriver = config('database.default', 'sqlite');
        if ($dbDriver === 'sqlite') {
            $sqlitePath = config('database.connections.sqlite.database');
            if (File::exists($sqlitePath)) {
                $zip->addFile($sqlitePath, 'database/database.sqlite');
            }
        }

        // 2. Add storage assets
        $directoriesToInclude = $this->resolveDirectoriesForScopes(['all']);
        $addedFilesCount = 0;

        foreach ($directoriesToInclude as $sourceDir => $zipPrefix) {
            if (! File::isDirectory($sourceDir)) {
                continue;
            }

            $finder = new Finder;
            $finder->files()->in($sourceDir)->ignoreDotFiles(true);

            foreach ($finder as $file) {
                $real = $file->getRealPath();
                if (str_contains($real, '/app/backups/') || str_contains($real, '/.git/') || str_contains($real, 'temp_db_')) {
                    continue;
                }

                $relativeInsideDir = $file->getRelativePathname();
                $archivePath = 'assets/'.trim($zipPrefix.'/'.$relativeInsideDir, '/');

                $zip->addFile($real, $archivePath);
                $addedFilesCount++;
            }
        }

        // 3. Add full backup manifest
        $manifest = [
            'app' => config('app.name', 'HResume'),
            'type' => 'full_backup',
            'environment' => config('app.env'),
            'exported_at' => Carbon::now()->toIso8601String(),
            'database' => [
                'driver' => $dbDriver,
                'tables_count' => count($allTables),
            ],
            'assets' => [
                'total_files' => $addedFilesCount,
            ],
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip->close();

        // Cleanup temporary SQL file
        File::delete($tempSqlFile);

        $size = File::exists($zipPath) ? File::size($zipPath) : 0;

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'size_bytes' => $size,
            'size_formatted' => $this->databaseExportService->formatBytes($size),
            'mime' => 'application/zip',
            'files_count' => $addedFilesCount,
        ];
    }

    /**
     * Map scope names to source directories on disk and zip relative path prefix.
     *
     * @param  array<string>  $scopes
     * @return array<string, string> [sourcePath => zipPrefix]
     */
    protected function resolveDirectoriesForScopes(array $scopes): array
    {
        $all = in_array('all', $scopes, true);
        $dirs = [];

        if ($all || in_array('public', $scopes, true) || in_array('avatars', $scopes, true)) {
            $avatarDir = storage_path('app/public/avatars');
            if (File::isDirectory($avatarDir)) {
                $dirs[$avatarDir] = 'public/avatars';
            }
            $brandDir = storage_path('app/public/brand-avatars');
            if (File::isDirectory($brandDir)) {
                $dirs[$brandDir] = 'public/brand-avatars';
            }
        }

        if ($all || in_array('public', $scopes, true) || in_array('blog', $scopes, true)) {
            $blogDir = storage_path('app/public/blog');
            if (File::isDirectory($blogDir)) {
                $dirs[$blogDir] = 'public/blog';
            }
        }

        if ($all || in_array('public', $scopes, true) || in_array('templates', $scopes, true)) {
            $tplDir = storage_path('app/public/templates');
            if (File::isDirectory($tplDir)) {
                $dirs[$tplDir] = 'public/templates';
            }
        }

        if ($all || in_array('fonts', $scopes, true)) {
            $fontsDir = storage_path('app/fonts');
            if (File::isDirectory($fontsDir)) {
                $dirs[$fontsDir] = 'fonts';
            }
            $localFontsDir = storage_path('fonts');
            if (File::isDirectory($localFontsDir)) {
                $dirs[$localFontsDir] = 'fonts';
            }
        }

        if ($all || in_array('public', $scopes, true)) {
            $publicRoot = storage_path('app/public');
            if (File::isDirectory($publicRoot) && ! isset($dirs[$publicRoot])) {
                $dirs[$publicRoot] = 'public';
            }
        }

        return $dirs;
    }

    /**
     * List all existing export/backup files in storage/app/backups.
     *
     * @return array<array<string, mixed>>
     */
    public function listExistingBackups(): array
    {
        $backupsDir = storage_path('app/backups');
        if (! File::isDirectory($backupsDir)) {
            return [];
        }

        $files = File::files($backupsDir);
        $backups = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($filename === '.gitignore' || str_starts_with($filename, '.')) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['sql', 'zip', 'json', 'sqlite'], true)) {
                continue;
            }

            $size = $file->getSize();
            $mtime = $file->getMTime();
            $carbonDate = Carbon::createFromTimestamp($mtime);

            $type = 'database';
            if (str_contains($filename, 'assets')) {
                $type = 'assets';
            } elseif (str_contains($filename, 'full')) {
                $type = 'full';
            }

            $backups[] = [
                'filename' => $filename,
                'type' => $type,
                'format' => $ext,
                'size_bytes' => $size,
                'size_formatted' => $this->databaseExportService->formatBytes($size),
                'created_at' => $carbonDate->toIso8601String(),
                'created_at_formatted' => $carbonDate->format('M j, Y H:i:s'),
                'age' => $carbonDate->diffForHumans(),
                'download_url' => route('admin.exports.download', ['filename' => $filename]),
            ];
        }

        // Sort by creation time descending
        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Delete an existing backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        $safeName = basename($filename);
        $filePath = storage_path('app/backups/'.$safeName);

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }
}
