<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div>
                <h1 class="page__title">{{ t('Ordini') }}</h1>
            </div>
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="filters.query"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra gli ordini')"
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
                    :items="orders.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessun ordine trovato') }}</template>
                    <template #status="{ item }">
                        <OperationOrderStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="router.get(route('orders.show', { order: item.id }))">
                                {{ t('Visualizza') }}
                            </BbButton>
                        </div>
                    </template>
                </BbTable>
            </ul>
        </div>

        <XPagination v-model="page" :disabled="loading" :per-page="orders.per_page" :total-items="orders.total" :total-pages="orders.last_page" />
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import OperationOrderStatusBadge from '@/components/operations/OperationOrderStatusBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Order } from '@/types/Order';
import { Pagination } from '@/types/Pagination';
import { currency } from '@/utils/formatters/currency';
import { router } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbTable, BbTextInput } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Ordini' }, () => [page]),
});

type Props = {
    orders: Pagination<Order>;
};

const props = defineProps<Props>();

const filtersDefault = {
    query: route().params.query ?? null,
};

const { loading, page, filters, resetFilters } = useIndexPage('/orders', filtersDefault, props.orders);

const tableContext = useTableContext<Order['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'id',
        label: 'ID',
    },
    {
        key: 'code',
        label: t('Codice'),
        formatter: (d) => d ?? '--',
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
        key: 'amount',
        label: t('Importo'),
        formatter: (d) => (d !== null && d !== undefined ? currency({ value: Number(d), minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '--'),
    },
    {
        key: 'confirmed_at',
        label: t('Confermato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);
</script>
