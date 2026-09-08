<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GeneratesAnnualFolio
{
    public static function for(string $modelClass, string $prefix): string
    {
        return DB::transaction(function () use ($modelClass, $prefix) {
            $year = now()->year;
            /** @var Model|null $last */
            $last = $modelClass::where('folio', 'like', "$prefix/$year/%")
                ->lockForUpdate()->orderByDesc('id')->first();
            $sequence = $last ? ((int) substr($last->folio, strrpos($last->folio, '/') + 1)) + 1 : 1;
            return sprintf('%s/%d/%03d', $prefix, $year, $sequence);
        });
    }
}
