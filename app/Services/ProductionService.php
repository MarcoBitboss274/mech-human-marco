<?php

namespace App\Services;

use App\Models\Production;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductionService extends ModelService
{
    protected static function getClass(): string
    {
        return Production::class;
    }

    public static function fetch(Request|null $request)
    {
        return static::getClass()::query()->with([
            'operation:id,typology,status',
        ]);
    }

    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['status']);
        }

        return $query;
    }

    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    public static function getAdminShowData(Production $production): array
    {
        $production->load([
            'operation:id,typology,status',
        ]);

        return [
            'production' => $production->toArray(),
        ];
    }
}

