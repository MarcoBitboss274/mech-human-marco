<template>
    <div class="admin-view supplier-operations-index">
        <div class="admin-view__header">
            <h1 class="page__title">{{ t('Lavorazioni') }}</h1>
        </div>
        <p class="page__subtitle">{{ t('Lavorazioni assegnate alla tua azienda.') }}</p>

        <div class="suppliers-show__content">
            <div class="my-4 flex flex-wrap items-end gap-3">
                <BbTextInput
                    class="w-full sm:w-1/2 lg:w-1/4"
                    v-model="queryModel"
                    append:icon="lens"
                    clearable
                    :label="t('Cerca per riferimento o lotto')"
                />
                <BbTextInput
                    class="w-full sm:w-1/3 lg:w-1/6"
                    v-model="refModel"
                    clearable
                    :label="t('Riferimento')"
                />
                <BbTextInput
                    class="w-full sm:w-1/3 lg:w-1/6"
                    v-model="batchModel"
                    clearable
                    :label="t('Lotto')"
                />
                <BbSelect
                    class="w-full sm:w-1/3 lg:w-1/6"
                    v-model="statusModel"
                    item-text="label"
                    item-value="value"
                    :items="supplierStatusOptions"
                    :label="t('Stato')"
                    clearable
                />
                <BbSelect
                    class="w-full sm:w-1/3 lg:w-1/6"
                    v-model="documentsModel"
                    item-text="label"
                    item-value="value"
                    :items="documentsOptions"
                    :label="t('Stato documenti')"
                    clearable
                />
            </div>

            <BbTable :columns="columns" item-value="id" :items="operations.data ?? []" :loading="loading">
                <template #no-data>{{ t('Non ti è ancora stata assegnata nessuna lavorazione.') }}</template>
                <template #typology="{ item }">
                    <PrescriptionTypologyBadge :typology="item.latest_prescription?.typology" size="xs" />
                </template>
                <template #supplier_visible_status="{ item }">
                    <span class="supplier-status">{{ supplierStatusLabel(item.supplier_visible_status) }}</span>
                </template>
                <template #documents_state="{ item }">
                    <span :class="documentsBadgeClass(item)">
                        {{ (item.supplier_documents_count ?? 0) > 0 ? t('Caricati') : t('Da caricare') }}
                    </span>
                </template>
                <template #actions="{ item }">
                    <BbButton
                        icon="eye"
                        size="xs"
                        @click="router.get(route('workspace.supplier.operations.show', { operation: item.id }))"
                    >
                        {{ t('Visualizza') }}
                    </BbButton>
                </template>
            </BbTable>

            <XPagination
                v-model="page"
                :disabled="loading"
                :per-page="operations.per_page"
                :total-items="operations.total"
                :total-pages="operations.last_page"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { Operation } from '@/types/Operation';
import type { Pagination } from '@/types/Pagination';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton, BbSelect, BbTable, type BbTableColumn, BbTextInput } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Lavorazioni' }, () => [page]),
});

type SupplierOperation = Operation & {
    supplier_visible_status: string | null;
    supplier_documents_count?: number;
    assigned_at?: string | null;
};

type Props = {
    supplier: { id: number; name: string | null };
    operations: Pagination<SupplierOperation>;
    filters: {
        query: string | null;
        ref: string | null;
        batch_number: string | null;
        supplier_visible_status: string | null;
        documents_state: string | null;
    };
};

const props = defineProps<Props>();

const inertiaPage = usePage();
const queryParam = (key: string): unknown => {
    const idx = inertiaPage.url.indexOf('?');
    if (idx === -1) return undefined;
    return new URLSearchParams(inertiaPage.url.slice(idx + 1)).get(key) ?? undefined;
};

const filtersDefault = {
    query: (queryParam('query') as string | undefined) ?? null,
    ref: (queryParam('ref') as string | undefined) ?? null,
    batch_number: (queryParam('batch_number') as string | undefined) ?? null,
    supplier_visible_status: (queryParam('supplier_visible_status') as string | undefined) ?? null,
    documents_state: (queryParam('documents_state') as string | undefined) ?? null,
};

const { loading, page, filters } = useIndexPage('/workspace/supplier/operations', filtersDefault, props.operations);

const stringModel = (key: keyof typeof filtersDefault) =>
    computed<string | null>({
        get: () => (filters[key] == null || filters[key] === '' ? null : String(filters[key])),
        set: (value) => {
            filters[key] = value;
        },
    });

const queryModel = stringModel('query');
const refModel = stringModel('ref');
const batchModel = stringModel('batch_number');
const statusModel = stringModel('supplier_visible_status');
const documentsModel = stringModel('documents_state');

const supplierStatusLabels: Record<string, string> = {
    assigned_waiting_documents: t('Assegnata, in attesa documenti'),
    documents_sent: t('Documenti inviati, in valutazione M&H'),
    under_evaluation: t('In valutazione M&H'),
    production_confirmed: t('Produzione confermata'),
    completed: t('Completata'),
    canceled: t('Annullata'),
};

const supplierStatusLabel = (key: string | null | undefined): string => {
    if (!key) return '--';
    return supplierStatusLabels[key] ?? key;
};

const supplierStatusOptions = Object.entries(supplierStatusLabels).map(([value, label]) => ({ value, label }));

const documentsOptions = [
    { value: 'missing', label: t('Da caricare') },
    { value: 'uploaded', label: t('Caricati') },
];

const documentsBadgeClass = (item: SupplierOperation) =>
    (item.supplier_documents_count ?? 0) > 0
        ? 'inline-block rounded px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700'
        : 'inline-block rounded px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700';

const columns: BbTableColumn[] = [
    { key: 'typology', label: t('Tipologia') },
    { key: 'batch_number', label: t('Lotto'), formatter: (d) => d ?? '--' },
    { key: 'latest_prescription.ref', label: t('Riferimento'), formatter: (d) => d ?? '--' },
    { key: 'supplier_visible_status', label: t('Stato') },
    { key: 'documents_state', label: t('Stato documenti') },
    {
        key: 'latest_prescription.expire_at',
        label: t('Scadenza'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
    {
        key: 'assigned_at',
        label: t('Assegnata il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
];
</script>
