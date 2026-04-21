<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class ModelService
{
    /**
     * Model pagination limit
     */
    protected static int $pagination_limit = 10;

    /**
     * Model with
     */
    protected static array $with = [];

    /**
     * Model class
     * 
     */
    abstract protected static function getClass(): string;

    /**
     * New model instance
     * 
     */
    public final static function newInstance()
    {
        $class = static::getClass();
        return new $class();
    }

    /**
     * Fetch model
     * 
     */
    public static function fetch(Request|null $request)
    {
        return static::getClass()::query();
    }

    /**
     * Search model
     * 
     */
    public static function search(Request $request, bool $paginate = true, array $with = [], array $select = [])
    {
        $query = static::fetch($request)
            ->with($with ?? static::$with);

        static::applySearch($query, $request);
        static::applyFilters($query, $request);
        static::applySorts($query, $request);

        return $paginate ? $query->paginate(
            perPage: $request['per_page'] ?? static::$pagination_limit,
            page: $request['page'] ?? 1,
        ) : $query->get();
    }

    /**
     * MeiliSearch model
     * 
     */
    public static function meiliSearch(Request $request, bool $paginate = true, array $with = [], array $select = [])
    {
        $ids = static::getClass()::search($request['query'])
            ->take(10000)
            ->get()
            ->pluck('id');

        $query = static::fetch($request)
            ->with($with ?? static::$with)
            ->whereIn('id', $ids);

        if (count($select)) {
            $query->select($select);
        }

        static::applyFilters($query, $request);
        static::applySorts($query, $request);

        return $paginate ? $query->paginate(
            perPage: intval($request['per_page'] ?? static::$pagination_limit),
            page: intval($request['page'] ?? 1),
        ) : $query->get();
    }

    /**
     * Apply search
     * 
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        return $query;
    }

    /**
     * Apply filters
     * 
     */
    public static function applyFilters(Builder $query, Request|null $request): Builder
    {
        return $query;
    }

    /**
     * Apply sorts
     * 
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query;
    }

    /**
     * Store model
     * 
     */
    public static function store(Model|null $model, array $data = []): Model
    {
        $model = $model ?? static::newInstance();
        $model->fill(
            Arr::only($data, $model->getFillable())
        );
        $model->save();

        return $model;
    }

    /**
     * Save model
     * 
     */
    public static function save(Model|null $model, array $data = [])
    {
        try {
            return static::store($model, $data);
        } catch (Exception $ex) {
            Log::error(static::getClass() . ' (save) : ' . $ex->getMessage() . ' on line ' . $ex->getLine() . ' in file ' . $ex->getFile());
            return 1;
        }
    }

    /**
     * Delete model
     * 
     */
    public static function delete(Model|null $model)
    {
        try {
            if ($model) {
                $model->delete();
            }

            return 0;
        } catch (Exception $ex) {
            Log::error(static::getClass() . ' (delete) : ' . $ex->getMessage());
            return 1;
        }
    }

    /**
     * Delete all models
     * 
     */
    public static function deleteAll($models = [])
    {
        foreach ($models as $model) {
            static::delete($model);
        }
    }

    /**
     * Generate random password
     * 
     */
    public static function generateRandomPassword()
    {
        return Str::random(10);
    }
}
