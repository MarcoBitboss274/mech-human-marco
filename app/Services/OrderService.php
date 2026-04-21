<?php

namespace App\Services;

use App\Enums\OperationStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Services\OperationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderService extends ModelService
{
    /**
     * Get the class of the model.
     */
    protected static function getClass(): string
    {
        return Order::class;
    }

    /**
     * Fetch model.
     */
    public static function fetch(Request|null $request)
    {
        return static::getClass()::query()->with([
            'operation:id,typology,status',
        ]);
    }

    /**
     * Apply search.
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['code', 'status', 'description']);
        }

        return $query;
    }

    /**
     * Apply sorts.
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    /**
     * Save order with create defaults.
     */
    public static function save(Model|null $model, array $data = [])
    {
        if (! $model) {
            $data['code'] = $data['code'] ?? static::generateUniqueCode();
            $data['status'] = $data['status'] ?? OrderStatusEnum::PENDING->value;
        }

        return parent::save($model, $data);
    }

    /**
     * Confirm order status.
     */
    public static function confirm(Order $order): void
    {
        static::confirmOrder($order);
    }

    /**
     * Confirm order status.
     */
    public static function confirmOrder(Order $order): void
    {
        $order->update([
            'status' => OrderStatusEnum::CONFIRMED->value,
        ]);

        OperationService::updateStatus($order->operation, OperationStatusEnum::PRODUCTION->value);
    }

    /**
     * Get payload for admin order show page.
     */
    public static function getAdminShowData(Order $order): array
    {
        $order->load([
            'operation:id,typology,status',
        ]);

        return [
            'order' => $order->toArray(),
        ];
    }

    /**
     * Generate a unique order code in AAA12345 format.
     */
    private static function generateUniqueCode(): string
    {
        do {
            $letters = strtoupper(Str::random(3));
            $numbers = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $code = $letters . $numbers;
        } while (Order::query()->where('code', $code)->exists());

        return $code;
    }
}
