<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_sd',
        'date_range',
        'description_en',
        'description_sd',
        'order',
    ];

    /**
     * Extract inclusive start/end years from free-text date_range values.
     * Handles hyphen/en-dash/"to" copy and trailing "Present".
     *
     * @return array{0: int, 1: int}|null
     */
    public function yearRange(): ?array
    {
        $raw = trim((string) $this->date_range);
        if ($raw === '') {
            return null;
        }

        preg_match_all('/\b(\d{3,4})\b/', $raw, $matches);
        $years = array_map('intval', $matches[1] ?? []);
        if ($years === []) {
            return null;
        }

        $startYear = $years[0];
        $endYear = preg_match('/present/i', $raw)
            ? (int) date('Y')
            : ($years[count($years) - 1] ?? null);

        if ($endYear === null) {
            return null;
        }

        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        return [$startYear, $endYear];
    }
}
