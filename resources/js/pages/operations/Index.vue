<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div class="">
                <h1 class="page__title">{{ t('Lavorazioni') }}</h1>
            </div>
            <div v-if="can('operations.create')" class="hidden lg:inline-flex">
                <BbButton prepend:icon="plus" @click="router.get(route('operations.create'))">{{ t('Nuova lavorazione') }}</BbButton>
            </div>
            <div class="lg:hidden">
                <BbDropdown v-if="can('operations.create')" :items="dropdownItems">
                    <template #activator="{ props }">
                        <BbButton icon="vertical_dots" v-bind="props">{{ t('Menu') }}</BbButton>
                    </template>
                    <template #item="{ text }">
                        <span class="flex gap-x-1">
                            <BbIcon type="plus" />
                            {{ text }}
                        </span>
                    </template>
                </BbDropdown>
            </div>
        </div>

        <div class="mt-4">
            <BbTab v-model="modeTab" :items="modeTabs" />
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="queryModel"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra le lavorazioni')"
            />
            <BbSelect
                v-model="buildingIdFilter"
                class="w-full sm:w-1/3 lg:w-1/4"
                item-text="label"
                item-value="value"
                :items="loadBuildingsFilter"
                :label="t('Struttura')"
                clearable
            />
            <BbSelect
                v-model="requesterIdFilter"
                class="w-full sm:w-1/3 lg:w-1/4"
                item-text="label"
                item-value="value"
                :items="loadRequestersFilter"
                :label="t('Richiedente')"
                clearable
            />
            <BbSelect
                v-model="prescriptionTypologyFilter"
                class="w-full sm:w-1/3 lg:w-1/5"
                item-text="label"
                item-value="value"
                :items="loadPrescriptionTypologiesFilter"
                :label="t('Tipologia')"
                clearable
            />
            <BbSelect
                v-model="statusFilter"
                class="w-full sm:w-1/3 lg:w-1/5"
                item-text="label"
                item-value="value"
                :items="loadOperationStatusesFilter"
                :label="t('Stato')"
                clearable
            />
            <BbSelect
                v-model="latestQuoteStatusFilter"
                class="w-full sm:w-1/3 lg:w-1/5"
                item-text="label"
                item-value="value"
                :items="loadQuoteStatusesFilter"
                :label="t('Preventivo')"
                clearable
            />
            <BbSelect
                v-if="can('operations.index.supplier')"
                v-model="supplierIdFilter"
                class="w-full sm:w-1/3 lg:w-1/4"
                item-text="label"
                item-value="value"
                :items="loadSuppliersFilter"
                :label="t('Fornitore')"
                clearable
            />
            <BbDatePickerInput
                v-model="expireAtRangeModel"
                class="w-full sm:w-1/2 lg:w-1/4"
                range
                clearable
                :label="t('Data di scadenza')"
                :allow-writing="'not-mobile'"
            />
            <BbDatePickerInput
                v-model="sendAtRangeModel"
                class="w-full sm:w-1/2 lg:w-1/4"
                range
                clearable
                :label="t('Data di invio')"
                :allow-writing="'not-mobile'"
            />
            <BbButton icon="trash" @click="resetAllFilters">{{ t('Pulisci') }}</BbButton>
        </div>

        <div class="mb-6">
            <ul class="space-y-4">
                <BbTable
                    v-model="tableContext.selected"
                    v-model:select-all="tableContext.all"
                    v-model:unselected-items="tableContext.unselected"
                    :columns="columns"
                    item-value="id"
                    :items="operations.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessuna lavorazione trovata') }}</template>
                    <template #typology="{ item }">
                        <PrescriptionTypologyBadge :typology="item.latest_prescription?.typology" size="xs" />
                    </template>
                    <template #status="{ item }">
                        <OperationStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #latest_quote_status="{ item }">
                        <OperationQuoteStatusBadge v-if="item.latest_quote_status" :status="item.latest_quote_status" size="xs" />
                        <span v-else>--</span>
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="router.get(route('operations.show', { operation: item.id }))">
                                {{ t('Visualizza') }}
                            </BbButton>
                            <BbButton
                                v-if="can('operations.cancel') && item.can_be_canceled"
                                icon="trash"
                                size="xs"
                                :title="t('Cancella')"
                                @click="openConfirmDialog('cancel', item)"
                            />
                            <BbButton
                                v-if="can('operations.cancel') && item.can_be_reactivated"
                                icon="undo"
                                size="xs"
                                :title="t('Riattiva')"
                                @click="openConfirmDialog('reactivate', item)"
                            />
                            <BbButton
                                v-if="can('operations.archive') && item.can_be_archived"
                                icon="archive"
                                size="xs"
                                :title="t('Archivia')"
                                @click="openConfirmDialog('archive', item)"
                            />
                            <BbButton
                                v-if="can('operations.archive') && item.can_be_reopened"
                                icon="folder-open"
                                size="xs"
                                :title="t('Riapri')"
                                @click="openConfirmDialog('reopen', item)"
                            />
                            <!-- <BbButton
                                v-if="can('operations.edit')"
                                icon="pencil_line"
                                size="xs"
                                @click="
                                    () => {
                                        selectedOperation = item;
                                        modal = true;
                                    }
                                "
                            >
                                {{ t('Modifica') }}
                            </BbButton> -->
                            <BbPopover ref="popover" v-if="can('operations.destroy') && item.can_be_deleted">
                                <template #activator="{ props }">
                                    <BbButton icon="trash" size="xs" v-bind="props">{{ t('Elimina') }}</BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler eliminare questa lavorazione?') }}
                                    </p>

                                    <div class="text-right">
                                        <BbButton
                                            variant="danger"
                                            size="xs"
                                            @click="
                                                () => {
                                                    deleteItem(item.id);
                                                    close();
                                                }
                                            "
                                            >{{ t('Elimina') }}</BbButton
                                        >
                                    </div>
                                </template>
                            </BbPopover>
                        </div>
                    </template>
                </BbTable>
            </ul>
        </div>

        <XPagination
            v-model="page"
            :disabled="loading"
            :per-page="operations.per_page"
            :total-items="operations.total"
            :total-pages="operations.last_page"
        />

        <BbDialog
            v-model="modal"
            :title="selectedOperation?.id ? t('Modifica lavorazione') : t('Aggiungi lavorazione')"
            size="lg"
            @hidden="
                () => {
                    selectedOperation = null;
                }
            "
        >
            <Form
                :operation="selectedOperation"
                @data:updated="
                    () => {
                        selectedOperation = null;
                        modal = false;
                        execute();
                    }
                "
            />
        </BbDialog>

        <BbDialog v-model="confirmDialogOpen" :title="t('Conferma')">
            <p class="mb-4 max-w-[520px]">
                {{ t('Sei sicuro di voler {action} questa lavorazione?', { action: confirmActionText }) }}
            </p>

            <div class="flex justify-end gap-2">
                <BbButton variant="secondary" @click="confirmDialogOpen = false">{{ t('Annulla') }}</BbButton>
                <BbButton variant="danger" @click="confirmOperationAction">{{ t('Conferma') }}</BbButton>
            </div>
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import OperationStatusBadge from '@/components/operations/OperationStatusBadge.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { usePermissions } from '@/composables/usePermissions';
import { useSelect } from '@/composables/useSelect';
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import Form from '@/pages/operations/partials/Form.vue';
import type { Operation } from '@/types/Operation';
import { Pagination } from '@/types/Pagination';
import { router, usePage } from '@inertiajs/vue3';
import type { BbTabItem, BbTableColumn } from 'bitboss-ui';
import {
    BbButton,
    BbDatePickerInput,
    BbDialog,
    BbDropdown,
    type BbDropdownItem,
    BbIcon,
    BbPopover,
    BbSelect,
    BbTab,
    BbTable,
    BbTextInput,
    useToast,
} from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Lavorazioni' }, () => [page]),
});

