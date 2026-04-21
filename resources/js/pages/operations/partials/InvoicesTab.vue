<script setup lang="ts">
import OperationInvoiceStatusBadge from '@/components/operations/OperationInvoiceStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import { useSelect } from '@/composables/useSelect';
import type { Invoice } from '@/types/Invoice';
import type { Operation } from '@/types/Operation';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbDropzone, BbPopover, BbSelect, BbTextInput } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { can } = usePermissions();
const { success, error } = useMainToast();
const { select: selectInvoiceStatuses } = useSelect('invoice-statuses');

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'invoice:updated'): void;
}>();

const invoices = computed<Invoice[]>(() => props.operation.invoices ?? []);
const sendingInvoiceId = ref<number | null>(null);

const modal = ref(false);
const selectedInvoice = ref<Invoice | null>(null);
const invoiceFile = ref<File | null>(null);
const form = useForm<{ description: string | null; file: File | null }>({
    description: null,
    file: null,
});

const statusModal = ref(false);
const selectedStatusInvoice = ref<Invoice | null>(null);
const statusForm = useForm<{ status: string | null }>({
    status: null,
});

const openModal = (invoice?: Invoice) => {
    selectedInvoice.value = invoice ?? null;
    form.clearErrors();
    form.description = invoice?.description ?? null;
    invoiceFile.value = null;
    form.file = null;
    modal.value = true;
};

const closeModal = () => {
    selectedInvoice.value = null;
    form.reset();
    form.clearErrors();
    invoiceFile.value = null;
    modal.value = false;
};

const saveInvoice = () => {
    form.transform((data) => {
        return {
            ...data,
            file: invoiceFile.value,
        };
    });

    console.log(form.file ?? 'null');

    if (selectedInvoice.value?.id) {
        router.post(
            route('operations.invoices.update', {
                operation: props.operation.id,
                invoice: selectedInvoice.value.id,
            }),
            {
                _method: 'put',
                description: form.description,
                file: invoiceFile?.value ?? null,
            },
            {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    closeModal();
                    success('Fattura aggiornata con successo');
                    emit('invoice:updated');
                },
                onError: () => {
                    error('Si è verificato un errore');
                },
            },
        );
        return;
    }

    form.post(route('operations.invoices.store', { operation: props.operation.id }), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            success('Fattura aggiunta con successo');
            emit('invoice:updated');
        },
        onError: () => {
            error('Si è verificato un errore');
        },
    });
};

