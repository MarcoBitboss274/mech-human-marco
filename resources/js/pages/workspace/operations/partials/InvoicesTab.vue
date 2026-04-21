<script setup lang="ts">
import OperationInvoiceStatusBadge from '@/components/operations/OperationInvoiceStatusBadge.vue';
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
            <article v-for="invoice in invoices" :key="invoice.id" class="operation-invoices__card">
                <div class="operation-invoices__card-header">
                    <h3 class="operation-invoices__card-title">{{ t('Fattura') }} {{ invoice.code ?? `#${invoice.id}` }}</h3>
                </div>

                <div class="operation-invoices__status">
                    <OperationInvoiceStatusBadge :status="invoice.status" size="xs" />
                </div>

                <div class="operation-invoices__meta">
                    <div class="operation-invoices__meta-item">
                        <span class="operation-invoices__meta-label">{{ t('Descrizione') }}</span>
                        <span>{{ invoice.description ?? '--' }}</span>
                    </div>
                    <div class="operation-invoices__meta-item">
                        <span class="operation-invoices__meta-label">{{ t('File') }}</span>
                        <a v-if="invoice.invoiceFile?.id" :href="route('media.index', { media: invoice.invoiceFile?.id })" target="_blank">{{
                            invoice.invoiceFile?.name
                        }}</a>
                        <span v-else>--</span>
                    </div>
                </div>
            </article>
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

.operation-invoices__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-invoices__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-invoices__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-invoices__status {
    @apply mt-3;
}

.operation-invoices__meta {
    @apply mt-3 space-y-2;
}

.operation-invoices__meta-item {
    @apply flex flex-col gap-1 text-sm text-gray-700;
}

.operation-invoices__meta-label {
    @apply text-xs text-gray-500;
}
</style>
