<script setup lang="ts">
import OperationOrderStatusBadge from '@/components/operations/OperationOrderStatusBadge.vue';
import type { Operation } from '@/types/Operation';
import type { Order } from '@/types/Order';
import { currency } from '@/utils/formatters/currency';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const orders = computed<Order[]>(() => props.operation.orders ?? []);
</script>

<template>
    <div class="operation-orders">
        <div class="operation-orders__header">
            <h2 class="operation-orders__title">{{ t('Ordini') }}</h2>
        </div>

        <div v-if="orders.length === 0" class="operation-orders__empty">
            {{ t('Nessun ordine presente') }}
        </div>

        <div v-else class="operation-orders__list">
            <article v-for="order in orders" :key="order.id" class="operation-orders__card">
                <div class="operation-orders__card-header">
                    <h3 class="operation-orders__card-title">{{ t('Ordine') }} {{ order.code ?? `#${order.id}` }}</h3>
                </div>

                <div class="operation-orders__status">
                    <OperationOrderStatusBadge :status="order.status" size="xs" />
                </div>

                <div class="operation-orders__meta">
                    <div class="operation-orders__meta-item">
                        <span class="operation-orders__meta-label">{{ t('Codice') }}</span>
                        <span>{{ order.code ?? '--' }}</span>
                    </div>
                    <div class="operation-orders__meta-item">
                        <span class="operation-orders__meta-label">{{ t('Importo') }}</span>
                        <span>
                            {{
                                order.amount !== null && order.amount !== undefined
                                    ? currency({ value: Number(order.amount), minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                    : '--'
                            }}
                        </span>
                    </div>
                    <div class="operation-orders__meta-item">
                        <span class="operation-orders__meta-label">{{ t('Descrizione') }}</span>
                        <span>{{ order.description ?? '--' }}</span>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operation-orders {
    @apply py-4;
}

.operation-orders__header {
    @apply mb-4 flex items-center justify-between gap-3;
}

.operation-orders__title {
    @apply text-lg font-semibold text-gray-900;
}

.operation-orders__empty {
    @apply py-10 text-center text-gray-500;
}

.operation-orders__list {
    @apply space-y-4;
}

.operation-orders__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-orders__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-orders__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-orders__status {
    @apply mt-3;
}

.operation-orders__meta {
    @apply mt-3 space-y-2;
}

.operation-orders__meta-item {
    @apply flex flex-col gap-1 text-sm text-gray-700;
}

.operation-orders__meta-label {
    @apply text-xs text-gray-500;
}
</style>
