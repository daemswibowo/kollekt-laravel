<?php

namespace KaryaKarsa\Kollekt\Helpers;

class KollektHelper
{
    public static $comparison_operators = [' eq ', ' neq ', ' gt ', ' ge ', ' lt ', ' le '];
    public static $comparison_operator_replacement = [' = ', ' != ', ' > ', ' >= ', ' < ', ' <= '];
    public static $logical_operators = [' and ', ' or ', ' not '];

    /**
     * @param string $filter
     * @return array|string|string[]
     */
    public static function sanitizeFilter(string $filter, array $allowed_fields = [])
    {
        // create pattern for logical operators
        $pattern = implode('|', self::$logical_operators);

        // separate each filter query based on logical operators
        $filters = preg_split('/' . $pattern . '/', $filter);
        $replaceWith = [];

        foreach ($filters as $f) {
            // check filters if match comparison operators or not
            if (preg_match('/' . implode('|', self::$comparison_operators) . '/', $f) > 0 and in_array(strtok(str_replace('(', '', $f), ' '), $allowed_fields)) {
                $replaceWith[] = $f;
            } else {
                // remove filter that doesn't match comparison operators
                $replaceWith[] = '';
            }
        }

        // sanitize filter
        return str_replace($filters, $replaceWith, $filter);
    }

    /**
     * @param string $filter
     * @return array|string|string[]
     */
    public static function makeFilter(string $filter, array $allowed_fields = [])
    {
        return str_replace(self::$comparison_operators, self::$comparison_operator_replacement, self::sanitizeFilter($filter, $allowed_fields));
    }

    public static function getFieldsFromFilter(string $filter): array
    {
        $filter = str_replace(['(', ')'], '', $filter);
        $filter = preg_split('/' . implode('|', self::$logical_operators) . '/', $filter);
        return collect($filter)->map(function ($item) {
            return strtok($item, " ");
        })->unique()->all();
    }

    public static function getRelationFieldsFromQuery($query): array
    {
        return collect($query)->filter(function ($key, $q) {
            return strpos($q, '_fields');
        })->toArray();
    }
}
