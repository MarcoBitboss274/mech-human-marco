<script setup lang="ts">
import InvoiceCard from '@/components/operations/InvoiceCard.vue';
import OperationInvoiceStatusBadge from '@/components/operations/OperationInvoiceStatusBadge.vue';
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import OperationStatusBadge from '@/components/operations/OperationStatusBadge.vue';
import QuoteCard from '@/components/operations/QuoteCard.vue';
import ActorCard from '@/components/operations/overview/ActorCard.vue';
import SummaryCard from '@/components/operations/overview/SummaryCard.vue';
import { useMainToast } from '@/composables/useMainToast';
import { useWorkspace } from '@/composables/useWorkspace';
import type { Operation } from '@/types/Operation';
import type { Quote } from '@/types/Quote';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbTextarea } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

type ActorPerson = { name: string | null; email: string | null } | null;
type SummaryData = {
    empty: boolean;
    count: number;
    status: string | null;
    updated_at: string | null;
    main_id: number | null;
};

type OverviewPayload = {
    actors: {
        requester: ActorPerson;
        building: ActorPerson;
        agent: ActorPerson;
        supplier: ActorPerson;
    };
    summary: {
        prescription: SummaryData;
        suppliers?: SummaryData;
        quotes: SummaryData;
        production: SummaryData;
        invoices: SummaryData;
    };
};

type Props = {
    operation: Operation;
    overview: OverviewPayload;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'change-tab', key: string): void;
    (e: 'operation:updated'): void;
}>();

const { t } = useI18n();
const { success, error } = useMainToast();
const { workspace } = useWorkspace();

const sentQuotes = computed<Quote[]>(() => (props.operation.quotes ?? []).filter((q) => q.status === 'sent'));
const sentInvoices = computed(() => (props.operation.invoices ?? []).filter((i) => i.status === 'sent'));

const acceptingQuoteId = ref<number | null>(null);
const rejectModal = ref(false);
const selectedRejectQuote = ref<Quote | null>(null);
const rejectForm = useForm<{ notes: string }>({ notes: '' });

const notifyUpdated = () => {
    router.reload({ only: ['operation', 'overview'] });
    emit('operation:updated');
};

const acceptQuote = (quote: Quote) => {
    if (!quote.id || acceptingQuoteId.value !== null) return;
    acceptingQuoteId.value = quote.id;
    router.post(
        route('workspace.quotes.accept', { building: workspace.value?.slug, quote: quote.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Preventivo accettato con successo');
                notifyUpdated();
            },
            onError: () => error('Si è verificato un errore'),
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

const submitReject = () => {
    if (!selectedRejectQuote.value?.id) return;
    rejectForm.post(
        route('workspace.quotes.reject', {
            building: workspace.value?.slug,
            quote: selectedRejectQuote.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectModal.value = false;
                selectedRejectQuote.value = null;
                success('Preventivo rifiutato con successo');
                notifyUpdated();
            },
            onError: () => error('Si è verificato un errore'),
        },
    );
};

const goToTab = (key: string) => emit('change-tab', key);
</script>

<template>
    <div class="operations-overview">
        <div class="operations-overview__grid">
            <section class="operations-overview__summary-col">
                <h2 class="operations-overview__section-title">{{ t('Riepilogo lavorazione') }}</h2>

                <SummaryCard
                    :title="t('Prescrizione')"
                    :empty="overview.summary.prescription.empty"
                    :count="overview.summary.prescription.count"
                    :updated-at="overview.summary.prescription.updated_at"
                    @click="goToTab('prescription')"
                >
                    <template #status>
                        <OperationStatusBadge v-if="overview.summary.prescription.status" :status="overview.summary.prescription.status" size="xs" />
                    </template>
                </SummaryCard>

                <SummaryCard
                    :title="t('Preventivi')"
                    :empty="overview.summary.quotes.empty"
                    :count="overview.summary.quotes.count"
                    :updated-at="overview.summary.quotes.updated_at"
                    @click="goToTab('quote')"
                >
                    <template #status>
                        <OperationQuoteStatusBadge v-if="overview.summary.quotes.status" :status="overview.summary.quotes.status" size="xs" />
                    </template>
                </SummaryCard>

                <div v-if="sentQuotes.length" class="operations-overview__inline">
                    <QuoteCard
                        v-for="quote in sentQuotes"
                        :key="quote.id"
                        :quote="quote"
                        mode="customer"
                        :accepting-id="acceptingQuoteId"
                        @accept="acceptQuote"
                        @reject="openRejectModal"
                    />
                </div>

                <SummaryCard
                    :title="t('Produzione')"
                    :empty="overview.summary.production.empty"
                    :count="overview.summary.production.count"
                    :updated-at="overview.summary.production.updated_at"
                    @click="goToTab('production')"
                >
                    <template #status>
                        <OperationStatusBadge v-if="overview.summary.production.status" :status="overview.summary.production.status" size="xs" />
                    </template>
                </SummaryCard>

                <SummaryCard
                    :title="t('Fatture')"
                    :empty="overview.summary.invoices.empty"
                    :count="overview.summary.invoices.count"
                    :updated-at="overview.summary.invoices.updated_at"
                    @click="goToTab('invoice')"
                >
                    <template #status>
                        <OperationInvoiceStatusBadge v-if="overview.summary.invoices.status" :status="overview.summary.invoices.status" size="xs" />
                    </template>
                </SummaryCard>

                <div v-if="sentInvoices.length" class="operations-overview__inline">
                    <InvoiceCard v-for="invoice in sentInvoices" :key="invoice.id" :invoice="invoice" mode="customer" />
                </div>
            </section>

            <aside class="operations-overview__actors-col">
                <h2 class="operations-overview__section-title">{{ t('Attori coinvolti') }}</h2>
                <ActorCard :role="t('Richiedente')" :person="overview.actors.requester" :placeholder="t('Non ancora definito')" />
                <ActorCard :role="t('Struttura')" :person="overview.actors.building" :placeholder="t('--')" />
            </aside>
        </div>

        <BbDialog v-model="rejectModal" :title="t('Rifiuta preventivo')" size="md">
            <form class="operations-overview__dialog" @submit.prevent="submitReject">
                <BbTextarea v-model="rejectForm.notes" autocomplete="off" :label="t('Nota rifiuto')" :errors="rejectForm.errors?.notes" required />
                <div class="operations-overview__dialog-actions">
                    <BbButton type="button" variant="outline" @click="rejectModal = false">{{ t('Annulla') }}</BbButton>
                    <BbButton type="submit" :disabled="rejectForm.processing || rejectForm.notes.trim() === ''">{{ t('Rifiuta') }}</BbButton>
                </div>
            </form>
        </BbDialog>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operations-overview {
    @apply py-4;
}

.operations-overview__grid {
    @apply grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px];
}

.operations-overview__summary-col,
.operations-overview__actors-col {
    @apply flex flex-col gap-4;
}

.operations-overview__section-title {
    @apply text-sm font-semibold uppercase tracking-wide text-gray-500;
}

.operations-overview__inline {
    @apply flex flex-col gap-4;
}

.operations-overview__dialog {
    @apply flex flex-col gap-4;
}

.operations-overview__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
