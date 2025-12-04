<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ArduinoCliService
{
    protected string $arduinoCliPath;
    protected string $workDir;

    public function __construct()
    {
        // Arduino CLI binary path - kann in .env konfiguriert werden
        $this->arduinoCliPath = env('ARDUINO_CLI_PATH', 'arduino-cli');
        $this->workDir = storage_path('app/arduino-builds');

        // Ensure work directory exists
        if (!file_exists($this->workDir)) {
            mkdir($this->workDir, 0755, true);
        }
    }

    /**
     * Check if Arduino CLI is available
     */
    public function isAvailable(): bool
    {
        $result = Process::run("{$this->arduinoCliPath} version");
        return $result->successful();
    }

    /**
     * Get Arduino CLI version
     */
    public function getVersion(): ?string
    {
        $result = Process::run("{$this->arduinoCliPath} version");
        if ($result->successful()) {
            return trim($result->output());
        }
        return null;
    }

    /**
     * List connected boards
     */
    public function listBoards(): array
    {
        $result = Process::run("{$this->arduinoCliPath} board list --format json");

        if ($result->successful()) {
            $data = json_decode($result->output(), true);
            return $data ?? [];
        }

        return [];
    }

    /**
     * Compile Arduino sketch
     *
     * @param string $code C++ code to compile
     * @param string $board FQBN (Fully Qualified Board Name), e.g., "esp32:esp32:esp32"
     * @param string $scriptName Name for the sketch directory
     * @return array ['success' => bool, 'output' => string, 'error' => string]
     */
    public function compile(string $code, string $board = 'esp32:esp32:esp32', string $scriptName = 'sketch'): array
    {
        try {
            // Create sketch directory
            $sketchDir = $this->workDir . '/' . $scriptName;
            $inoFile = $sketchDir . '/' . $scriptName . '.ino';

            if (!file_exists($sketchDir)) {
                mkdir($sketchDir, 0755, true);
            }

            // Write code to .ino file
            file_put_contents($inoFile, $code);

            // Compile command
            $command = sprintf(
                '%s compile --fqbn %s %s 2>&1',
                $this->arduinoCliPath,
                escapeshellarg($board),
                escapeshellarg($sketchDir)
            );

            Log::info("Arduino compile command: {$command}");

            $result = Process::timeout(300)->run($command);

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
                'sketch_path' => $sketchDir,
            ];

        } catch (\Exception $e) {
            Log::error("Arduino compile error: " . $e->getMessage());
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload compiled sketch to board
     *
     * @param string $sketchPath Path to compiled sketch
     * @param string $port Serial port (e.g., "COM3" or "/dev/ttyUSB0")
     * @param string $board FQBN
     * @return array ['success' => bool, 'output' => string, 'error' => string]
     */
    public function upload(string $sketchPath, string $port, string $board = 'esp32:esp32:esp32'): array
    {
        try {
            $command = sprintf(
                '%s upload --fqbn %s --port %s %s 2>&1',
                $this->arduinoCliPath,
                escapeshellarg($board),
                escapeshellarg($port),
                escapeshellarg($sketchPath)
            );

            Log::info("Arduino upload command: {$command}");

            $result = Process::timeout(300)->run($command);

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ];

        } catch (\Exception $e) {
            Log::error("Arduino upload error: " . $e->getMessage());
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compile and upload in one step
     */
    public function compileAndUpload(string $code, string $port, string $board = 'esp32:esp32:esp32', string $scriptName = 'sketch'): array
    {
        // First compile
        $compileResult = $this->compile($code, $board, $scriptName);

        if (!$compileResult['success']) {
            return [
                'success' => false,
                'compile_output' => $compileResult['output'],
                'compile_error' => $compileResult['error'],
                'upload_output' => '',
                'upload_error' => 'Compilation failed, upload skipped',
            ];
        }

        // Then upload
        $uploadResult = $this->upload($compileResult['sketch_path'], $port, $board);

        return [
            'success' => $uploadResult['success'],
            'compile_output' => $compileResult['output'],
            'compile_error' => $compileResult['error'],
            'upload_output' => $uploadResult['output'],
            'upload_error' => $uploadResult['error'],
        ];
    }

    /**
     * Install board package (core)
     */
    public function installCore(string $core = 'esp32:esp32'): array
    {
        $result = Process::timeout(600)->run("{$this->arduinoCliPath} core install {$core}");

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Install library
     */
    public function installLibrary(string $library): array
    {
        $result = Process::timeout(300)->run("{$this->arduinoCliPath} lib install {$library}");

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Clean up build files for a script
     */
    public function cleanup(string $scriptName): bool
    {
        $sketchDir = $this->workDir . '/' . $scriptName;

        if (file_exists($sketchDir)) {
            return $this->deleteDirectory($sketchDir);
        }

        return true;
    }

    /**
     * Recursively delete directory
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }
}
