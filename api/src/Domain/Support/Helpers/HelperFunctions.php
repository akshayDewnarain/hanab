<?php

namespace Domain\Support\Helpers;

class HelperFunctions
{
    /**
     * Flattens nested properties represented in dot notation into a unique list.
     *
     * @param  array<string>  $data  Array of dot-notated properties.
     * @param  string  $delimiter  Delimiter used for separating nested properties.
     * @return array<string> Flattened unique property paths.
     */
    public static function flatNestedProperties(array $data, string $delimiter = '.'): array
    {
        $properties = [];

        foreach ($data as $prop) {
            $keys = explode($delimiter, $prop);
            $newKey = '';

            foreach ($keys as $key) {
                $newKey = empty($newKey) ? $key : "{$newKey}{$delimiter}{$key}";

                if (! in_array($newKey, $properties)) {
                    $properties[] = $newKey;
                }
            }
        }

        return $properties;
    }
}
