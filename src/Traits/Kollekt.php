<?php

namespace KaryaKarsa\Kollekt\Traits;

use Illuminate\Http\Request;
use KaryaKarsa\Kollekt\Helpers\KollektHelper;

trait Kollekt
{
    private $allowed_fields;
    /**
     * @throws \ErrorException
     */
    public function initializeKollekt()
    {
        if ($this->filterable) {
            $this->allowed_fields = $this->filterable;
        } else {
            $this->allowed_fields = $this->getFillable();
        }

        if (!$this->allowed_fields) {
            throw new \ErrorException('No $filterable column set, please specify it on your model. Example: protected $filterable = ["title", "description"]');
        }
    }

    /**
     * This should do extra query to match KaryaKarsa API Guideline
     *
     * @usage
     * Model::kollekt($request)->all();
     *
     * @throws \ErrorException
     */
    public function scopeKollekt($query, Request $request)
    {
        $filters = $this->generateFilters($request);
        $orderBy = $request->query('order_by');
        $with = $this->generateRelationsQuery($request);
        $fields = $request->query('fields');

        if ($fields) {
            // apply custom select
            $fields = explode(',', str_replace(' ', '', $fields));
            // apply select based on fields
            $query->select($fields);
        }

        if ($filters) {
            // apply filters
            $query->whereRaw($filters);
        }

        if ($with) {
            // apply relations
            $query->with($with);
        }

        if ($orderBy) {
            // apply order
            $query->orderBy($orderBy);
        }

        return $query;
    }

    /**
     * Generate query filters
     *
     * @param Request $request
     * @return array|string|string[]
     * @throws \ErrorException
     */
    public function generateFilters(Request $request)
    {
        $filter = $request->query('filter');

        if (!$filter) {
            return null;
        }

        // check invalid fields filter
        $fields = KollektHelper::getFieldsFromFilter($filter);
        $fieldDiff = array_diff($fields, $this->allowed_fields);
        if (!!$fieldDiff) {
            // if fields contain not allowed fields, return exception
            throw new \ErrorException("Unknown field:" . implode(',', $fieldDiff));
        }
        $filters = KollektHelper::makeFilter($filter, $this->allowed_fields);

        return $filters;
    }

    /**
     * Generate relations query
     *
     * @param Request $request
     * @return array|false|\Illuminate\Support\Collection|string|string[]|null
     */
    public function generateRelationsQuery(Request $request)
    {
        $with = $request->query('with');
        $relationFields = KollektHelper::getRelationFieldsFromQuery($request->query);

        if ($with) {
            $with = explode(',', $with);

            if (is_array($relationFields) && count($relationFields) > 0) {
                $relationFieldsKey = array_keys($relationFields);
                // handle custom select for relation
                $with = collect($with)->map(function($w) use ($relationFields, $relationFieldsKey) {
                    $key = $w . '_fields';

                    if (in_array($key, $relationFieldsKey)) {
                        // modify relation with custom select
                        return $w . ':' . $relationFields[$key];
                    }

                    return $w;
                })->all();
            }
        }

        return $with;
    }
}