type Props = {
    operations: Pagination<Operation>;
};

const props = defineProps<Props>();

const { toast } = useToast();
const { can } = usePermissions();

const parseIdParam = (param: unknown): string | null => {
    if (param == null || param === '') return null;
    const n = typeof param === 'number' ? param : parseInt(String(param), 10);
    return Number.isFinite(n) && n > 0 ? String(n) : null;
};

const parseStringParam = (param: unknown): string | null => {
    if (param == null || param === '') return null;
    if (Array.isArray(param)) {
        const first = param.find((x) => x != null && String(x).trim() !== '');
        return first != null ? String(first).trim() : null;
    }
    const s = String(param).trim();
    return s || null;
};

const parseDateParam = (param: unknown): string | null => {
    const s = parseStringParam(param);
    return s;
};

const inertiaPage = usePage();

const queryParam = (key: string): unknown => {
    const idx = inertiaPage.url.indexOf('?');
    if (idx === -1) return undefined;
    const params = new URLSearchParams(inertiaPage.url.slice(idx + 1));
    const all = params.getAll(key);
    if (all.length === 0) return undefined;
    if (all.length === 1) return all[0];
    return all;
};

type OperationIndexMode = 'draft' | 'active' | 'archived';

const parseModeParam = (param: unknown): OperationIndexMode => {
    const value = parseStringParam(param);
    if (value === 'draft' || value === 'active' || value === 'archived') return value;
    return 'draft';
};

