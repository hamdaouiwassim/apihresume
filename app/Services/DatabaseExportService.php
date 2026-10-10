<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use ZipArchive;

class DatabaseExportService
{
    /**
     * Core business and content tables belonging to the HResume application.
     * Exported by default in logical dependency order.
     */
    public const CORE_APP_TABLES = [
        'users',
        'admins',
        'candidates',
        'recruiters',
        'personal_access_tokens',
        'password_reset_tokens',
        'resumes',
        'basic_infos',
        'experiences',
        'education',
        'skills',
        'projects',
        'certificates',
        'languages',
        'hobbies',
        'resume_collaborators',
        'shareable_links',
        'templates',
        'template_proposals',
        'cover_letters',
        'cover_letter_templates',
        'work_certificates',
        'blog_posts',
        'reviews',
        'subscribers',
        'pdf_fonts',
        'ai_usage_logs',
        'outbound_emails',
        'migrations',
    ];

    /**
     * Ephemeral Laravel framework cache/job/session tables.
     * Can optionally be selected but excluded from core data exports.
     */
    public const SYSTEM_EPHEMERAL_TABLES = [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'pulse_values',
        'pulse_entries',
        'pulse_aggregates',
    ];

    /**
     * Get the configured database name from .env / config for this application.
     */
    public function getTargetDatabaseName(): string
    {
        $connection = config('database.default', 'sqlite');
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $dbPath = config("database.connections.{$connection}.database", '');

            return basename($dbPath) ?: 'database.sqlite';
        }

