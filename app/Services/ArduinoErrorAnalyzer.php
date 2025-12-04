<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArduinoErrorAnalyzer
{
    private string $apiEndpoint;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiEndpoint = config('services.openai.endpoint', 'https://api.openai.com/v1');
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Analysiert Arduino-Kompilierungsfehler und schlägt Korrekturen vor
     */
    public function analyzeAndFix(string $errorMessage, string $originalCode, string $boardFqbn): array
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API Key nicht konfiguriert - Error-Analyse übersprungen');
            return [
                'has_fix' => false,
                'error' => 'LLM-Integration nicht konfiguriert (OpenAI API Key fehlt)',
            ];
        }

        $prompt = $this->buildPrompt($errorMessage, $originalCode, $boardFqbn);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiEndpoint}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Du bist ein Arduino/C++ Experte. Analysiere Compiler-Fehler und schlage präzise Code-Korrekturen vor. Antworte IMMER im JSON-Format.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::error('OpenAI API Fehler: ' . $response->body());
                return [
                    'has_fix' => false,
                    'error' => 'OpenAI API fehlgeschlagen: HTTP ' . $response->status(),
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return [
                    'has_fix' => false,
                    'error' => 'Keine Antwort vom LLM erhalten',
                ];
            }

            $analysis = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('LLM Response ist kein valides JSON: ' . $content);
                return [
                    'has_fix' => false,
                    'error' => 'Ungültige LLM-Antwort',
                ];
            }

            // Validierung der Response-Struktur
            if (!isset($analysis['error_summary']) || !isset($analysis['fixed_code'])) {
                return [
                    'has_fix' => false,
                    'error' => 'LLM-Response unvollständig',
                ];
            }

            return [
                'has_fix' => true,
                'error_summary' => $analysis['error_summary'],
                'explanation' => $analysis['explanation'] ?? 'Keine Erklärung verfügbar',
                'fixed_code' => $analysis['fixed_code'],
                'confidence' => $analysis['confidence'] ?? 'medium',
            ];

        } catch (\Exception $e) {
            Log::error('ArduinoErrorAnalyzer Exception: ' . $e->getMessage());
            return [
                'has_fix' => false,
                'error' => 'Fehler bei Error-Analyse: ' . $e->getMessage(),
            ];
        }
    }

    private function buildPrompt(string $errorMessage, string $originalCode, string $boardFqbn): string
    {
        return <<<PROMPT
# Arduino Compilation Error Analysis

**Board:** {$boardFqbn}

**Compiler Error:**
```
{$errorMessage}
```

**Original Code:**
```cpp
{$originalCode}
```

**Task:**
Analysiere den Compiler-Fehler und korrigiere den Code.

**Response Format (JSON):**
```json
{
  "error_summary": "Kurze Zusammenfassung des Fehlers (max 100 Zeichen)",
  "explanation": "Detaillierte Erklärung was falsch war und wie der Fix funktioniert",
  "fixed_code": "Der vollständige korrigierte C++ Code",
  "confidence": "high|medium|low"
}
```

**Regeln:**
- `fixed_code` muss vollständiger, kompilierbarer Arduino-Code sein
- Keine zusätzlichen Kommentare außerhalb des JSON
- Behalte Code-Stil und Formatierung bei
- Fixe NUR den Fehler, ändere nichts Unnötiges
PROMPT;
    }
}
