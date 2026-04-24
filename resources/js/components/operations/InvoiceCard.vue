<script setup lang="ts">
import OperationInvoiceStatusBadge from '@/components/operations/OperationInvoiceStatusBadge.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { Invoice } from '@/types/Invoice';
import { BbButton, BbPopover } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Mode = 'admin' | 'customer';

type Props = {
    invoice: Invoice;
    mode: Mode;
    sendingId?: number | null;
};

const props = withDefaults(defineProps<Props>(), {
    sendingId: null,
});

const emit = defineEmits<{
    (e: 'edit', invoice: Invoice): void;
    (e: 'send', invoice: Invoice): void;
    (e: 'delete', invoice: Invoice): void;
    (e: 'status-click', invoice: Invoice): void;
}>();

const { t } = useI18n();
const { can } = usePermissions();

const canManage = computed(() => props.mode === 'admin' && can('operations.invoice.manage'));
const title = computed(() => props.invoice.code ?? `#${props.invoice.id}`);
const fileId = computed(() => props.invoice.invoiceFile?.id);
const fileName = computed(() => props.invoice.invoiceFile?.name);
</script>

<template>
    <article class="operation-invoices__card">
        <div class="operation-invoices__card-header">
            <h3 class="operation-invoices__card-title">{{ t('Fattura') }} {{ title }}</h3>
            <div class="operation-invoices__actions">
                <BbButton v-if="canManage" size="xs" icon="pencil" @click="emit('edit', invoice)">
                    {{ t('Modifica') }}
                </BbButton>
                <BbButton
                    v-if="canManage && invoice.status === 'draft'"
                    size="xs"
                    append:icon="play"
                    :disabled="sendingId === invoice.id"
                    @click="emit('send', invoice)"
                >
                    {{ t('Invia') }}
                </BbButton>
                <BbPopover v-if="canManage">
                    <template #activator="{ props: popoverProps }">
                        <BbButton size="xs" variant="danger" v-bind="popoverProps">
                            {{ t('Elimina') }}
                        </BbButton>
                    </template>
                    <template #default="{ close }">
                        <p class="mb-2 max-w-[250px]">
                            {{ t('Sei sicuro di voler eliminare') }}
                            <strong> {{ t('Fattura') }} {{ title }}?</strong>
                        </p>
                        <div class="text-right">
                            <BbButton
                                variant="danger"
                                size="xs"
                                @click="
                                    () => {
                                        emit('delete', invoice);
                                        close();
                                    }
                                "
                            >
                                {{ t('Elimina') }}
                            </BbButton>
                        </div>
                    </template>
                </BbPopover>
            </div>
        </div>

        <div class="operation-invoices__status">
            <button
                v-if="canManage"
                type="button"
                class="operation-invoices__status-button"
                @click="emit('status-click', invoice)"
            >
                <OperationInvoiceStatusBadge :status="invoice.status" size="xs" />
            </button>
            <OperationInvoiceStatusBadge v-else :status="invoice.status" size="xs" />
        </div>

        <div class="operation-invoices__meta">
            <div class="operation-invoices__meta-item">
                <span class="operation-invoices__meta-label">{{ t('Note') }}</span>
                <span>{{ invoice.description ?? '--' }}</span>
            </div>
            <div class="operation-invoices__meta-item">
                <span class="operation-invoices__meta-label">{{ t('File') }}</span>
                <a v-if="fileId" :href="route('media.index', { media: fileId })" target="_blank">{{ fileName }}</a>
                <span v-else>--</span>
            </div>
        </div>
    </article>
</template>

<style>
@reference '@/../css/base.css';

.operation-invoices__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-invoices__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-invoices__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-invoices__actions {
    @apply flex items-center gap-2;
}

.operation-invoices__status {
    @apply mt-3;
}

.operation-invoices__status-button {
    @apply rounded-md;
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
