<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssetExportService;
use App\Services\DatabaseExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminExportController extends Controller
{
    public function __construct(
        protected DatabaseExportService $databaseExportService,
        protected AssetExportService $assetExportService
    ) {}

    /**
     * Get system export status and statistics.
     */
    public function status(): JsonResponse
    {
        try {
            $dbStats = $this->databaseExportService->getDatabaseStats();
            $assetStats = $this->assetExportService->getAssetStats();
            $backups = $this->assetExportService->listExistingBackups();

            return response()->json([
                'status' => true,
                'message' => 'Export status retrieved successfully',
                'data' => [
                    'database' => $dbStats,
                    'assets' => $assetStats,
                    'backups' => $backups,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve export status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate database export.
     */
    public function exportDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'nullable|string|in:sql,sqlite,json',
            'tables' => 'nullable|array',
            'tables.*' => 'string',
            'compress' => 'nullable|boolean',
        ]);

        $format = $validated['format'] ?? 'sql';
        $tables = $validated['tables'] ?? null;
        $compress = (bool) ($validated['compress'] ?? false);

        try {
            $result = $this->databaseExportService->export($format, $tables, $compress, true);

            return response()->json([
                'status' => true,
                'message' => 'Database exported successfully',
                'data' => array_merge($result, [
                    'download_url' => route('admin.exports.download', ['filename' => $result['filename']]),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Database export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate assets export.
     */
    public function exportAssets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|in:all,public,avatars,blog,templates,fonts',
        ]);

        $scopes = $validated['scopes'] ?? ['all'];

        try {
            $result = $this->assetExportService->exportAssets($scopes);

            return response()->json([
                'status' => true,
                'message' => 'Assets exported successfully',
                'data' => array_merge($result, [
                    'download_url' => route('admin.exports.download', ['filename' => $result['filename']]),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Assets export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate full system backup (Database + Assets).
     */
    public function exportFull(Request $request): JsonResponse
    {
        try {
            $result = $this->assetExportService->exportFullBackup();

            return response()->json([
                'status' => true,
                'message' => 'Full backup generated successfully',
                'data' => array_merge($result, [
                    'download_url' => route('admin.exports.download', ['filename' => $result['filename']]),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Full backup failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a generated backup file.
     */
    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $safeName = basename($filename);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

        if ($safeName === '.gitignore' || str_starts_with($safeName, '.') || ! in_array($ext, ['sql', 'zip', 'json', 'sqlite'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid backup file',
            ], 404);
        }

        $filePath = storage_path('app/backups/'.$safeName);
        if (! File::exists($filePath) || ! File::isFile($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'Backup file not found',
            ], 404);
        }

        return response()->download($filePath, $safeName, [
            'Content-Type' => $this->databaseExportService->getMimeType($safeName),
        ]);
    }

    /**
     * Direct download streaming for database export.
     */
    public function downloadDatabaseDirect(Request $request): BinaryFileResponse|JsonResponse
    {
        $format = $request->query('format', 'sql');
        if (! in_array($format, ['sql', 'sqlite', 'json'], true)) {
            $format = 'sql';
        }

        $compress = (bool) $request->query('compress', false);
        $tables = $request->query('tables');
        if (is_string($tables)) {
            $tables = array_filter(explode(',', $tables));
        }

        try {
            $result = $this->databaseExportService->export($format, $tables, $compress, true);

            return response()->download($result['path'], $result['filename'], [
                'Content-Type' => $result['mime'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Database export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Direct download streaming for assets export.
     */
    public function downloadAssetsDirect(Request $request): BinaryFileResponse|JsonResponse
    {
        $scope = $request->query('scope', 'all');
        $validScopes = ['all', 'public', 'avatars', 'blog', 'templates', 'fonts'];
        $scopes = in_array($scope, $validScopes, true) ? [$scope] : ['all'];

        try {
            $result = $this->assetExportService->exportAssets($scopes);

            return response()->download($result['path'], $result['filename'], [
                'Content-Type' => $result['mime'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Assets export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Direct download streaming for full backup.
     */
    public function downloadFullDirect(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $result = $this->assetExportService->exportFullBackup();

            return response()->download($result['path'], $result['filename'], [
                'Content-Type' => $result['mime'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Full backup failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an existing backup file.
     */
    public function destroy(string $filename): JsonResponse
    {
        $safeName = basename($filename);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

        if ($safeName === '.gitignore' || str_starts_with($safeName, '.') || ! in_array($ext, ['sql', 'zip', 'json', 'sqlite'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid backup file',
            ], 404);
        }

        $deleted = $this->assetExportService->deleteBackup($safeName);

        if (! $deleted) {
            return response()->json([
                'status' => false,
                'message' => 'Backup file not found or could not be deleted',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Backup file deleted successfully',
        ]);
    }
}
