<?php

namespace App\Validators;

/**
 * Class UsernameValidator.
 */
class UsernameValidator
{
    public static function validate($attribute, $value, $parameters, $validator)
    {
        return \preg_match('/^[a-zA-Z]+[a-zA-Z0-9\-]+$/', $value);
    }
}
