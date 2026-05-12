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
                    v-model="productionStatusModel"
                    item-text="label"
                    item-value="value"
                    :items="productionStatusOptions"
                    :label="t('Stato produzione')"
                    clearable
                />
                <BbSelect
                    class="w-full sm:w-1/3 lg:w-1/6"
                    v-model="canceledModel"
                    item-text="label"
                    item-value="value"
                    :items="canceledOptions"
                    :label="t('Annullate')"
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

            <BbTable :columns="columns" item-value="id" :items="operations.data ?? []" :loading="loading" actions>
                <template #no-data>{{ t('Non ti è ancora stata assegnata nessuna lavorazione.') }}</template>
                <template #typology="{ item }">
                    <div class="flex items-center gap-1">
                        <PrescriptionTypologyBadge :typology="item.latest_prescription?.typology" size="xs" />
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
                <template #production_status="{ item }">
                    <span class="inline-flex flex-wrap items-center gap-1">
                        <ProductionStatusBadge :status="item.production_status" size="xs" verbose />
                        <span
                            v-if="item.canceled_at"
                            class="rounded border border-red-500 bg-red-200 px-2 py-0.5 text-xs font-medium text-red-700"
                        >
                            {{ t('Annullata') }}
                        </span>
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
import ProductionStatusBadge from '@/components/productions/ProductionStatusBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { Operation } from '@/types/Operation';
import type { Pagination } from '@/types/Pagination';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton, BbSelect, BbTable, type BbTableColumn, BbTextInput, BbTooltip } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Lavorazioni' }, () => [page]),
});

type SupplierOperation = Operation & {
    production_status: string | null;
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
        production_status: string | null;
        canceled_state: string | null;
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
    production_status: (queryParam('production_status') as string | undefined) ?? null,
    canceled_state: (queryParam('canceled_state') as string | undefined) ?? null,
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
const productionStatusModel = stringModel('production_status');
const canceledModel = stringModel('canceled_state');
const documentsModel = stringModel('documents_state');

const productionStatusOptions = [
    { value: 'null', label: t('Nessuna') },
    { value: 'confirmed', label: t('Confermata') },
    { value: 'canceled', label: t('Annullata') },
    { value: 'completed', label: t('Completata') },
];

const canceledOptions = [
    { value: 'only', label: t('Solo annullate') },
    { value: 'excluded', label: t('Escludi annullate') },
];

const documentsOptions = [
    { value: 'missing', label: t('Da caricare') },
    { value: 'uploaded', label: t('Caricati') },
];

const columns: BbTableColumn[] = [
    { key: 'typology', label: t('Tipologia') },
    {
        key: 'assigned_at',
        label: t('Assegnata il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
    { key: 'batch_number', label: t('Lotto'), formatter: (d) => d ?? '--' },
    { key: 'latest_prescription.ref', label: t('Riferimento'), formatter: (d) => d ?? '--' },
    { key: 'production_status', label: t('Stato produzione') },
    { key: 'supplier_documents_count', label: t('Documenti caricati'), formatter: (d) => String(d ?? 0) },
    {
        key: 'latest_prescription.expire_at',
        label: t('Scadenza'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
];
</script>
