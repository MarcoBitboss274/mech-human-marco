<script setup lang="ts">
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { useWorkspace } from '@/composables/useWorkspace';
import type { Operation } from '@/types/Operation';
import type { Quote } from '@/types/Quote';
import { dateTime } from '@/utils/formatters/date';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbTextarea } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { success, error } = useMainToast();
const { workspace } = useWorkspace();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'quote:updated'): void;
}>();

const quotes = computed<Quote[]>(() => (props.operation.quotes ?? []).filter((quote) => quote.status !== 'draft'));

const acceptingQuoteId = ref<number | null>(null);

const rejectModal = ref(false);
const selectedRejectQuote = ref<Quote | null>(null);
const rejectForm = useForm<{ notes: string }>({
    notes: '',
});

const acceptQuote = (quote: Quote) => {
    if (!quote.id || acceptingQuoteId.value !== null) {
        return;
    }

    acceptingQuoteId.value = quote.id;
    router.post(
        route('workspace.quotes.accept', {
            building: workspace.value?.slug,
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
        route('workspace.quotes.reject', {
            building: workspace.value?.slug,
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
</script>

<template>
    <div class="operation-quotes">
        <div class="operation-quotes__header">
            <h2 class="operation-quotes__title">{{ t('Preventivi') }}</h2>
        </div>

        <div v-if="quotes.length === 0" class="operation-quotes__empty">
            {{ t('Nessun preventivo presente') }}
        </div>

        <div v-else class="operation-quotes__list">
            <article v-for="quote in quotes" :key="quote.id" class="operation-quotes__card">
                <div class="operation-quotes__card-header">
                    <h3 class="operation-quotes__card-title">{{ t('Preventivo') }} {{ quote.id }}</h3>
                    <div class="operation-quotes__actions">
                        <BbButton
                            v-if="quote.status === 'sent'"
                            size="xs"
                            :disabled="acceptingQuoteId === quote.id"
                            @click="acceptQuote(quote)"
                        >
                            {{ t('Accetta') }}
                        </BbButton>
                        <BbButton v-if="quote.status === 'sent'" size="xs" variant="outline" @click="openRejectModal(quote)">
                            {{ t('Rifiuta') }}
                        </BbButton>
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

.operation-quotes__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
