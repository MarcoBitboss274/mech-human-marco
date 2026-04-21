<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Services\AddressService;
use App\Http\Requests\Address\StoreAddressRequest;
use Illuminate\Http\RedirectResponse;

class AddressController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAddressRequest $request): RedirectResponse
    {
        AddressService::save(null, $request->validated());
        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreAddressRequest $request, Address $address): RedirectResponse
    {
        AddressService::save($address, $request->validated());
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address): RedirectResponse
    {
        AddressService::delete($address);
        return back();
    }

    /**
     * Set the address as default for its building.
     */
    public function setDefault(Address $address): RedirectResponse
    {
        AddressService::setDefault($address);
        return back();
    }
}