<script setup lang="ts">
import QuoteCard from '@/components/operations/QuoteCard.vue';
import { useMainToast } from '@/composables/useMainToast';
import { useWorkspace } from '@/composables/useWorkspace';
import type { Operation } from '@/types/Operation';
import type { Quote } from '@/types/Quote';
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
            <QuoteCard
                v-for="quote in quotes"
                :key="quote.id"
                :quote="quote"
                mode="customer"
                :accepting-id="acceptingQuoteId"
                @accept="acceptQuote"
                @reject="openRejectModal"
            />
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

.operation-quotes__dialog {
    @apply flex flex-col gap-4;
}

.operation-quotes__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
