<template>
    <div class="supplier-operations-index">
        <h1 class="page__title">{{ t('Lavorazioni') }}</h1>
        <p class="page__subtitle">{{ t('Lavorazioni assegnate alla tua azienda.') }}</p>

        <div class="my-4 flex flex-wrap items-end gap-3">
            <BbTextInput
                class="w-full sm:w-1/2 lg:w-1/3"
                v-model="queryModel"
                append:icon="lens"
                clearable
                :label="t('Cerca per riferimento o lotto')"
            />
        </div>

        <BbTable :columns="columns" item-value="id" :items="operations.data ?? []" :loading="loading">
            <template #no-data>{{ t('Nessuna lavorazione assegnata') }}</template>
            <template #typology="{ item }">
                <PrescriptionTypologyBadge :typology="item.latest_prescription?.typology" size="xs" />
            </template>
            <template #supplier_visible_status="{ item }">
                <span class="supplier-status">{{ supplierStatusLabel(item.supplier_visible_status) }}</span>
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
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { Operation } from '@/types/Operation';
import type { Pagination } from '@/types/Pagination';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton, BbTable, type BbTableColumn, BbTextInput } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Lavorazioni' }, () => [page]),
});

type Props = {
    supplier: { id: number; name: string | null };
    operations: Pagination<Operation & { supplier_visible_status: string | null }>;
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
};

const { loading, page, filters } = useIndexPage('/workspace/supplier/operations', filtersDefault, props.operations);

const queryModel = computed<string | null>({
    get: () => (filters.query == null || filters.query === '' ? null : String(filters.query)),
    set: (value) => {
        filters.query = value;
    },
});

const columns: BbTableColumn[] = [
    { key: 'batch_number', label: t('Lotto'), formatter: (d) => d ?? '--' },
    { key: 'typology', label: t('Tipologia') },
    { key: 'latest_prescription.ref', label: t('Riferimento'), formatter: (d) => d ?? '--' },
    { key: 'supplier_visible_status', label: t('Stato') },
    {
        key: 'latest_prescription.expire_at',
        label: t('Scadenza'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
    {
        key: 'created_at',
        label: t('Creata il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
];

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
</script>
