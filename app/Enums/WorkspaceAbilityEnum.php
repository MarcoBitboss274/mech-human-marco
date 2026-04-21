<?php

namespace App\Enums;

enum WorkspaceAbilityEnum: string
{
    use BaseEnum;

    case BUILDING_VIEW = 'workspace.building.view';
    case BUILDING_UPDATE = 'workspace.building.update';
    case BUILDING_ADDRESSES_MANAGE = 'workspace.building.addresses.manage';

    case BUILDING_MEMBERS_INVITE = 'workspace.building.members.invite';
    case BUILDING_MEMBERS_EDIT = 'workspace.building.members.edit';
    case BUILDING_MEMBERS_REMOVE = 'workspace.building.members.remove';

    case OPERATIONS_VIEW = 'workspace.operations.view';
    case OPERATIONS_VIEW_ALL = 'workspace.operations.view-all';
    case OPERATIONS_CREATE = 'workspace.operations.create';
    case OPERATIONS_EDIT = 'workspace.operations.edit';

    case PRESCRIPTIONS_VIEW = 'workspace.prescriptions.view';
    case PRESCRIPTIONS_SEND = 'workspace.prescriptions.send';

    case QUOTES_VIEW = 'workspace.quotes.view';
    case QUOTES_ACCEPT = 'workspace.quotes.accept';
    case QUOTES_REJECT = 'workspace.quotes.reject';

    case ORDERS_VIEW = 'workspace.orders.view';
    case INVOICES_VIEW = 'workspace.invoices.view';
}