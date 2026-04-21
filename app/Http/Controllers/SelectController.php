<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Enums\OperationStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\OperationSupplierStatusEnum;
use App\Enums\SupplierStatusEnum;
use App\Enums\BuildingUserRoleEnum;
use App\Enums\PrescriptionGenderEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;

class SelectController extends Controller
{
    /**
     * Limit of results
     * 
     */
    private const LIMIT = 10;

    /**
     * Build the response
     * 
     */
    private function buildResponse($data)
    {
        return response()->json($data);
    }

    /**
     * Get the roles
     * 
     */
    public function roles()
    {
        // Exclude superadmin
        return $this->buildResponse(collect(RoleEnum::toArrayWithLabels())->where('value', '!=', RoleEnum::SUPERADMIN->value)->values()->toArray());
    }

    /**
     * Get the buildings
     * 
     */
    public function buildings(Request $request)
    {
        $prefill = $request->has('id');
        $query = Building::query()->select('id', 'name');

        if ($prefill) {
            $id = $request->input('id');
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search = $request->input('search')) {
                $query->where('name', 'like', "%{$search}%");
            }
            if ($excludeIds = $request->input('exclude_ids')) {
                $ids = is_array($excludeIds) ? $excludeIds : [$excludeIds];
                $query->whereNotIn('id', array_filter($ids));
            }
            $query->limit(self::LIMIT);
        }

        $items = $query->get()->map(fn($b) => ['value' => $b->id, 'label' => $b->name ?? (string) $b->id]);

        return $this->buildResponse($items);
    }

    /**
     * Get the operations
     * 
     */
    public function operations(Request $request)
    {
        $prefill = $request->has('id');
        $query = Operation::query()->select('id', 'typology', 'status');

        if ($prefill) {
            $id = $request->input('id');
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search = $request->input('search')) {
                $query->where('typology', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            }
            $query->limit(self::LIMIT);
        }

        $items = $query->get()->map(fn($o) => [
            'value' => $o->id,
            'label' => implode(' - ', array_filter([$o->typology, $o->status])) ?: (string) $o->id,
        ]);

        return $this->buildResponse($items);
    }

    /**
     * Get the users
     * 
     */
    public function users(Request $request)
    {
        $prefill = $request->has('id');
        $query = User::query()->select('id', 'name', 'surname', 'email');
        $buildingId = $request->input('building_id');

        if (!empty($buildingId)) {
            $query->whereHas('buildings', function ($buildingQuery) use ($buildingId) {
                $buildingQuery->where('buildings.id', $buildingId);
            });
        }

        if ($prefill) {
            $id = $request->input('id');
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
            $query->limit(self::LIMIT);
        }

        $items = $query->get()->map(fn($u) => [
            'value' => $u->id,
            'label' => implode(' ', array_filter([$u->name, $u->surname])) ?: $u->email ?? (string) $u->id,
        ]);

        return $this->buildResponse($items);
    }

    /**
     * Get the agents
     *
     */
    public function agents(Request $request)
    {
        $prefill = $request->has('id');
        $query = User::query()
            ->select('id', 'name', 'surname', 'email')
            ->where('role', RoleEnum::AGENT->value)
            ->where('active', true);

        if ($prefill) {
            $id = $request->input('id');
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
            $query->limit(self::LIMIT);
        }

        $items = $query->get()->map(fn($u) => [
            'value' => $u->id,
            'label' => implode(' ', array_filter([$u->name, $u->surname])) ?: $u->email ?? (string) $u->id,
        ]);

        return $this->buildResponse($items);
    }

    /**
     * Get the building user roles
     * 
     */
    public function buildingUserRoles()
    {
        return $this->buildResponse(BuildingUserRoleEnum::toArrayWithLabels());
    }

    /**
     * Get the prescription typologies
     * 
     */
    public function prescriptionTypologies()
    {
        return $this->buildResponse(PrescriptionTypologyEnum::toArrayWithLabels());
    }

    /**
     * Get the prescription statuses
     * 
     */
    public function prescriptionStatuses()
    {
        return $this->buildResponse(PrescriptionStatusEnum::toArrayWithLabels());
    }

    /**
     * Get the prescription genders
     * 
     */
    public function prescriptionGenders()
    {
        return $this->buildResponse(PrescriptionGenderEnum::toArrayWithLabels());
    }

    /**
     * Get the operation statuses
     * 
     */
    public function operationStatuses()
    {
        return $this->buildResponse(OperationStatusEnum::toArrayWithLabels());
    }

    /**
     * Get the operation supplier statuses.
     */
    public function operationSupplierStatuses()
    {
        return $this->buildResponse(OperationSupplierStatusEnum::toArrayWithLabels());
    }

    /**
     * Get the quote statuses.
     */
    public function quoteStatuses()
    {
        return $this->buildResponse(QuoteStatusEnum::toArrayWithLabels());
    }

    /**
     * Get the order statuses.
     */
    public function orderStatuses()
    {
        return $this->buildResponse(OrderStatusEnum::toArrayWithLabels());
    }

    /**
     * Get the invoice statuses.
     */
    public function invoiceStatuses()
    {
        return $this->buildResponse(InvoiceStatusEnum::toArrayWithLabels());
    }

    /**
     * Get the suppliers.
     */
    public function suppliers(Request $request)
    {
        $prefill = $request->has('id');
        $query = Supplier::query()
            ->select('id', 'name', 'vat')
            ->where('status', SupplierStatusEnum::ACTIVE->value);

        if ($prefill) {
            $id = $request->input('id');
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('vat', 'like', "%{$search}%");
                });
            }

            if ($excludeIds = $request->input('exclude_ids')) {
                $ids = is_array($excludeIds) ? $excludeIds : [$excludeIds];
                $query->whereNotIn('id', array_filter($ids));
            }

            $query->limit(self::LIMIT);
        }

        $items = $query->get()->map(fn($supplier) => [
            'value' => $supplier->id,
            'label' => implode(' - ', array_filter([$supplier->name, $supplier->vat])) ?: (string) $supplier->id,
        ]);

        return $this->buildResponse($items);
    }

    /**
     * Get the supplier statuses
     */
    public function supplierStatuses()
    {
        return $this->buildResponse(SupplierStatusEnum::toArrayWithLabels());
    }
}