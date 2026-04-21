<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div>
                <h1 class="page__title">{{ t('Fatture') }}</h1>
            </div>
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="filters.query"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra le fatture')"
            />
            <BbButton icon="trash" @click="resetFilters">{{ t('Pulisci') }}</BbButton>
        </div>

        <div class="mb-6">
            <ul class="space-y-4">
                <BbTable
                    v-model="tableContext.selected"
                    v-model:select-all="tableContext.all"
                    v-model:unselected-items="tableContext.unselected"
                    :columns="columns"
                    item-value="id"
                    :items="invoices.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessuna fattura trovata') }}</template>
                    <template #status="{ item }">
                        <OperationInvoiceStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="router.get(route('invoices.show', { invoice: item.id }))">
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
            :per-page="invoices.per_page"
            :total-items="invoices.total"
            :total-pages="invoices.last_page"
        />
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import OperationInvoiceStatusBadge from '@/components/operations/OperationInvoiceStatusBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Invoice } from '@/types/Invoice';
import { Pagination } from '@/types/Pagination';
import { router } from '@inertiajs/vue3';
import { BbButton, BbTable, BbTextInput, type BbTableColumn } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Fatture' }, () => [page]),
});

type Props = {
    invoices: Pagination<Invoice>;
};

const props = defineProps<Props>();

const filtersDefault = {
    query: route().params.query ?? null,
};

const { loading, page, filters, resetFilters } = useIndexPage('/invoices', filtersDefault, props.invoices);

const tableContext = useTableContext<Invoice['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'id',
        label: 'ID',
    },
    {
        key: 'operation.batch_number',
        label: t('Lotto lavorazione'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'status',
        label: t('Stato'),
    },
    {
        key: 'sent_at',
        label: t('Inviata il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
    {
        key: 'created_at',
        label: t('Creato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);
</script>
