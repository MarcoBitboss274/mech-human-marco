<script setup lang="ts">
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import type { Operation } from '@/types/Operation';
import type { Quote } from '@/types/Quote';
import { dateTime } from '@/utils/formatters/date';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbDropzone, BbPopover, BbTextInput, BbTextarea } from 'bitboss-ui';
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { can } = usePermissions();
const { success, error } = useMainToast();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'quote:updated'): void;
}>();

const quotes = computed<Quote[]>(() => props.operation.quotes ?? []);

const modal = ref(false);
const selectedQuote = ref<Quote | null>(null);
const form = useForm<{ notes: string | null }>({
    notes: null,
});
const quoteUploads = reactive<{
    processingCostPdf: File | null;
    initialAndFinalStl: File | null;
    videoReport: File | null;
    orthoreportPdf: File | null;
    quotePdf: File | null;
    drillReportPdf: File | null;
    projectPdf: File | null;
}>({
    processingCostPdf: null,
    initialAndFinalStl: null,
    videoReport: null,
    orthoreportPdf: null,
    quotePdf: null,
    drillReportPdf: null,
    projectPdf: null,
});

const resetQuoteUploads = () => {
    quoteUploads.processingCostPdf = null;
    quoteUploads.initialAndFinalStl = null;
    quoteUploads.videoReport = null;
    quoteUploads.orthoreportPdf = null;
    quoteUploads.quotePdf = null;
    quoteUploads.drillReportPdf = null;
    quoteUploads.projectPdf = null;
};

const operationTypology = computed(() => props.operation.typology);
const sendingQuoteId = ref<number | null>(null);
const acceptingQuoteId = ref<number | null>(null);

const rejectModal = ref(false);
const selectedRejectQuote = ref<Quote | null>(null);
const rejectForm = useForm<{ notes: string }>({
    notes: '',
});

const cancelModal = ref(false);
const selectedCancelQuote = ref<Quote | null>(null);
const cancelForm = useForm<{ notes: string }>({
    notes: '',
});

const openModal = (quote?: Quote) => {
    selectedQuote.value = quote ?? null;
    form.clearErrors();
    form.notes = quote?.notes ?? null;
    resetQuoteUploads();
    modal.value = true;
};

const closeModal = () => {
    selectedQuote.value = null;
    form.reset();
    form.clearErrors();
    resetQuoteUploads();
    modal.value = false;
};

const saveQuote = () => {
    if (selectedQuote.value?.id) {
        form.put(
            route('operations.quotes.update', {
                operation: props.operation.id,
                quote: selectedQuote.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();
                    success('Preventivo aggiornato con successo');
                    emit('quote:updated');
                },
                onError: () => {
                    error('Si è verificato un errore');
                },
            },
        );
        return;
    }

    form.post(route('operations.quotes.store', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            success('Preventivo aggiunto con successo');
            emit('quote:updated');
        },
        onError: () => {
            error('Si è verificato un errore');
        },
    });
};

