<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <h1 class="workspace-view__title">{{ t('Dettaglio fattura') }}</h1>
            <BbButton icon="arrow-left" @click="backToIndex">{{ t('Torna alla lista') }}</BbButton>
        </div>

        <div class="invoices-show__content">
            <div class="invoices-show__details">
                <div class="invoices-show__details-grid">
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">ID</span>
                        <span class="invoices-show__value">{{ invoice.id }}</span>
                    </div>
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">{{ t('Lavorazione') }}</span>
                        <span class="invoices-show__value">{{ invoice.operation?.id ?? '--' }}</span>
                    </div>
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">{{ t('Stato') }}</span>
                        <OperationInvoiceStatusBadge :status="invoice.status" />
                    </div>
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">{{ t('File') }}</span>
                        <a v-if="invoice.invoiceFile?.id" :href="route('media.index', { media: invoice.invoiceFile?.id })" target="_blank">{{
                            invoice.invoiceFile?.name
                        }}</a>
                        <span v-else>--</span>
                    </div>
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">{{ t('Inviata il') }}</span>
                        <span class="invoices-show__value">{{ dateTime(invoice.sent_at) ?? '--' }}</span>
                    </div>
                    <div class="invoices-show__details-item invoices-show__details-item--full">
                        <span class="invoices-show__label">{{ t('Descrizione') }}</span>
                        <span class="invoices-show__value">{{ invoice.description ?? '--' }}</span>
                    </div>
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">{{ t('Creato il') }}</span>
                        <span class="invoices-show__value">{{ dateTime(invoice.created_at) ?? '--' }}</span>
                    </div>
                    <div class="invoices-show__details-item">
                        <span class="invoices-show__label">{{ t('Aggiornato il') }}</span>
                        <span class="invoices-show__value">{{ dateTime(invoice.updated_at) ?? '--' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import OperationInvoiceStatusBadge from '@/components/operations/OperationInvoiceStatusBadge.vue';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import type { Invoice } from '@/types/Invoice';
import { dateTime } from '@/utils/formatters/date';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Dettaglio fattura' }, () => [page]),
});

type Props = {
    invoice: Invoice;
};

defineProps<Props>();

const backToIndex = () => {
    router.get(
        route('workspace.invoices.index', {
            building: workspace.value?.slug,
        }),
    );
};
</script>

<style>
@reference '@/../css/base.css';

.invoices-show__content {
    @apply mt-6;
}

.invoices-show__details {
    @apply py-4;
}

.invoices-show__details-grid {
    @apply grid gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.invoices-show__details-item {
    @apply flex flex-col gap-1;
}

.invoices-show__details-item--full {
    @apply sm:col-span-2 lg:col-span-3;
}

.invoices-show__label {
    @apply text-sm font-medium text-gray-500;
}

.invoices-show__value {
    @apply text-gray-900;
}
</style>