const filtersDefault = {
    mode: parseModeParam(queryParam('mode')),
    query: (queryParam('query') as string | undefined) ?? null,
    building_id: parseIdParam(queryParam('building_id')),
    requester_id: parseIdParam(queryParam('requester_id')),
    prescription_typology: parseStringParam(queryParam('prescription_typology')),
    status: parseStringParam(queryParam('status')),
    latest_quote_status: parseStringParam(queryParam('latest_quote_status')),
    supplier_id: parseIdParam(queryParam('supplier_id')),
    expire_at_from: parseDateParam(queryParam('expire_at_from')),
    expire_at_to: parseDateParam(queryParam('expire_at_to')),
    send_at_from: parseDateParam(queryParam('send_at_from')),
    send_at_to: parseDateParam(queryParam('send_at_to')),
};

const { loading, page, execute, filters, resetFilters } = useIndexPage('/operations', filtersDefault, props.operations);

const modeTab = computed<OperationIndexMode>({
    get: () => parseModeParam(filters.mode),
    set: (value) => {
        filters.mode = value;
    },
});

const modeTabs = ref<BbTabItem[]>([
    { key: 'draft', label: t('Bozze') },
    { key: 'active', label: t('Attive') },
    { key: 'archived', label: t('Archiviate') },
]);

const resetAllFilters = () => {
    resetFilters();
    filters.mode = 'draft';
};

const queryModel = computed<string | null>({
    get: () => (filters.query === null || filters.query === undefined ? null : String(filters.query)),
    set: (value) => {
        filters.query = value;
    },
});

const buildingIdFilter = computed<number | null>({
    get: () => {
        const v = filters.building_id;
        if (v === null || v === undefined || v === '') return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    },
    set: (v: number | null) => {
        filters.building_id = v != null && Number.isFinite(v) ? String(v) : null;
    },
});

const requesterIdFilter = computed<number | null>({
    get: () => {
        const v = filters.requester_id;
        if (v === null || v === undefined || v === '') return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    },
    set: (v: number | null) => {
        filters.requester_id = v != null && Number.isFinite(v) ? String(v) : null;
    },
});

const prescriptionTypologyFilter = computed<string | null>({
    get: () => (filters.prescription_typology != null && filters.prescription_typology !== '' ? String(filters.prescription_typology) : null),
    set: (v: string | null) => {
        filters.prescription_typology = v;
    },
});

const statusFilter = computed<string | null>({
    get: () => (filters.status != null && filters.status !== '' ? String(filters.status) : null),
    set: (v: string | null) => {
        filters.status = v;
    },
});

const latestQuoteStatusFilter = computed<string | null>({
    get: () => (filters.latest_quote_status != null && filters.latest_quote_status !== '' ? String(filters.latest_quote_status) : null),
    set: (v: string | null) => {
        filters.latest_quote_status = v;
    },
});

const supplierIdFilter = computed<number | null>({
    get: () => {
        const v = filters.supplier_id;
        if (v === null || v === undefined || v === '') return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    },
    set: (v: number | null) => {
        filters.supplier_id = v != null && Number.isFinite(v) ? String(v) : null;
    },
});

const expireAtRangeModel = computed<string[]>({
    get: () => {
        const from = filters.expire_at_from != null && filters.expire_at_from !== '' ? String(filters.expire_at_from) : null;
        const to = filters.expire_at_to != null && filters.expire_at_to !== '' ? String(filters.expire_at_to) : null;
        if (!from && !to) return [];
        return [from, to].filter((x): x is string => x != null && x !== '');
    },
    set: (v: string[] | null) => {
        if (!v || v.length === 0) {
            filters.expire_at_from = null;
            filters.expire_at_to = null;
            return;
        }
        const from = v[0] ? String(v[0]) : null;
        const to = v[1] ? String(v[1]) : null;
        filters.expire_at_from = from;
        filters.expire_at_to = to;
    },
});

const sendAtRangeModel = computed<string[]>({
    get: () => {
        const from = filters.send_at_from != null && filters.send_at_from !== '' ? String(filters.send_at_from) : null;
        const to = filters.send_at_to != null && filters.send_at_to !== '' ? String(filters.send_at_to) : null;
        if (!from && !to) return [];
        return [from, to].filter((x): x is string => x != null && x !== '');
    },
    set: (v: string[] | null) => {
        if (!v || v.length === 0) {
            filters.send_at_from = null;
            filters.send_at_to = null;
            return;
        }
        const from = v[0] ? String(v[0]) : null;
        const to = v[1] ? String(v[1]) : null;
        filters.send_at_from = from;
        filters.send_at_to = to;
    },
});

