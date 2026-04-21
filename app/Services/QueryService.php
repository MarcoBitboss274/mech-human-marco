<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

class QueryService
{
    /**
     * Query search
     * 
     */
    public static function querySearch(Builder $query, string|null $search, array $search_fields = [])
    {
        if ($search) {
            $query->where(function ($query) use ($search, $search_fields) {
                $searches = explode(' ', $search);
                foreach ($search_fields as $field) {
                    if (is_string($field) && str_contains($field, '.')) {
                        $segments = explode('.', $field);

                        if (count($segments) !== 2) {
                            throw new InvalidArgumentException("Only one relation level is supported for search fields. Field: {$field}");
                        }

                        [$relation, $column] = $segments;

                        if ($relation === '' || $column === '') {
                            throw new InvalidArgumentException("Invalid relation search field. Field: {$field}");
                        }

                        $model = $query->getModel();

                        if (! method_exists($model, $relation)) {
                            throw new InvalidArgumentException("Relation '{$relation}' does not exist on model " . $model::class . ". Field: {$field}");
                        }

                        $relationInstance = $model->{$relation}();

                        if (! $relationInstance instanceof Relation) {
                            throw new InvalidArgumentException("Method '{$relation}' on model " . $model::class . " is not an Eloquent relation. Field: {$field}");
                        }

                        $query->orWhereHas($relation, function (Builder $relationQuery) use ($searches, $column) {
                            $relationQuery->where($column, 'like', "%{$searches[0]}%");

                            if (count($searches) > 1) {
                                $relationQuery->orWhere($column, 'like', "%{$searches[1]}%");
                            }
                        });

                        continue;
                    }

                    $query->orWhere($field, 'like', "%{$searches[0]}%");
                    if (count($searches) > 1) {
                        $query->orWhere($field, 'like', "%{$searches[1]}%");
                    }
                }
            });
        }
    }
}