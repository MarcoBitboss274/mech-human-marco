<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SelectController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    // Impersonate
    Route::impersonate();

    // Media
    Route::get('/media/{media}', [MediaController::class, 'index'])->name('media.index');

    // Dashboard
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    // Chat
    Route::get('/operations/{operation}/chat/messages', [ChatController::class, 'messages'])
        ->middleware('can:chat.read')
        ->name('operations.chat.messages');
    Route::post('/operations/{operation}/chat/messages', [ChatController::class, 'store'])
        ->middleware('can:chat.send')
        ->name('operations.chat.store');
    Route::post('/operations/{operation}/chat/read', [ChatController::class, 'markAsRead'])
        ->middleware('can:chat.read')
        ->name('operations.chat.read');
    Route::get('/chat/unread-by-operation', [ChatController::class, 'unreadByOperation'])
        ->middleware('can:chat.read')
        ->name('chat.unread-by-operation');

    // Selects workspace
    Route::prefix('/select')->name('select.')->group(function () {
        Route::get('/buildings', [SelectController::class, 'buildings'])->name('buildings');
        Route::get('/users', [SelectController::class, 'users'])->name('users');
        Route::get('/prescription-typologies', [SelectController::class, 'prescriptionTypologies'])->name('prescription-typologies');
        Route::get('/prescription-genders', [SelectController::class, 'prescriptionGenders'])->name('prescription-genders');
    });

    // Admin area
    Route::middleware(['role:superadmin|admin|agent'])->group(function () {

        // Profile
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Test
        Route::get('/test', [TestController::class, 'index'])->name('test.index');

        // Users
        Route::post('/users/{user}/invite', [UserController::class, 'invite'])->name('users.invite');
        Route::resource('users', UserController::class);

        // Buildings
        Route::get('/buildings/my', [BuildingController::class, 'my'])->name('buildings.my');
        Route::resource('buildings', BuildingController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('/buildings/{building}/members/invite', [BuildingController::class, 'inviteMember'])->name('buildings.members.invite');
        Route::put('/buildings/{building}/members/{user}', [BuildingController::class, 'updateMemberRole'])->name('buildings.members.edit');
        Route::delete('/buildings/{building}/members/{user}', [BuildingController::class, 'removeMember'])->name('buildings.members.delete');

        // Operations
        Route::get('/operations/export', [OperationController::class, 'export'])
            ->middleware('can:operations.export')
            ->name('operations.export');
        Route::get('/operations/export-all', [OperationController::class, 'exportAll'])
            ->middleware('can:operations.export')
            ->name('operations.export-all');
        Route::get('/operations/create', [OperationController::class, 'create'])->name('operations.create');
        Route::get('/operations/{operation}/edit-wizard', [OperationController::class, 'editWithPrescription'])->name('operations.edit-wizard');
        Route::post('/operations/store-wizard', [OperationController::class, 'storeWithPrescription'])->name('operations.store-wizard');
        Route::put('/operations/{operation}/update-wizard', [OperationController::class, 'updateWithPrescription'])->name('operations.update-wizard');
        Route::put('/operations/{operation}/prescriptions/{prescription}', [OperationController::class, 'updatePrescription'])->name('operations.prescriptions.update');
        Route::post('/operations/{operation}/suppliers', [OperationController::class, 'addSupplier'])->name('operations.suppliers.store');
        Route::patch('/operations/{operation}/suppliers/select', [OperationController::class, 'selectSupplier'])->name('operations.suppliers.select');
        Route::patch('/operations/{operation}/suppliers/status', [OperationController::class, 'updateSupplierStatus'])->name('operations.suppliers.status');
        Route::delete('/operations/{operation}/suppliers/{supplier}', [OperationController::class, 'removeSupplier'])->name('operations.suppliers.destroy');
        Route::post('/operations/{operation}/quotes', [OperationController::class, 'addQuote'])->name('operations.quotes.store');
        Route::put('/operations/{operation}/quotes/{quote}', [OperationController::class, 'updateQuote'])->name('operations.quotes.update');
        Route::patch('/operations/{operation}/quotes/{quote}/status', [OperationController::class, 'updateQuoteStatus'])->name('operations.quotes.status');
        Route::delete('/operations/{operation}/quotes/{quote}', [OperationController::class, 'removeQuote'])->name('operations.quotes.destroy');
        Route::post('/operations/{operation}/orders', [OperationController::class, 'addOrder'])->name('operations.orders.store');
        Route::put('/operations/{operation}/orders/{order}', [OperationController::class, 'updateOrder'])->name('operations.orders.update');
        Route::patch('/operations/{operation}/orders/{order}/status', [OperationController::class, 'updateOrderStatus'])->name('operations.orders.status');
        Route::delete('/operations/{operation}/orders/{order}', [OperationController::class, 'removeOrder'])->name('operations.orders.destroy');
        Route::post('/operations/{operation}/productions/confirm', [OperationController::class, 'confirmProduction'])->name('operations.productions.confirm');
        Route::post('/operations/{operation}/productions/cancel', [OperationController::class, 'cancelProduction'])->name('operations.productions.cancel');
        Route::post('/operations/{operation}/invoices', [OperationController::class, 'addInvoice'])->name('operations.invoices.store');
        Route::put('/operations/{operation}/invoices/{invoice}', [OperationController::class, 'updateInvoice'])->name('operations.invoices.update');
        Route::patch('/operations/{operation}/invoices/{invoice}/status', [OperationController::class, 'updateInvoiceStatus'])->name('operations.invoices.status');
        Route::delete('/operations/{operation}/invoices/{invoice}', [OperationController::class, 'removeInvoice'])->name('operations.invoices.destroy');
        Route::patch('/operations/{operation}/cancel', [OperationController::class, 'cancel'])
            ->middleware('can:operations.cancel')
            ->name('operations.cancel');
        Route::patch('/operations/{operation}/reactivate', [OperationController::class, 'reactivate'])
            ->middleware('can:operations.cancel')
            ->name('operations.reactivate');
        Route::patch('/operations/{operation}/archive', [OperationController::class, 'archive'])
            ->middleware('can:operations.archive')
            ->name('operations.archive');
        Route::patch('/operations/{operation}/reopen', [OperationController::class, 'reopen'])
            ->middleware('can:operations.archive')
            ->name('operations.reopen');
        Route::patch('/operations/{operation}/status', [OperationController::class, 'updateStatus'])->name('operations.status');
        Route::resource('operations', OperationController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

        // Activity log (admin only)
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        // Prescriptions
        Route::post('/prescriptions/{prescription}/send', [PrescriptionController::class, 'send'])->name('prescriptions.send');
        Route::post('/prescriptions/{prescription}/confirm', [PrescriptionController::class, 'confirm'])->name('prescriptions.confirm');
        Route::post('/prescriptions/{prescription}/revisions', [PrescriptionController::class, 'openRevision'])->name('prescriptions.revisions.open');
        Route::post('/prescriptions/{prescription}/revisions/reasons', [PrescriptionController::class, 'addRevisionReason'])->name('prescriptions.revisions.add-reason');
        Route::post('/prescriptions/{prescription}/revisions/close', [PrescriptionController::class, 'closeRevision'])->name('prescriptions.revisions.close');
        Route::resource('prescriptions', PrescriptionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

        // Quotes
        Route::post('/quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
        Route::post('/quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept');
        Route::post('/quotes/{quote}/reject', [QuoteController::class, 'reject'])->name('quotes.reject');
        Route::post('/quotes/{quote}/cancel', [QuoteController::class, 'cancel'])->name('quotes.cancel');
        Route::resource('quotes', QuoteController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

        // Orders
        Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::resource('orders', OrderController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

        // Productions
        Route::resource('productions', ProductionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

        // Invoices
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

        // Suppliers
        Route::resource('suppliers', SupplierController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('/suppliers/{supplier}/members/invite', [SupplierController::class, 'inviteMember'])
            ->middleware('can:suppliers.members.manage')
            ->name('suppliers.members.invite');
        Route::post('/suppliers/{supplier}/members', [SupplierController::class, 'attachMember'])
            ->middleware('can:suppliers.members.manage')
            ->name('suppliers.members.store');
        Route::put('/suppliers/{supplier}/members/{user}', [SupplierController::class, 'updateMember'])
            ->middleware('can:suppliers.members.manage')
            ->name('suppliers.members.update');
        Route::delete('/suppliers/{supplier}/members/{user}', [SupplierController::class, 'detachMember'])
            ->middleware('can:suppliers.members.manage')
            ->name('suppliers.members.destroy');

        // Addresses
        Route::post('/addresses/{address}/set-default', [AddressController::class, 'setDefault'])->name('addresses.set-default');
        Route::resource('addresses', AddressController::class)->only(['store', 'update', 'destroy']);

        // Selects admin
        Route::prefix('/select')->name('select.')->group(function () {
            Route::get('/roles', [SelectController::class, 'roles'])->name('roles');
            Route::get('/operations', [SelectController::class, 'operations'])->name('operations');
            Route::get('/building-user-roles', [SelectController::class, 'buildingUserRoles'])->name('building-user-roles');
            Route::get('/supplier-user-roles', [SelectController::class, 'supplierUserRoles'])->name('supplier-user-roles');
            Route::get('/prescription-statuses', [SelectController::class, 'prescriptionStatuses'])->name('prescription-statuses');
            Route::get('/operation-statuses', [SelectController::class, 'operationStatuses'])->name('operation-statuses');
            Route::get('/operation-supplier-statuses', [SelectController::class, 'operationSupplierStatuses'])->name('operation-supplier-statuses');
            Route::get('/quote-statuses', [SelectController::class, 'quoteStatuses'])->name('quote-statuses');
            Route::get('/order-statuses', [SelectController::class, 'orderStatuses'])->name('order-statuses');
            Route::get('/invoice-statuses', [SelectController::class, 'invoiceStatuses'])->name('invoice-statuses');
            Route::get('/suppliers', [SelectController::class, 'suppliers'])->name('suppliers');
            Route::get('/agents', [SelectController::class, 'agents'])->name('agents');
            Route::get('/supplier-statuses', [SelectController::class, 'supplierStatuses'])->name('supplier-statuses');
        });
    });

    // Workspace
    Route::middleware(['role:customer', 'onboarding'])
        ->prefix('/workspace')
        ->name('workspace.')
        ->group(function () {

            Route::get('/', [WorkspaceController::class, 'index'])->name('index');
            Route::get('/dashboard', [WorkspaceController::class, 'dashboard'])->name('dashboard');
            Route::get('/create-building', [WorkspaceController::class, 'createBuilding'])
                ->withoutMiddleware('onboarding')
                ->name('create-building');
            Route::post('/buildings', [WorkspaceController::class, 'storeBuilding'])
                ->withoutMiddleware('onboarding')
                ->name('buildings.store');
            Route::get('/profile', [WorkspaceController::class, 'profile'])->name('profile.index');
            Route::post('/profile', [WorkspaceController::class, 'updateProfile'])->name('profile.update');

            Route::middleware(['workspace'])
                ->prefix('/{building:slug}')
                ->group(function () {
                    Route::get('/', [WorkspaceController::class, 'buildingIndex'])->name('building.index');
                    Route::post('/members/invite', [WorkspaceController::class, 'inviteBuildingMember'])->name('building.members.invite');
                    Route::put('/', [WorkspaceController::class, 'updateBuilding'])->name('building.update');
                    Route::put('/members/{user}', [WorkspaceController::class, 'updateBuildingMemberRole'])->name('building.members.edit');
                    Route::delete('/members/{user}', [WorkspaceController::class, 'removeBuildingMember'])->name('building.members.destroy');
                    Route::post('/addresses', [WorkspaceController::class, 'storeAddress'])->name('building.addresses.store');
                    Route::put('/addresses/{address}', [WorkspaceController::class, 'updateAddress'])->name('building.addresses.update');
                    Route::delete('/addresses/{address}', [WorkspaceController::class, 'destroyAddress'])->name('building.addresses.destroy');
                    Route::post('/addresses/{address}/set-default', [WorkspaceController::class, 'setDefaultAddress'])->name('building.addresses.set-default');
                    Route::get('/prescriptions', [WorkspaceController::class, 'prescriptionsIndex'])->name('prescriptions.index');
                    Route::get('/prescriptions/{prescription}', [WorkspaceController::class, 'prescriptionsShow'])->name('prescriptions.show');
                    Route::post('/prescriptions/{prescription}/send', [WorkspaceController::class, 'prescriptionsSend'])->name('prescriptions.send');
                    Route::get('/quotes', [WorkspaceController::class, 'quotesIndex'])->name('quotes.index');
                    Route::get('/quotes/{quote}', [WorkspaceController::class, 'quotesShow'])->name('quotes.show');
                    Route::post('/quotes/{quote}/accept', [WorkspaceController::class, 'quotesAccept'])->name('quotes.accept');
                    Route::post('/quotes/{quote}/reject', [WorkspaceController::class, 'quotesReject'])->name('quotes.reject');
                    Route::get('/orders', [WorkspaceController::class, 'ordersIndex'])->name('orders.index');
                    Route::get('/orders/{order}', [WorkspaceController::class, 'ordersShow'])->name('orders.show');
                    Route::get('/invoices', [WorkspaceController::class, 'invoicesIndex'])->name('invoices.index');
                    Route::get('/invoices/{invoice}', [WorkspaceController::class, 'invoicesShow'])->name('invoices.show');
                    Route::get('/operations', [WorkspaceController::class, 'operationsIndex'])->name('operations.index');
                    Route::get('/operations/create', [WorkspaceController::class, 'operationsCreateWizard'])->name('operations.create');
                    Route::get('/operations/{operation}', [WorkspaceController::class, 'operationsShow'])->name('operations.show');
                    Route::get('/operations/{operation}/edit-wizard', [WorkspaceController::class, 'operationsEditWizard'])->name('operations.edit-wizard');
                    Route::post('/operations/store-wizard', [WorkspaceController::class, 'operationsStoreWizard'])->name('operations.store-wizard');
                    Route::put('/operations/{operation}/update-wizard', [WorkspaceController::class, 'operationsUpdateWizard'])->name('operations.update-wizard');
                });
        });

    // Workspace Supplier
    Route::middleware(['role:supplier'])
        ->prefix('/workspace/supplier')
        ->name('workspace.supplier.')
        ->group(function () {

            // Orphan landing — accessible without the supplier-team check (otherwise infinite redirect).
            Route::get('/orphan', [\App\Http\Controllers\WorkspaceSupplierController::class, 'orphan'])->name('orphan');

            Route::middleware(['workspace.supplier'])
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\WorkspaceSupplierController::class, 'index'])->name('index');
                    Route::get('/dashboard', [\App\Http\Controllers\WorkspaceSupplierController::class, 'dashboard'])->name('dashboard');
                    Route::get('/profile', [\App\Http\Controllers\WorkspaceSupplierController::class, 'profile'])->name('profile.index');
                    Route::post('/profile', [\App\Http\Controllers\WorkspaceSupplierController::class, 'updateProfile'])->name('profile.update');
                    Route::get('/settings', [\App\Http\Controllers\WorkspaceSupplierController::class, 'settings'])->name('settings.index');
                    Route::put('/settings', [\App\Http\Controllers\WorkspaceSupplierController::class, 'updateSettings'])->name('settings.update');
                    Route::get('/team', [\App\Http\Controllers\WorkspaceSupplierController::class, 'team'])->name('team.index');
                    Route::put('/team/{user}', [\App\Http\Controllers\WorkspaceSupplierController::class, 'updateTeamMember'])->name('team.update');
                    Route::delete('/team/{user}', [\App\Http\Controllers\WorkspaceSupplierController::class, 'removeTeamMember'])->name('team.destroy');
                    Route::get('/operations', [\App\Http\Controllers\WorkspaceSupplierController::class, 'operationsIndex'])->name('operations.index');
                    Route::get('/operations/{operation}', [\App\Http\Controllers\WorkspaceSupplierController::class, 'operationsShow'])->name('operations.show');
                });
        });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/examples.php';