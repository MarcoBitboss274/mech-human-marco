<script setup lang="ts">
import InvoiceCard from '@/components/operations/InvoiceCard.vue';
import type { Invoice } from '@/types/Invoice';
import type { Operation } from '@/types/Operation';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const invoices = computed<Invoice[]>(() => props.operation.invoices ?? []);
</script>

<template>
    <div class="operation-invoices">
        <div class="operation-invoices__header">
            <h2 class="operation-invoices__title">{{ t('Fatture') }}</h2>
        </div>

        <div v-if="invoices.length === 0" class="operation-invoices__empty">
            {{ t('Nessuna fattura presente') }}
        </div>

        <div v-else class="operation-invoices__list">
            <InvoiceCard v-for="invoice in invoices" :key="invoice.id" :invoice="invoice" mode="customer" />
        </div>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operation-invoices {
    @apply py-4;
}

.operation-invoices__header {
    @apply mb-4 flex items-center justify-between gap-3;
}

.operation-invoices__title {
    @apply text-lg font-semibold text-gray-900;
}

.operation-invoices__empty {
    @apply py-10 text-center text-gray-500;
}

.operation-invoices__list {
    @apply space-y-4;
}
</style>
