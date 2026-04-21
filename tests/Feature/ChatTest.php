<?php

use App\Enums\RoleEnum;
use App\Models\Operation;
use App\Models\OperationChatMessage;
use App\Models\User;
use App\Policies\OperationChatPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    config()->set('broadcasting.default', 'log');

    $this->adminRole = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'guard_name' => 'web',
    ]);

    Permission::query()->create(['name' => 'operations.index', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'chat.read', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'chat.send', 'guard_name' => 'web']);
});

function makeAdminUser(array $permissions = []): User
{
    $user = User::factory()->create();
    $user->assignRoleToUser(RoleEnum::ADMIN->value);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

test('cannot read operation chat messages without chat.read permission', function () {
    $user = makeAdminUser(['operations.index']);
    $operation = Operation::query()->create([
        'typology' => 'test',
        'status' => 'draft',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('operations.chat.messages', ['operation' => $operation->id]));

    $response->assertNotFound();
});

test('can send operation chat messages with chat.read and chat.send permissions', function () {
    $user = makeAdminUser(['operations.index', 'chat.read', 'chat.send']);
    $operation = Operation::query()->create([
        'typology' => 'test',
        'status' => 'draft',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('operations.chat.store', ['operation' => $operation->id]), [
            'body' => 'Nuovo messaggio',
        ]);

    $response->assertCreated();
    $response->assertJsonPath('message.body', 'Nuovo messaggio');
    $this->assertDatabaseHas('operation_chat_messages', [
        'operation_id' => $operation->id,
        'user_id' => $user->id,
        'body' => 'Nuovo messaggio',
    ]);
});

test('mark as read clears unread grouped counter only for current operation', function () {
    $receiver = makeAdminUser(['operations.index', 'chat.read']);
    $sender = makeAdminUser(['operations.index', 'chat.read', 'chat.send']);

    $operationOne = Operation::query()->create([
        'typology' => 'test-one',
        'status' => 'draft',
        'batch_number' => 'OP00001',
    ]);

    $operationTwo = Operation::query()->create([
        'typology' => 'test-two',
        'status' => 'draft',
        'batch_number' => 'OP00002',
    ]);

    OperationChatMessage::query()->create([
        'operation_id' => $operationOne->id,
        'user_id' => $sender->id,
        'body' => 'Messaggio 1',
    ]);
    OperationChatMessage::query()->create([
        'operation_id' => $operationTwo->id,
        'user_id' => $sender->id,
        'body' => 'Messaggio 2',
    ]);

    $unreadBefore = $this
        ->actingAs($receiver)
        ->get(route('chat.unread-by-operation'));

    $unreadBefore->assertOk();
    expect(collect($unreadBefore->json('items'))->pluck('operation_id')->all())
        ->toContain($operationOne->id, $operationTwo->id);

    $this
        ->actingAs($receiver)
        ->post(route('operations.chat.read', ['operation' => $operationOne->id]))
        ->assertOk();

    $unreadAfter = $this
        ->actingAs($receiver)
        ->get(route('chat.unread-by-operation'));

    $unreadAfter->assertOk();
    expect(collect($unreadAfter->json('items'))->pluck('operation_id')->all())
        ->not->toContain($operationOne->id)
        ->toContain($operationTwo->id);
});

test('broadcast policy authorization follows operation access and chat.read permission', function () {
    $allowedUser = makeAdminUser(['operations.index', 'chat.read']);
    $deniedUser = makeAdminUser([]);

    $operation = Operation::query()->create([
        'typology' => 'test',
        'status' => 'draft',
    ]);

    expect(app(OperationChatPolicy::class)->receiveBroadcast($allowedUser, $operation))->toBeTrue();
    expect(app(OperationChatPolicy::class)->receiveBroadcast($deniedUser, $operation))->toBeFalse();
});

