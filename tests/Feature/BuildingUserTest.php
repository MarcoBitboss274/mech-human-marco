<?php

use App\Enums\BuildingUserRoleEnum;
use App\Models\Building;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can be attached to building with role', function () {
    $user = User::factory()->create();
    $building = Building::create([
        'name' => 'Test Building',
        'vat' => null,
        'is_studio' => null,
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => false,
    ]);

    $user->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);

    $this->assertDatabaseHas('building_user', [
        'user_id' => $user->id,
        'building_id' => $building->id,
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);
});

test('pivot table stores created_at and updated_at timestamps', function () {
    $user = User::factory()->create();
    $building = Building::create([
        'name' => 'Test Building',
        'vat' => null,
        'is_studio' => null,
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => false,
    ]);

    $user->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $pivotRow = \Illuminate\Support\Facades\DB::table('building_user')
        ->where('user_id', $user->id)
        ->where('building_id', $building->id)
        ->first();

    expect($pivotRow->created_at)->not->toBeNull();
    expect($pivotRow->updated_at)->not->toBeNull();
});
