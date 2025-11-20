<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Browsershot\Browsershot;

class PDFController extends Controller
{
    /**
     * Get Node.js binary path from nvm or system
     */
    private function getNodeBinaryPath(): ?string
    {
        // Try to get Node.js path from environment variable first
        $nodePath = '/home/ubuntu/.nvm/versions/node/v22.15.0/bin/node';
        if ($nodePath && file_exists($nodePath)) {
            return $nodePath;
        }

        $homeDir = getenv('HOME') ?: getenv('USERPROFILE');
        
        // Try to find the current nvm version by reading .nvmrc
        $nvmrcPath = base_path('.nvmrc');
        if (file_exists($nvmrcPath) && $homeDir) {
            $version = trim(file_get_contents($nvmrcPath));
            $majorVersion = explode('.', $version)[0];
            
            // Try to find the latest installed version matching the major version
            $nvmVersionDir = $homeDir . '/.nvm/versions/node';
            if (is_dir($nvmVersionDir)) {
                $versions = glob($nvmVersionDir . '/v' . $majorVersion . '.*');
                if (!empty($versions)) {
                    // Sort and get the latest version
                    rsort($versions);
                    $nodePath = $versions[0] . '/bin/node';
                    if (file_exists($nodePath)) {
                        return $nodePath;
                    }
                }
            }
        }

        // Try to use nvm to get current node path (works if nvm is loaded)
        if ($homeDir && file_exists($homeDir . '/.nvm/nvm.sh')) {
            // Use bash to source nvm and get node path
            $command = sprintf(
                'bash -c "source %s/.nvm/nvm.sh 2>/dev/null && command -v node"',
                escapeshellarg($homeDir)
            );
            $output = shell_exec($command);
            if ($output) {
                $nodePath = trim($output);
                if (file_exists($nodePath)) {
                    return $nodePath;
                }
            }
        }

        // Try to find any Node.js 14+ in nvm versions directory
        if ($homeDir) {
            $nvmVersionDir = $homeDir . '/.nvm/versions/node';
            if (is_dir($nvmVersionDir)) {
                // Get all version directories
                $versions = glob($nvmVersionDir . '/v*');
                if (!empty($versions)) {
                    // Sort versions descending
                    rsort($versions);
                    foreach ($versions as $versionDir) {
                        $nodePath = $versionDir . '/bin/node';
                        if (file_exists($nodePath)) {
                            // Check if version is 14 or higher
                            $versionName = basename($versionDir);
                            preg_match('/v(\d+)/', $versionName, $matches);
                            if (!empty($matches[1]) && (int)$matches[1] >= 14) {
                                return $nodePath;
                            }
                        }
                    }
                }
            }
        }

        // Fallback to system node (might be old version, but better than nothing)
        $systemNode = shell_exec('command -v node 2>/dev/null');
        if ($systemNode) {
            return trim($systemNode);
        }

        return null;
    }

    /**
     * Generate PDF from HTML content
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'html' => 'required|string',
            'filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $html = $request->input('html');
            $filename = $request->input('filename', 'resume.pdf');

            // Get Node.js binary path
            $nodePath = $this->getNodeBinaryPath();
            Log::info('PDF generation node path', ['nodePath' => $nodePath]);

            $chromePath = '/usr/bin/google-chrome-stable';
            $userDataDir = '/var/www/chrome-profile';

            // Generate PDF using Browsershot (Puppeteer)
            $browsershot = Browsershot::html($html)
                ->setChromePath($chromePath)
                ->setChromeOptions(['args' => [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--user-data-dir=' . $userDataDir,
                ]])
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu'
                ]) // For server environments
                ->margins(20, 20, 20, 20, 'mm') // Top, Right, Bottom, Left (matches p-8 padding)
                ->format('A4')
                ->showBackground()
                ->waitUntilNetworkIdle(false) // Wait until network is idle
                ->dismissDialogs()
                ->timeout(60); // 60 second timeout

            // Set Node.js binary path if found
            // Note: Browsershot uses setNodeBinary() method to set the Node.js path
            if ($nodePath && method_exists($browsershot, 'setNodeBinary')) {
                $browsershot->setNodeBinary($nodePath);
                Log::info('Browsershot setNodeBinary called', ['nodePath' => $nodePath]);
            } elseif ($nodePath && method_exists($browsershot, 'setNodePath')) {
                $browsershot->setNodePath($nodePath);
                Log::info('Browsershot setNodePath called', ['nodePath' => $nodePath]);
            } elseif ($nodePath) {
                // If methods don't exist, set via environment variable
                // This ensures the correct Node.js is used when browsershot executes
                putenv('PATH=' . dirname($nodePath) . ':' . getenv('PATH'));
                Log::info('Browsershot PATH updated via putenv', ['nodePath' => $nodePath]);
            } else {
                Log::warning('Browsershot node path missing');
            }

            $pdf = $browsershot->pdf();

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'PDF generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
