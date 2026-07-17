<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasMexicoTimezone
{
    /**
     * Get the created_at attribute in Mexico timezone.
     *
     * @param  string  $value
     * @return \Carbon\Carbon|null
     */
    public function getCreatedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)
            ->setTimezone('America/Mexico_City');
    }

    /**
     * Get the updated_at attribute in Mexico timezone.
     *
     * @param  string  $value
     * @return \Carbon\Carbon|null
     */
    public function getUpdatedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)
            ->setTimezone('America/Mexico_City');
    }
}
