<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        // Clean up test backups
        $backupsDir = storage_path('app/backups');
        if (File::isDirectory($backupsDir)) {
            $files = File::files($backupsDir);
            foreach ($files as $file) {
                if ($file->getFilename() !== '.gitignore') {
                    File::delete($file->getRealPath());
                }
            }
        }

        parent::tearDown();
    }

    protected function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    protected function regularUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    public function test_non_admin_cannot_access_export_page_or_endpoints(): void
    {
        $user = $this->regularUser();

        $this->actingAs($user)->get('/admin/exports')->assertRedirect('/resumes');
        $this->actingAs($user)->getJson('/api/admin/exports/status')->assertForbidden();
        $this->actingAs($user)->postJson('/api/admin/exports/database')->assertForbidden();
        $this->actingAs($user)->postJson('/api/admin/exports/assets')->assertForbidden();
        $this->actingAs($user)->postJson('/api/admin/exports/full')->assertForbidden();
    }

    public function test_admin_can_view_exports_page(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/exports');

        $response->assertOk()
            ->assertSee('Database &amp; Assets Export', false)
            ->assertSee('Export Database to File', false)
            ->assertSee('Export Assets to File', false);
    }

    public function test_admin_can_retrieve_export_status(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->getJson('/api/admin/exports/status');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'database' => ['driver', 'database_name', 'total_tables', 'total_records', 'tables'],
                    'assets' => ['total_files', 'total_bytes', 'categories'],
                    'backups',
                ],
            ]);
    }

    public function test_admin_can_export_database_to_sql_file(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/api/admin/exports/database', [
            'format' => 'sql',
            'tables' => ['users', 'templates'],
            'compress' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data['filename']);
        $this->assertStringEndsWith('.sql', $data['filename']);
        $this->assertFileExists($data['path']);

        $content = file_get_contents($data['path']);
        $this->assertStringContainsString('CREATE TABLE "users"', $content);
        $this->assertStringContainsString('CREATE TABLE "templates"', $content);
    }

    public function test_admin_can_export_database_compressed_with_zip(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/api/admin/exports/database', [
            'format' => 'sql',
            'compress' => true,
        ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertStringEndsWith('.zip', $data['filename']);
        $this->assertFileExists($data['path']);
    }

    public function test_admin_can_export_database_to_json(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/api/admin/exports/database', [
            'format' => 'json',
            'tables' => ['users'],
            'compress' => false,
        ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertStringEndsWith('.json', $data['filename']);
        $this->assertFileExists($data['path']);

        $decoded = json_decode(file_get_contents($data['path']), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertArrayHasKey('tables', $decoded);
    }

    public function test_admin_can_export_assets_to_zip(): void
    {
        $admin = $this->adminUser();

        // Create a dummy asset file to ensure zip packaging is tested
        $dummyDir = storage_path('app/public/avatars');
        File::makeDirectory($dummyDir, 0755, true, true);
        File::put($dummyDir.'/test_avatar.jpg', 'fake-image-bytes');

        $response = $this->actingAs($admin)->postJson('/api/admin/exports/assets', [
            'scopes' => ['avatars'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true);

        $data = $response->json('data');
        $this->assertStringEndsWith('.zip', $data['filename']);
        $this->assertFileExists($data['path']);

        // Inspect zip contents
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($data['path']));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $zip->close();
    }

    public function test_admin_can_export_full_backup(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/api/admin/exports/full');

        $response->assertOk()
            ->assertJsonPath('status', true);

        $data = $response->json('data');
        $this->assertStringContainsString('hresume_full_backup_', $data['filename']);
        $this->assertStringEndsWith('.zip', $data['filename']);
        $this->assertFileExists($data['path']);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($data['path']));
        $this->assertNotFalse($zip->locateName('database/database_dump.sql'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $zip->close();
    }

    public function test_admin_can_download_and_delete_backup(): void
    {
        $admin = $this->adminUser();

        // Generate a database export
        $genResponse = $this->actingAs($admin)->postJson('/api/admin/exports/database', ['format' => 'sql']);
        $filename = $genResponse->json('data.filename');

        // Download
        $downloadResponse = $this->actingAs($admin)->get("/admin/exports/download/{$filename}");
        $downloadResponse->assertOk()
            ->assertHeader('content-disposition', "attachment; filename={$filename}");

        // Delete
        $deleteResponse = $this->actingAs($admin)->deleteJson("/api/admin/exports/{$filename}");
        $deleteResponse->assertOk()
            ->assertJsonPath('status', true);

        // Verify file is gone
        $this->assertFileDoesNotExist(storage_path('app/backups/'.$filename));
    }

    public function test_admin_can_direct_download_database_and_assets(): void
    {
        $admin = $this->adminUser();

        $dbResponse = $this->actingAs($admin)->get('/admin/exports/database/download?format=sql');
        $dbResponse->assertOk()
            ->assertHeader('content-type', 'application/sql');

        $assetsResponse = $this->actingAs($admin)->get('/admin/exports/assets/download?scope=all');
        $assetsResponse->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $fullResponse = $this->actingAs($admin)->get('/admin/exports/full/download');
        $fullResponse->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_path_traversal_is_blocked_on_download_and_delete(): void
    {
        $admin = $this->adminUser();

        // Accessing hidden/sensitive files or non-existent files returns 404
        $this->actingAs($admin)->get('/admin/exports/download/.gitignore')
            ->assertNotFound();

        $this->actingAs($admin)->get('/admin/exports/download/non_existent_file.sql')
            ->assertNotFound();

        $this->actingAs($admin)->deleteJson('/api/admin/exports/.gitignore')
            ->assertNotFound();

        $this->actingAs($admin)->deleteJson('/api/admin/exports/non_existent_file.sql')
            ->assertNotFound();
    }
}
