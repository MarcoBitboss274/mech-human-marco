<script setup lang="ts">
import InvoiceCard from '@/components/operations/InvoiceCard.vue';
import QuoteCard from '@/components/operations/QuoteCard.vue';
import ActorCard from '@/components/operations/overview/ActorCard.vue';
import OverviewSection from '@/components/operations/overview/OverviewSection.vue';
import { useMainToast } from '@/composables/useMainToast';
import { useWorkspace } from '@/composables/useWorkspace';
import type { Invoice } from '@/types/Invoice';
import type { Operation } from '@/types/Operation';
import type { OverviewPayload } from '@/types/Overview';
import type { Quote } from '@/types/Quote';
import { dateTime } from '@/utils/formatters/date';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbTextarea } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

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

const sentQuotes = computed<Quote[]>(() => {
    const ids = new Set(props.overview.summary.quotes.sent_ids);
    return (props.operation.quotes ?? []).filter((q) => q.id && ids.has(q.id));
});
const acceptedQuote = computed<Quote | null>(() => {
    const acceptedId = props.overview.summary.quotes.accepted?.id;
    if (!acceptedId) return null;
    return (props.operation.quotes ?? []).find((q) => q.id === acceptedId) ?? null;
});
const sentInvoices = computed<Invoice[]>(() => {
    const ids = new Set(props.overview.summary.invoices.sent_ids);
    return (props.operation.invoices ?? []).filter((i) => i.id && ids.has(i.id));
});

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

const productionEmptyLabel = computed(() => {
    const p = props.overview.summary.production;
    if (p.empty) return t('Nessuna produzione ancora');
    if (!p.confirmed_at && !p.canceled_at) return t('Non ancora confermata');
    return null;
});
</script>

<template>
    <div class="operations-overview">
        <div class="operations-overview__grid">
            <div class="operations-overview__summary-col">
                <h2 class="operations-overview__section-title">{{ t('Riepilogo lavorazione') }}</h2>

                <OverviewSection
                    :title="t('Prescrizione')"
                    :go-to-label="t('Vai a Prescrizione')"
                    @go-to="goToTab('prescription')"
                >
                    <template v-if="overview.summary.prescription.count > 1" #counters>
                        <span>{{ overview.summary.prescription.count }} {{ t('prescrizioni') }}</span>
                    </template>

                    <template v-if="overview.summary.prescription.empty">
                        <p class="operations-overview__empty">{{ t('Nessuna prescrizione ancora') }}</p>
                    </template>
                    <template v-else>
                        <p v-if="overview.summary.prescription.sent_at">
                            {{ t('Inviata il') }} <strong>{{ dateTime(overview.summary.prescription.sent_at) }}</strong>
                        </p>
                        <p v-if="overview.summary.prescription.confirmed_at">
                            {{ t('Confermata il') }} <strong>{{ dateTime(overview.summary.prescription.confirmed_at) }}</strong>
                        </p>
                    </template>
                </OverviewSection>

                <OverviewSection
                    :title="t('Preventivi')"
                    :go-to-label="t('Vai a Preventivi')"
                    @go-to="goToTab('quote')"
                >
                    <template v-if="overview.summary.quotes.rejected_count || overview.summary.quotes.canceled_count" #counters>
                        <span v-if="overview.summary.quotes.rejected_count">{{ overview.summary.quotes.rejected_count }} {{ t('rifiutati') }}</span>
                        <span v-if="overview.summary.quotes.rejected_count && overview.summary.quotes.canceled_count">·</span>
                        <span v-if="overview.summary.quotes.canceled_count">{{ overview.summary.quotes.canceled_count }} {{ t('annullati') }}</span>
                    </template>

                    <template v-if="overview.summary.quotes.empty">
                        <p class="operations-overview__empty">{{ t('Nessun preventivo ancora') }}</p>
                    </template>
                    <template v-else-if="sentQuotes.length">
                        <QuoteCard
                            v-for="quote in sentQuotes"
                            :key="quote.id"
                            :quote="quote"
                            mode="customer"
                            :accepting-id="acceptingQuoteId"
                            @accept="acceptQuote"
                            @reject="openRejectModal"
                        />
                    </template>
                    <template v-else-if="acceptedQuote">
                        <QuoteCard :quote="acceptedQuote" mode="customer" readonly />
                    </template>
                    <template v-else>
                        <p class="operations-overview__empty">{{ t('Nessun preventivo attivo') }}</p>
                    </template>
                </OverviewSection>

                <OverviewSection
                    :title="t('Produzione')"
                    :go-to-label="t('Vai a Produzione')"
                    @go-to="goToTab('production')"
                >
                    <template v-if="overview.summary.production.count > 1" #counters>
                        <span>{{ overview.summary.production.count }} {{ t('produzioni') }}</span>
                    </template>

                    <template v-if="productionEmptyLabel">
                        <p class="operations-overview__empty">{{ productionEmptyLabel }}</p>
                    </template>
                    <template v-else>
                        <p v-if="overview.summary.production.confirmed_at">
                            {{ t('Confermata il') }} <strong>{{ dateTime(overview.summary.production.confirmed_at) }}</strong>
                        </p>
                        <p v-if="overview.summary.production.canceled_at">
                            {{ t('Annullata il') }} <strong>{{ dateTime(overview.summary.production.canceled_at) }}</strong>
                        </p>
                    </template>
                </OverviewSection>

                <OverviewSection
                    :title="t('Fatture')"
                    :go-to-label="t('Vai a Fatture')"
                    @go-to="goToTab('invoice')"
                >
                    <template v-if="overview.summary.invoices.canceled_count" #counters>
                        <span>{{ overview.summary.invoices.canceled_count }} {{ t('annullate') }}</span>
                    </template>

                    <template v-if="overview.summary.invoices.empty">
                        <p class="operations-overview__empty">{{ t('Nessuna fattura ancora') }}</p>
                    </template>
                    <template v-else-if="sentInvoices.length">
                        <InvoiceCard v-for="invoice in sentInvoices" :key="invoice.id" :invoice="invoice" mode="customer" />
                    </template>
                    <template v-else>
                        <p class="operations-overview__empty">{{ t('Nessuna fattura attiva') }}</p>
                    </template>
                </OverviewSection>
            </div>

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

.operations-overview__summary-col {
    @apply flex flex-col;
}

.operations-overview__actors-col {
    @apply flex flex-col gap-4;
}

.operations-overview__section-title {
    @apply text-sm font-semibold uppercase tracking-wide text-gray-500;
}

.operations-overview__empty {
    @apply text-sm italic text-gray-400;
}

.operations-overview__dialog {
    @apply flex flex-col gap-4;
}

.operations-overview__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