const removeInvoice = (invoiceId: number) => {
    router.delete(
        route('operations.invoices.destroy', {
            operation: props.operation.id,
            invoice: invoiceId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Fattura eliminata con successo');
                emit('invoice:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const sendInvoice = (invoice: Invoice) => {
    if (!invoice.id || sendingInvoiceId.value !== null) {
        return;
    }

    sendingInvoiceId.value = invoice.id;
    router.post(
        route('invoices.send', {
            invoice: invoice.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Fattura inviata con successo');
                emit('invoice:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
            onFinish: () => {
                sendingInvoiceId.value = null;
            },
        },
    );
};

const openStatusModal = (invoice: Invoice) => {
    selectedStatusInvoice.value = invoice;
    statusForm.clearErrors();
    statusForm.status = invoice.status ?? 'draft';
    statusModal.value = true;
};

const closeStatusModal = () => {
    selectedStatusInvoice.value = null;
    statusForm.reset();
    statusForm.clearErrors();
    statusModal.value = false;
};

const saveStatus = () => {
    if (!selectedStatusInvoice.value?.id) {
        return;
    }

    statusForm.patch(
        route('operations.invoices.status', {
            operation: props.operation.id,
            invoice: selectedStatusInvoice.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeStatusModal();
                success('Stato fattura aggiornato con successo');
                emit('invoice:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};
</script>

<template>
    <div class="operation-invoices">
        <div class="operation-invoices__header">
            <h2 class="operation-invoices__title">{{ t('Fatture') }}</h2>
            <BbButton v-if="can('operations.invoice.manage')" append:icon="plus" size="xs" @click="openModal()">
                {{ t('Aggiungi fattura') }}
            </BbButton>
        </div>

        <div v-if="invoices.length === 0" class="operation-invoices__empty">
            {{ t('Nessuna fattura presente') }}
        </div>

        <div v-else class="operation-invoices__list">
            <article v-for="invoice in invoices" :key="invoice.id" class="operation-invoices__card">
                <div class="operation-invoices__card-header">
                    <h3 class="operation-invoices__card-title">{{ t('Fattura') }} {{ invoice.code ?? `#${invoice.id}` }}</h3>
                    <div class="operation-invoices__actions">
                        <BbButton v-if="can('operations.invoice.manage')" size="xs" icon="pencil" @click="openModal(invoice)">
                            {{ t('Modifica') }}
                        </BbButton>
                        <BbButton
                            v-if="can('operations.invoice.manage') && invoice.status === 'draft'"
                            size="xs"
                            append:icon="play"
                            :disabled="sendingInvoiceId === invoice.id"
                            @click="sendInvoice(invoice)"
                        >
                            {{ t('Invia') }}
                        </BbButton>
                        <BbPopover v-if="can('operations.invoice.manage')">
                            <template #activator="{ props }">
                                <BbButton size="xs" variant="danger" v-bind="props">
                                    {{ t('Elimina') }}
                                </BbButton>
                            </template>
                            <template #default="{ close }">
                                <p class="mb-2 max-w-[250px]">
                                    {{ t('Sei sicuro di voler eliminare') }}
                                    <strong> {{ t('Fattura') }} {{ invoice.code ?? `#${invoice.id}` }}?</strong>
                                </p>
                                <div class="text-right">
                                    <BbButton
                                        variant="danger"
                                        size="xs"
                                        @click="
                                            () => {
                                                removeInvoice(invoice.id);
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
                        v-if="can('operations.invoice.manage')"
                        type="button"
                        class="operation-invoices__status-button"
                        @click="openStatusModal(invoice)"
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
                        <a v-if="invoice.invoiceFile?.id" :href="route('media.index', { media: invoice.invoiceFile?.id })" target="_blank">{{
                            invoice.invoiceFile?.name
                        }}</a>
                        <span v-else>--</span>
                    </div>
                </div>
            </article>
        </div>

        <BbDialog v-model="modal" :title="selectedInvoice?.id ? t('Modifica fattura') : t('Aggiungi fattura')" size="md">
            <form class="operation-invoices__dialog" @submit.prevent="saveInvoice">
                <div>
                    <span class="mb-1 block text-sm font-medium text-gray-700">{{ t('File (PDF)') }}</span>
                    <BbDropzone v-slot="{ dragging }" v-model="invoiceFile" :accept="['application/pdf']">
                        <div
                            class="rounded-lg border border-dashed p-4 text-sm"
                            :class="dragging ? 'border-gray-400 bg-gray-50' : 'border-gray-300 bg-white'"
                        >
                            <div v-if="invoiceFile" class="flex items-center justify-between gap-3">
                                <span class="min-w-0 truncate">{{ invoiceFile.name }}</span>
                                <BbButton size="xs" variant="ghost" type="button" @click="invoiceFile = null">
                                    {{ t('Rimuovi') }}
                                </BbButton>
                            </div>
                            <div v-else class="text-gray-600">
                                {{ t('Clicca o trascina qui per caricare un PDF (max 10MB)') }}
                            </div>
                        </div>
                    </BbDropzone>
                    <p v-if="form.errors?.file" class="mt-1 text-sm text-red-600">
                        {{ form.errors.file }}
                    </p>
                </div>
                <BbTextInput v-model="form.description" autocomplete="off" :label="t('Note')" :errors="form.errors?.description" />

                <div class="operation-invoices__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeModal">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="form.processing">
                        {{ t('Salva') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="statusModal" :title="t('Aggiorna stato fattura')" size="sm">
            <form class="operation-invoices__dialog" @submit.prevent="saveStatus">
                <BbSelect
                    v-model="statusForm.status"
                    item-text="label"
                    item-value="value"
                    :items="selectInvoiceStatuses"
                    :label="t('Stato')"
                    :errors="statusForm.errors?.status"
                />
                <div class="operation-invoices__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeStatusModal">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="statusForm.processing">
                        {{ t('Salva') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>
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

.operation-invoices__dialog {
    @apply flex flex-col gap-4;
}

.operation-invoices__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