        return (string) (
            config("database.connections.{$connection}.database")
            ?: env('DB_DATABASE')
            ?: DB::connection()->getDatabaseName()
            ?: 'laravel'
        );
    }

    /**
     * Get summary statistics for the database.
     *
     * @return array<string, mixed>
     */
    public function getDatabaseStats(): array
    {
        $connection = config('database.default', 'sqlite');
        $driver = DB::connection()->getDriverName();
        $targetDb = $this->getTargetDatabaseName();

        $allTables = $this->getAllTables(false);
        $tableStats = [];
        $totalRecords = 0;
        $coreRecords = 0;

        foreach ($allTables as $table) {
            try {
                $count = DB::table($table)->count();
            } catch (\Throwable) {
                $count = 0;
            }
            $isCore = in_array($table, self::CORE_APP_TABLES, true);
            $tableStats[] = [
                'name' => $table,
                'rows' => $count,
                'is_core' => $isCore,
                'category' => $isCore ? 'App Data' : 'Cache / Session',
            ];
            $totalRecords += $count;
            if ($isCore) {
                $coreRecords += $count;
            }
        }

        // Calculate database size strictly for the application's tables within targetDb
        $sizeBytes = 0;
        if ($driver === 'sqlite') {
            $path = config("database.connections.{$connection}.database", '');
            if (! is_file($path)) {
                $path = database_path('database.sqlite');
            }
            if (File::exists($path)) {
                $sizeBytes = File::size($path);
            }
        } elseif (($driver === 'mysql' || $driver === 'mariadb') && ! empty($allTables)) {
            try {
                $placeholders = implode(',', array_fill(0, count($allTables), '?'));
                $bindings = array_merge([$targetDb], $allTables);
                $result = DB::select(
                    "SELECT SUM(data_length + index_length) AS size_bytes FROM information_schema.TABLES WHERE table_schema = ? AND table_name IN ({$placeholders})",
                    $bindings
                );
                $sizeBytes = (int) ($result[0]->size_bytes ?? 0);
            } catch (\Throwable) {
                $sizeBytes = 0;
            }
        }

        $coreTables = $this->getAllTables(true);

        return [
            'driver' => $driver,
            'connection' => $connection,
            'database_name' => $targetDb,
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatBytes($sizeBytes),
            'total_tables' => count($allTables),
            'core_tables_count' => count($coreTables),
            'total_records' => $totalRecords,
            'core_records' => $coreRecords,
            'tables' => $tableStats,
        ];
    }

    /**
     * List application-related tables that exist in the specific target database from .env.
     * Any foreign or unrelated tables in MySQL are automatically excluded.
     *
     * @param  bool  $coreOnly  If true, returns only core application data tables (excludes cache/sessions/pulse).
     * @return array<string>
     */
    public function getAllTables(bool $coreOnly = false): array
    {
        $connection = config('database.default', 'sqlite');
        $driver = DB::connection()->getDriverName();
        $targetDb = $this->getTargetDatabaseName();
        $existing = [];

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Strictly query information_schema for tables belonging ONLY to this specific database from .env
            try {
                $rows = DB::select(
                    'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE"',
                    [$targetDb]
                );
                foreach ($rows as $row) {
                    $name = (string) $row->name;
                    $existing[strtolower($name)] = $name;
                }
            } catch (\Throwable) {
                $rawTables = Schema::getTableListing();
                foreach ($rawTables as $table) {
                    $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
                    $existing[strtolower($name)] = $name;
                }
            }
        } else {
            $rawTables = Schema::getTableListing();
            foreach ($rawTables as $table) {
                $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
                if (! str_starts_with($name, 'sqlite_')) {
                    $existing[strtolower($name)] = $name;
                }
            }
        }

        $allowedList = $coreOnly ? self::CORE_APP_TABLES : array_merge(self::CORE_APP_TABLES, self::SYSTEM_EPHEMERAL_TABLES);
        $result = [];

        // Return strictly in defined logical order, only including tables belonging to this project in the target DB
        foreach ($allowedList as $table) {
            $lower = strtolower($table);
            if (isset($existing[$lower])) {
                $result[] = $existing[$lower];
            }
        }

        return array_values($result);
    }

    /**
     * Export database to a file.
     *
     * @param  string  $format  'sql' | 'sqlite' | 'json'
     * @param  array<string>|null  $tables
     * @return array{path: string, filename: string, size_bytes: int, size_formatted: string, mime: string}
     */
    public function export(string $format = 'sql', ?array $tables = null, bool $compress = false, bool $saveToBackups = true): array
    {
        $allowedTables = $this->getAllTables(false);
        $defaultTables = $this->getAllTables(true); // Default: Core app tables only

        if ($tables !== null && ! empty($tables)) {
            // Strictly intersect with recognized application tables to discard any foreign tables
            $selectedTables = array_values(array_intersect($tables, $allowedTables));
        } else {
            $selectedTables = $defaultTables;
        }

        if (empty($selectedTables)) {
            $selectedTables = $defaultTables;
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupsDir = storage_path('app/backups');
        if (! File::isDirectory($backupsDir)) {
            File::makeDirectory($backupsDir, 0755, true);
        }

        $driver = DB::connection()->getDriverName();

        // 1. Raw SQLite export
        if ($format === 'sqlite' && $driver === 'sqlite') {
            $baseFilename = "hresume_db_{$timestamp}.sqlite";
            $targetPath = $backupsDir.'/'.$baseFilename;
            $this->copySqliteDatabase($targetPath);
        }
        // 2. JSON export
        elseif ($format === 'json') {
            $baseFilename = "hresume_db_{$timestamp}.json";
            $targetPath = $backupsDir.'/'.$baseFilename;
            $this->dumpToJson($selectedTables, $targetPath);
        }
        // 3. SQL export (default)
        else {
            $format = 'sql';
            $baseFilename = "hresume_db_{$timestamp}.sql";
            $targetPath = $backupsDir.'/'.$baseFilename;
            $this->dumpToSql($selectedTables, $targetPath);
        }

        // Handle compression if requested
        if ($compress) {
            $zipFilename = pathinfo($baseFilename, PATHINFO_FILENAME).'.zip';
            $zipPath = $backupsDir.'/'.$zipFilename;

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($targetPath, $baseFilename);
                $zip->close();

                // If only zip was requested, delete raw file unless raw was specifically stored
                File::delete($targetPath);
                $targetPath = $zipPath;
                $baseFilename = $zipFilename;
            }
        }

        $size = File::exists($targetPath) ? File::size($targetPath) : 0;
        $mime = $this->getMimeType($baseFilename);

        return [
            'path' => $targetPath,
            'filename' => $baseFilename,
            'size_bytes' => $size,
            'size_formatted' => $this->formatBytes($size),
            'mime' => $mime,
        ];
    }

    /**
     * Dump the specified tables to an SQL file stream.
     *
     * @param  array<string>  $tables
     */
    public function dumpToSql(array $tables, string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');
        if (! $handle) {
            throw new \RuntimeException("Unable to open file for writing: {$outputPath}");
        }

        $driver = DB::connection()->getDriverName();
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);
        $targetDb = $this->getTargetDatabaseName();
        $timestamp = Carbon::now()->toIso8601String();
        $appName = config('app.name', 'HResume');

        fwrite($handle, "-- ============================================================\n");
        fwrite($handle, "-- {$appName} Database Backup\n");
        fwrite($handle, "-- Database Name: {$targetDb}\n");
        fwrite($handle, "-- Generated: {$timestamp}\n");
        fwrite($handle, "-- Database Driver: {$driver}\n");
        fwrite($handle, '-- Tables ('.count($tables).'): '.implode(', ', $tables)."\n");
        fwrite($handle, "-- ============================================================\n\n");

        if ($driver === 'sqlite') {
            fwrite($handle, "PRAGMA foreign_keys = OFF;\n");
            fwrite($handle, "BEGIN TRANSACTION;\n\n");
        } elseif ($isMysql) {
            fwrite($handle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
            fwrite($handle, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
            fwrite($handle, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
            fwrite($handle, "/*!40101 SET NAMES utf8mb4 */;\n");
            fwrite($handle, "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n");
            fwrite($handle, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n");
            fwrite($handle, "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n");
            fwrite($handle, "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n");
            fwrite($handle, "-- Ensure target database exists and is selected\n");
            fwrite($handle, "CREATE DATABASE IF NOT EXISTS `{$targetDb}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
            fwrite($handle, "USE `{$targetDb}`;\n\n");
        }

        $pdo = DB::connection()->getPdo();

        foreach ($tables as $table) {
            fwrite($handle, "-- ------------------------------------------------------------\n");
            fwrite($handle, "-- Table structure and data for `{$table}`\n");
            fwrite($handle, "-- ------------------------------------------------------------\n");

            // 1. Table schema
            $this->writeTableSchema($handle, $table, $driver);

            // 2. Table records
            $this->writeTableData($handle, $table, $pdo, $driver);

            fwrite($handle, "\n");
        }

        if ($driver === 'sqlite') {
            fwrite($handle, "COMMIT;\n");
            fwrite($handle, "PRAGMA foreign_keys = ON;\n");
        } elseif ($isMysql) {
            fwrite($handle, "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n");
            fwrite($handle, "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
            fwrite($handle, "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n");
            fwrite($handle, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
            fwrite($handle, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
            fwrite($handle, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");
            fwrite($handle, "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n");
        }

        fclose($handle);
    }

    /**
     * Write CREATE TABLE statement for the given table.
     *
     * @param  resource  $handle
     */
    protected function writeTableSchema($handle, string $table, string $driver): void
    {
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);
        $quote = $isMysql ? '`' : '"';

        fwrite($handle, "DROP TABLE IF EXISTS {$quote}{$table}{$quote};\n");

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
            if (! empty($result) && ! empty($result[0]->sql)) {
                fwrite($handle, $result[0]->sql.";\n\n");
            }

            // Also dump custom indexes
            $indices = DB::select("SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND sql IS NOT NULL", [$table]);
            foreach ($indices as $index) {
                if (! empty($index->sql)) {
                    fwrite($handle, $index->sql.";\n");
                }
            }
            if (! empty($indices)) {
                fwrite($handle, "\n");
            }
        } elseif ($isMysql) {
            $result = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($result)) {
                $row = (array) $result[0];
                $createSql = $row['Create Table'] ?? $row['create table'] ?? null;
                if ($createSql) {
                    fwrite($handle, "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
                    fwrite($handle, "/*!40101 SET character_set_client = utf8mb4 */;\n");
                    fwrite($handle, "{$createSql};\n");
                    fwrite($handle, "/*!40101 SET character_set_client = @saved_cs_client */;\n\n");
                }
            }
        } else {
            // Generic DDL generation using columns metadata
            $columns = Schema::getColumns($table);
            $colDefs = [];
            foreach ($columns as $col) {
                $def = "{$quote}{$col['name']}{$quote} {$col['type_name']}";
                if (! $col['nullable']) {
                    $def .= ' NOT NULL';
                }
                if ($col['default'] !== null) {
                    $def .= ' DEFAULT '.$col['default'];
                }
                $colDefs[] = $def;
            }
            fwrite($handle, "CREATE TABLE {$quote}{$table}{$quote} (\n  ".implode(",\n  ", $colDefs)."\n);\n\n");
        }
    }

    /**
     * Write INSERT statements for rows in table.
     *
     * @param  resource  $handle
     */
    protected function writeTableData($handle, string $table, PDO $pdo, string $driver = 'sqlite'): void
    {
        $columns = Schema::getColumnListing($table);
        if (empty($columns)) {
            return;
        }

        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);
        $quote = $isMysql ? '`' : '"';
        $quotedCols = array_map(fn ($c) => "{$quote}{$c}{$quote}", $columns);
        $colsSql = implode(', ', $quotedCols);
        $tableQuoted = "{$quote}{$table}{$quote}";

        $firstCol = $columns[0];

        if ($isMysql) {
            fwrite($handle, "LOCK TABLES {$tableQuoted} WRITE;\n");
            fwrite($handle, "/*!40000 ALTER TABLE {$tableQuoted} DISABLE KEYS */;\n");
        }

        try {
            DB::table($table)->orderBy($firstCol)->chunk(250, function ($rows) use ($handle, $tableQuoted, $colsSql, $columns, $pdo) {
                if ($rows->isEmpty()) {
                    return;
                }

                $valueSets = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $col) {
                        $val = $row->{$col} ?? null;
                        if ($val === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($val) && ! is_string($val)) {
                            $values[] = (string) $val;
                        } elseif (is_bool($val)) {
                            $values[] = $val ? '1' : '0';
                        } else {
                            $values[] = $pdo->quote((string) $val);
                        }
                    }
                    $valueSets[] = '('.implode(', ', $values).')';
                }

                $insertSql = "INSERT INTO {$tableQuoted} ({$colsSql}) VALUES\n  ".implode(",\n  ", $valueSets).";\n";
                fwrite($handle, $insertSql);
            });
        } catch (\Throwable) {
            // Table might not have sortable first column or is empty
            $rows = DB::table($table)->get();
            if ($rows->isNotEmpty()) {
                $valueSets = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $col) {
                        $val = $row->{$col} ?? null;
                        if ($val === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($val) && ! is_string($val)) {
                            $values[] = (string) $val;
                        } elseif (is_bool($val)) {
                            $values[] = $val ? '1' : '0';
                        } else {
                            $values[] = $pdo->quote((string) $val);
                        }
                    }
                    $valueSets[] = '('.implode(', ', $values).')';
                }
                $insertSql = "INSERT INTO {$tableQuoted} ({$colsSql}) VALUES\n  ".implode(",\n  ", $valueSets).";\n";
                fwrite($handle, $insertSql);
            }
        }

        if ($isMysql) {
            fwrite($handle, "/*!40000 ALTER TABLE {$tableQuoted} ENABLE KEYS */;\n");
            fwrite($handle, "UNLOCK TABLES;\n");
        }
    }

    /**
     * Dump specified tables to a formatted JSON file.
     *
     * @param  array<string>  $tables
     */
    public function dumpToJson(array $tables, string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');
        if (! $handle) {
            throw new \RuntimeException("Unable to open file for writing: {$outputPath}");
        }

        $meta = [
            'app' => config('app.name', 'HResume'),
            'exported_at' => Carbon::now()->toIso8601String(),
            'driver' => DB::connection()->getDriverName(),
            'tables_count' => count($tables),
        ];

        fwrite($handle, "{\n  \"metadata\": ".json_encode($meta, JSON_PRETTY_PRINT).",\n  \"tables\": {\n");

        $totalTables = count($tables);
        foreach ($tables as $index => $table) {
            fwrite($handle, "    \"{$table}\": [\n");

            $columns = Schema::getColumnListing($table);
            $firstCol = ! empty($columns) ? $columns[0] : null;

            $isFirstRow = true;
            $query = DB::table($table);
            if ($firstCol) {
                $query->orderBy($firstCol);
            }

            try {
                $query->chunk(500, function ($rows) use ($handle, &$isFirstRow) {
                    foreach ($rows as $row) {
                        $prefix = $isFirstRow ? '      ' : ",\n      ";
                        fwrite($handle, $prefix.json_encode($row));
                        $isFirstRow = false;
                    }
                });
            } catch (\Throwable) {
                $rows = DB::table($table)->get();
                foreach ($rows as $row) {
                    $prefix = $isFirstRow ? '      ' : ",\n      ";
                    fwrite($handle, $prefix.json_encode($row));
                    $isFirstRow = false;
                }
            }

            $tableSuffix = ($index < $totalTables - 1) ? "\n    ],\n" : "\n    ]\n";
            fwrite($handle, $tableSuffix);
        }

        fwrite($handle, "  }\n}\n");
        fclose($handle);
    }

    /**
     * Safe copy of SQLite database file.
     */
    protected function copySqliteDatabase(string $targetPath): void
    {
        $databasePath = config('database.connections.sqlite.database');
        if (! File::exists($databasePath)) {
            $databasePath = database_path('database.sqlite');
        }

        if (! File::exists($databasePath)) {
            throw new \RuntimeException("SQLite database file not found at: {$databasePath}");
        }

        // Try SQLite VACUUM INTO for hot-safe atomic copy if supported
        try {
            DB::statement('VACUUM INTO ?', [$targetPath]);
        } catch (\Throwable) {
            // Fallback to direct file copy
            File::copy($databasePath, $targetPath);
        }
    }

    /**
     * Determine mime type by file extension.
     */
    public function getMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'sql' => 'application/sql',
            'zip' => 'application/zip',
            'json' => 'application/json',
            'sqlite' => 'application/x-sqlite3',
            default => 'application/octet-stream',
        };
    }

    /**
     * Human readable byte formatting.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $precision).' '.$units[$power];
    }
}
