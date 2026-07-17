<?php

namespace App\Traits;

trait UppercaseAttributes
{

    protected array $uppercaseExcept = [
        'fcm_token',
        'password'
    ];

    public function setAttribute($key, $value)
    {
        if (
            is_string($value)
            && !in_array($key, $this->uppercaseExcept, true)
        ) {
            $value = strtoupper($value);
        }

        return parent::setAttribute($key, $value);
    }
}