const removeQuote = (quoteId: number) => {
    router.delete(
        route('operations.quotes.destroy', {
            operation: props.operation.id,
            quote: quoteId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Preventivo eliminato con successo');
                emit('quote:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const sendQuote = (quote: Quote) => {
    if (!quote.id || sendingQuoteId.value !== null) {
        return;
    }

    sendingQuoteId.value = quote.id;
    router.post(
        route('quotes.send', {
            quote: quote.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Preventivo inviato con successo');
                emit('quote:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
            onFinish: () => {
                sendingQuoteId.value = null;
            },
        },
    );
};

const acceptQuote = (quote: Quote) => {
    if (!quote.id || acceptingQuoteId.value !== null) {
        return;
    }

    acceptingQuoteId.value = quote.id;
    router.post(
        route('quotes.accept', {
            quote: quote.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Preventivo accettato con successo');
                emit('quote:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
            onFinish: () => {
                acceptingQuoteId.value = null;
            },
        },
    );
};

const openRejectModal = (quote: Quote) => {
    selectedRejectQuote.value = quote;
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectModal.value = true;
};

const closeRejectModal = () => {
    selectedRejectQuote.value = null;
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectModal.value = false;
};

const rejectQuote = () => {
    if (!selectedRejectQuote.value?.id) {
        return;
    }

    rejectForm.post(
        route('quotes.reject', {
            quote: selectedRejectQuote.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRejectModal();
                success('Preventivo rifiutato con successo');
                emit('quote:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const openCancelModal = (quote: Quote) => {
    selectedCancelQuote.value = quote;
    cancelForm.reset();
    cancelForm.clearErrors();
    cancelModal.value = true;
};

const closeCancelModal = () => {
    selectedCancelQuote.value = null;
    cancelForm.reset();
    cancelForm.clearErrors();
    cancelModal.value = false;
};

const cancelQuote = () => {
    if (!selectedCancelQuote.value?.id) {
        return;
    }

    cancelForm.post(
        route('quotes.cancel', {
            quote: selectedCancelQuote.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeCancelModal();
                success('Preventivo annullato con successo');
                emit('quote:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};
</script>

<template>
    <div class="operation-quotes">
        <div class="operation-quotes__header">
            <h2 class="operation-quotes__title">{{ t('Preventivi') }}</h2>
            <BbButton v-if="can('operations.quote.manage')" append:icon="plus" size="xs" @click="openModal()">
                {{ t('Aggiungi preventivo') }}
            </BbButton>
        </div>

        <div v-if="quotes.length === 0" class="operation-quotes__empty">
            {{ t('Nessun preventivo presente') }}
        </div>

        <div v-else class="operation-quotes__list">
            <article v-for="quote in quotes" :key="quote.id" class="operation-quotes__card">
                <div class="operation-quotes__card-header">
                    <h3 class="operation-quotes__card-title">{{ t('Preventivo') }} {{ quote.id }}</h3>
                    <div class="operation-quotes__actions">
                        <BbButton v-if="can('operations.quote.manage')" size="xs" icon="pencil" @click="openModal(quote)">
                            {{ t('Modifica') }}
                        </BbButton>
                        <BbButton
                            v-if="can('operations.quote.manage') && quote.status === 'draft'"
                            size="xs"
                            append:icon="play"
                            :disabled="sendingQuoteId === quote.id"
                            @click="sendQuote(quote)"
                        >
                            {{ t('Invia') }}
                        </BbButton>
                        <BbButton
                            v-if="can('operations.quote.manage') && quote.status === 'sent'"
                            size="xs"
                            :disabled="acceptingQuoteId === quote.id"
                            @click="acceptQuote(quote)"
                        >
                            {{ t('Accetta') }}
                        </BbButton>
                        <BbButton
                            v-if="can('operations.quote.manage') && quote.status === 'sent'"
                            size="xs"
                            variant="outline"
                            @click="openRejectModal(quote)"
                        >
                            {{ t('Rifiuta') }}
                        </BbButton>
                        <BbButton
                            v-if="can('operations.quote.manage') && quote.status === 'sent'"
                            size="xs"
                            variant="outline"
                            @click="openCancelModal(quote)"
                        >
                            {{ t('Annulla') }}
                        </BbButton>
                        <BbPopover v-if="can('operations.quote.manage')">
                            <template #activator="{ props }">
                                <BbButton size="xs" variant="danger" v-bind="props">
                                    {{ t('Elimina') }}
                                </BbButton>
                            </template>
                            <template #default="{ close }">
                                <p class="mb-2 max-w-[250px]">
                                    {{ t('Sei sicuro di voler eliminare') }}
                                    <strong> {{ t('Preventivo') }} {{ quote.id }}?</strong>
                                </p>
                                <div class="text-right">
                                    <BbButton
                                        variant="danger"
                                        size="xs"
                                        @click="
                                            () => {
                                                removeQuote(quote.id);
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

                <div class="operation-quotes__status">
                    <OperationQuoteStatusBadge :status="quote.status" size="xs" />
                </div>

                <div class="operation-quotes__meta">
                    <div class="operation-quotes__meta-item">
                        <span class="operation-quotes__meta-label">{{ t('Accettato il') }}</span>
                        <span>{{ dateTime(quote.accepted_at) ?? '--' }}</span>
                    </div>
                    <div class="operation-quotes__meta-item">
                        <span class="operation-quotes__meta-label">{{ t('Note') }}</span>
                        <span>{{ quote.notes ?? '--' }}</span>
                    </div>
                </div>
            </article>
        </div>

        <BbDialog v-model="modal" :title="selectedQuote?.id ? t('Modifica preventivo') : t('Aggiungi preventivo')" size="md">
            <form class="operation-quotes__dialog" @submit.prevent="saveQuote">
                <BbTextInput v-model="form.notes" autocomplete="off" :label="t('Note')" :errors="form.errors?.notes" />

                <div class="operation-quotes__uploads">
                    <template v-if="operationTypology === 'protrusor'">
                        <div>
                            <span class="bb-label">{{ t('Costo di lavorazione PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.processingCostPdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.processingCostPdf
                                        ? t('File selezionato', { name: quoteUploads.processingCostPdf.name })
                                        : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                    </template>

                    <template v-else-if="operationTypology === 'lybra_aligner'">
                        <div>
                            <span class="bb-label">{{ t('Modelli iniziali e finali STL') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.initialAndFinalStl" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.initialAndFinalStl
                                        ? t('File selezionato', { name: quoteUploads.initialAndFinalStl.name })
                                        : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                        <div>
                            <span class="bb-label">{{ t('Video report') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.videoReport" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.videoReport ? t('File selezionato', { name: quoteUploads.videoReport.name }) : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                        <div>
                            <span class="bb-label">{{ t('Orthoreport PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.orthoreportPdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.orthoreportPdf
                                        ? t('File selezionato', { name: quoteUploads.orthoreportPdf.name })
                                        : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                        <div>
                            <span class="bb-label">{{ t('Preventivo PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.quotePdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.quotePdf ? t('File selezionato', { name: quoteUploads.quotePdf.name }) : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                    </template>

                    <template v-else-if="operationTypology === 'guided_surgery'">
                        <div>
                            <span class="bb-label">{{ t('Drill report PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.drillReportPdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.drillReportPdf
                                        ? t('File selezionato', { name: quoteUploads.drillReportPdf.name })
                                        : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                        <div>
                            <span class="bb-label">{{ t('Progetto PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.projectPdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.projectPdf ? t('File selezionato', { name: quoteUploads.projectPdf.name }) : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                        <div>
                            <span class="bb-label">{{ t('Preventivo PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.quotePdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.quotePdf ? t('File selezionato', { name: quoteUploads.quotePdf.name }) : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                    </template>

                    <template v-else-if="operationTypology === '3d_mesh'">
                        <div>
                            <span class="bb-label">{{ t('Video report') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.videoReport" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.videoReport ? t('File selezionato', { name: quoteUploads.videoReport.name }) : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                        <div>
                            <span class="bb-label">{{ t('Preventivo PDF') }}</span>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.quotePdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.quotePdf ? t('File selezionato', { name: quoteUploads.quotePdf.name }) : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                    </template>

                    <template v-else-if="operationTypology === 'prosthesis'">
                        <div>
                            <span class="bb-label">{{ t('Costo di lavorazione PDF') }}</span>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ t('Con specifica di maggiorazione costi con componentistica') }}
                            </p>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.processingCostPdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.processingCostPdf
                                        ? t('File selezionato', { name: quoteUploads.processingCostPdf.name })
                                        : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                    </template>

                    <template v-else-if="operationTypology === 'semi_finished_prostheses'">
                        <div>
                            <span class="bb-label">{{ t('Costo di lavorazione PDF') }}</span>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ t('Con specifica di maggiorazione costi con componentistica') }}
                            </p>
                            <BbDropzone v-slot="{ dragging }" v-model="quoteUploads.processingCostPdf" class="rounded-xl border border-dashed p-10 text-center">
                                <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                                <span v-else>{{
                                    quoteUploads.processingCostPdf
                                        ? t('File selezionato', { name: quoteUploads.processingCostPdf.name })
                                        : t('Seleziona un file')
                                }}</span>
                            </BbDropzone>
                        </div>
                    </template>
                </div>

                <div class="operation-quotes__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeModal">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="form.processing">
                        {{ t('Salva') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="rejectModal" :title="t('Rifiuta preventivo')" size="md">
            <form class="operation-quotes__dialog" @submit.prevent="rejectQuote">
                <BbTextarea v-model="rejectForm.notes" autocomplete="off" :label="t('Nota rifiuto')" :errors="rejectForm.errors?.notes" required />
                <div class="operation-quotes__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeRejectModal">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="rejectForm.processing || rejectForm.notes.trim() === ''">
                        {{ t('Rifiuta') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="cancelModal" :title="t('Annulla preventivo')" size="md">
            <form class="operation-quotes__dialog" @submit.prevent="cancelQuote">
                <BbTextarea v-model="cancelForm.notes" autocomplete="off" :label="t('Nota annullamento')" :errors="cancelForm.errors?.notes" required />
                <div class="operation-quotes__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeCancelModal">
                        {{ t('Indietro') }}
                    </BbButton>
                    <BbButton type="submit" variant="danger" :disabled="cancelForm.processing || cancelForm.notes.trim() === ''">
                        {{ t('Conferma annullamento') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operation-quotes {
    @apply py-4;
}

.operation-quotes__header {
    @apply mb-4 flex items-center justify-between gap-3;
}

.operation-quotes__title {
    @apply text-lg font-semibold text-gray-900;
}

.operation-quotes__empty {
    @apply py-10 text-center text-gray-500;
}

.operation-quotes__list {
    @apply space-y-4;
}

.operation-quotes__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-quotes__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-quotes__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-quotes__actions {
    @apply flex items-center gap-2;
}

.operation-quotes__status {
    @apply mt-3;
}

.operation-quotes__meta {
    @apply mt-3 space-y-2;
}

.operation-quotes__meta-item {
    @apply flex flex-col gap-1 text-sm text-gray-700;
}

.operation-quotes__meta-label {
    @apply text-xs text-gray-500;
}

.operation-quotes__dialog {
    @apply flex flex-col gap-4;
}

.operation-quotes__uploads {
    @apply flex flex-col gap-4;
}

.operation-quotes__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
