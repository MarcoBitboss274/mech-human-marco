<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <div>
                <h1 class="workspace-view__title">{{ t('Lavorazioni') }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <BbButton prepend:icon="plus" @click="router.get(route('workspace.operations.create', { building: workspace?.slug }))">
                    {{ t('Nuova lavorazione') }}
                </BbButton>
            </div>
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
            <BbDatePickerInput
                v-model="sendAtRangeModel"
                class="w-full sm:w-1/2 lg:w-1/4"
                range
                clearable
                :label="t('Data di invio')"
                :allow-writing="'not-mobile'"
            />
            <BbDatePickerInput
                v-model="expireAtRangeModel"
                class="w-full sm:w-1/2 lg:w-1/4"
                range
                clearable
                :label="t('Data di scadenza')"
                :allow-writing="'not-mobile'"
            />
            <BbButton icon="trash" @click="resetFilters">{{ t('Pulisci') }}</BbButton>
        </div>

        <div class="mb-6">
            <ul class="space-y-4">
                <BbTable :columns="columns" item-value="id" :items="operations.data ?? []" :loading="loading" actions>
                    <template #no-data>{{ t('Nessuna lavorazione trovata') }}</template>
                    <template #typology="{ item }">
                        <PrescriptionTypologyBadge :typology="item.latest_prescription?.typology" size="xs" />
                    </template>
                    <template #status="{ item }">
                        <div class="flex items-center gap-1">
                            <OperationStatusBadge :status="item.status" size="xs" />
                            <BbTooltip v-if="item.latest_prescription?.active_revision">
                                <template #activator="{ props: tooltipProps }">
                                    <span
                                        v-bind="tooltipProps"
                                        class="text-base leading-none"
                                        role="img"
                                        :aria-label="t('Prescrizione in revisione')"
                                    >🔄</span>
                                </template>
                                {{ t('Prescrizione in revisione') }}
                            </BbTooltip>
                        </div>
                    </template>
                    <template #latest_quote_status="{ item }">
                        <OperationQuoteStatusBadge v-if="item.latest_quote_status" :status="item.latest_quote_status" size="xs" />
                        <span v-else>--</span>
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="openShow(item.id)">
                                {{ t('Visualizza') }}
                            </BbButton>
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
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import OperationStatusBadge from '@/components/operations/OperationStatusBadge.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { useSelect } from '@/composables/useSelect';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import type { Operation } from '@/types/Operation';
import { Pagination } from '@/types/Pagination';
import { router, usePage } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbDatePickerInput, BbSelect, BbTable, BbTextInput, BbTooltip } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Lavorazioni' }, () => [page]),
});

type Props = {
    operations: Pagination<Operation>;
};

const props = defineProps<Props>();

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

const filtersDefault = {
    query: (queryParam('query') as string | undefined) ?? null,
    requester_id: parseIdParam(queryParam('requester_id')),
    prescription_typology: parseStringParam(queryParam('prescription_typology')),
    status: parseStringParam(queryParam('status')),
    latest_quote_status: parseStringParam(queryParam('latest_quote_status')),
    expire_at_from: parseDateParam(queryParam('expire_at_from')),
    expire_at_to: parseDateParam(queryParam('expire_at_to')),
    send_at_from: parseDateParam(queryParam('send_at_from')),
    send_at_to: parseDateParam(queryParam('send_at_to')),
};

const indexRoute = route('workspace.operations.index', { building: workspace.value?.slug });
const { loading, page, filters, resetFilters } = useIndexPage(indexRoute, filtersDefault, props.operations);

const queryModel = computed<string | null>({
    get: () => (filters.query === null || filters.query === undefined ? null : String(filters.query)),
    set: (value) => {
        filters.query = value;
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

const { select: selectUsers } = useSelect('users');
const { select: selectPrescriptionTypologies } = useSelect('prescription-typologies');
const { select: selectOperationStatuses } = useSelect('operation-statuses');
const { select: selectQuoteStatuses } = useSelect('quote-statuses');

const loadRequestersFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? requesterIdFilter.value) as number | string | string[] | number[] | null | undefined;
    const buildingId = workspace.value?.id ?? null;
    return selectUsers(query || null, prefill, mv ?? null, prefill ? null : buildingId ? { building_id: buildingId } : null);
};

const loadPrescriptionTypologiesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectPrescriptionTypologies(query || null, prefill, (modelValue ?? prescriptionTypologyFilter.value) as string | null);

const loadOperationStatusesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectOperationStatuses(query || null, prefill, (modelValue ?? statusFilter.value) as string | null);

const loadQuoteStatusesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectQuoteStatuses(query || null, prefill, (modelValue ?? latestQuoteStatusFilter.value) as string | null);

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
        key: 'latest_quote_status',
        label: t('Preventivo'),
    },
    {
        key: 'latest_prescription.expire_at',
        label: t('Scade il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
    {
        key: 'created_at',
        label: t('Creato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);

const openShow = (id: Operation['id']) => {
    router.get(
        route('workspace.operations.show', {
            building: workspace.value?.slug,
            operation: id,
        }),
    );
};
</script>