const { select: selectBuildings } = useSelect('buildings');
const { select: selectUsers } = useSelect('users');
const { select: selectPrescriptionTypologies } = useSelect('prescription-typologies');
const { select: selectOperationStatuses } = useSelect('operation-statuses');
const { select: selectQuoteStatuses } = useSelect('quote-statuses');
const { select: selectSuppliers } = useSelect('suppliers');

const loadBuildingsFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? buildingIdFilter.value) as number | string | string[] | number[] | null | undefined;
    return selectBuildings(query || null, prefill, mv ?? null);
};

const loadRequestersFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? requesterIdFilter.value) as number | string | string[] | number[] | null | undefined;
    const buildingId = buildingIdFilter.value;
    return selectUsers(query || null, prefill, mv ?? null, prefill ? null : buildingId ? { building_id: buildingId } : null);
};

const loadPrescriptionTypologiesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectPrescriptionTypologies(query || null, prefill, (modelValue ?? prescriptionTypologyFilter.value) as string | null);

const loadOperationStatusesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectOperationStatuses(query || null, prefill, (modelValue ?? statusFilter.value) as string | null);

const loadQuoteStatusesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectQuoteStatuses(query || null, prefill, (modelValue ?? latestQuoteStatusFilter.value) as string | null);

const loadSuppliersFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? supplierIdFilter.value) as number | string | string[] | number[] | null | undefined;
    return selectSuppliers(query || null, prefill, mv ?? null);
};

const tableContext = useTableContext<Operation['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'batch_number',
        label: t('Lotto'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'typology',
        label: t('Tipologia'),
    },
    {
        key: 'status',
        label: t('Stato'),
    },
    {
        key: 'latest_prescription.ref',
        label: t('Riferimento'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'latest_prescription.user.full_name',
        label: t('Richiedente'),
        formatter: (d) => d ?? '--',
    },

    {
        key: 'building.name',
        label: t('Struttura'),
        formatter: (d) => d ?? '--',
    },
    ...(can('operations.index.supplier')
        ? [
              {
                  key: 'selected_supplier_name',
                  label: t('Fornitore'),
                  formatter: (d: string | null) => d ?? '--',
              },
          ]
        : ([] as BbTableColumn[])),
    {
        key: 'latest_quote_status',
        label: t('Preventivo'),
    },
    ...(can('operations.index.sent_at')
        ? [
              {
                  key: 'latest_prescription.send_at',
                  label: t('Inviato il'),
                  formatter: (d: string | null) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
              },
          ]
        : ([] as BbTableColumn[])),
    {
        key: 'latest_prescription.expire_at',
        label: t('Scade il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
        tdClass: (d, i, item) => {
            return item.status === 'completed' ? 'line-through' : '';
        },
    },
    {
        key: 'created_at',
        label: t('Creato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);

const dropdownItems: BbDropdownItem[] = [
    {
        key: 'add-operation',
        text: t('Aggiungi lavorazione'),
        onClick: () => {
            router.get(route('operations.create'));
        },
    },
];

const modal = ref(false);
const selectedOperation = ref<Operation | null>(null);

type OperationAction = 'cancel' | 'reactivate' | 'archive' | 'reopen';

const confirmDialogOpen = ref(false);
const confirmAction = ref<OperationAction | null>(null);
const confirmOperation = ref<Operation | null>(null);

const confirmActionText = computed(() => {
    switch (confirmAction.value) {
        case 'cancel':
            return t('cancellare');
        case 'reactivate':
            return t('riattivare');
        case 'archive':
            return t('archiviare');
        case 'reopen':
            return t('riaprire');
        default:
            return '';
    }
});

const openConfirmDialog = (action: OperationAction, operation: Operation) => {
    confirmAction.value = action;
    confirmOperation.value = operation;
    confirmDialogOpen.value = true;
};

const confirmOperationAction = () => {
    if (!confirmAction.value || !confirmOperation.value) return;

    const operationId = confirmOperation.value.id;

    let routeName: string | null = null;
    if (confirmAction.value === 'cancel') routeName = 'operations.cancel';
    if (confirmAction.value === 'reactivate') routeName = 'operations.reactivate';
    if (confirmAction.value === 'archive') routeName = 'operations.archive';
    if (confirmAction.value === 'reopen') routeName = 'operations.reopen';
    if (!routeName) return;

    confirmDialogOpen.value = false;

    router.patch(
        route(routeName, { operation: operationId }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['operations'] });
            },
            onFinish: () => {
                confirmAction.value = null;
                confirmOperation.value = null;
            },
        },
    );
};

const deleteItem = async (id: Operation['id']) => {
    await router.delete(route('operations.destroy', { operation: id }), {
        onSuccess: () => {
            page.value = 1;
            toast({
                theme: 'success',
                text: t('Lavorazione eliminata con successo'),
            });
        },
    });
};
</script>
