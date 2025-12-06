<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPattern extends Model
{
    protected $fillable = [
        'name',
        'regex_pattern',
        'icon',
        'color',
        'parser_config',
        'priority',
        'enabled',
        'description',
    ];

    protected $casts = [
        'parser_config' => 'array',
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get enabled patterns ordered by priority
     */
    public static function getActive()
    {
        return static::where('enabled', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Parse a log message with this pattern
     */
    public function parse(string $message): ?array
    {
        if (!preg_match($this->regex_pattern, $message, $matches)) {
            return null;
        }

        $result = [
            'pattern_name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'raw' => $message,
            'matches' => $matches,
        ];

        // If parser_config exists, use it for advanced parsing
        if ($this->parser_config && isset($this->parser_config['extractor'])) {
            $result['parsed'] = $this->extractKeyValuePairs($matches[1] ?? $message);
        }

        return $result;
    }

    /**
     * Extract key=value pairs from a string
     */
    private function extractKeyValuePairs(string $data): array
    {
        $parsed = [];
        $kvRegex = '/(\w+)=([\d.]+)/';
        if (preg_match_all($kvRegex, $data, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $parsed[$match[1]] = $match[2];
            }
        }
        return $parsed;
    }
}
