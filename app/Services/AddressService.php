<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AddressService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return Address::class;
    }

    /**
     * Store model with "unique default" logic.
     */
    public static function store(Model|null $model, array $data = []): Model
    {
        $creating = is_null($model);
        $model = $model ?? static::newInstance();

        $buildingId = $data['building_id'] ?? $model->building_id;

        if ($buildingId && ($data['is_default'] ?? $model->is_default)) {
            Address::query()
                ->where('building_id', $buildingId)
                ->when(! $creating, fn(Builder $q) => $q->where('id', '!=', $model->id))
                ->update(['is_default' => false]);
        }

        $model->fill(Arr::only($data, $model->getFillable()));
        $model->save();

        return $model;
    }

    /**
     * Apply sorts
     * 
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    /**
     * Set the address as the default for its building.
     */
    public static function setDefault(Address $address): void
    {
        $address->building->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }
}
