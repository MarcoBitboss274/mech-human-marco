<script setup lang="ts">
import type { Operation } from '@/types/Operation';
import { BbButton, BbIcon } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Props = {
    operation: Operation;
    scope?: 'admin' | 'workspace';
    enableAlert?: boolean;
    enableHint?: boolean;
};

type OperationNoticeItem = {
    key: string;
    title_it: string | null;
    title_en: string | null;
    message_it: string | null;
    message_en: string | null;
    action: (() => void) | null;
    action_cta: string | null;
    show: boolean | (() => boolean);
};

const props = withDefaults(defineProps<Props>(), {
    scope: 'admin',
    enableAlert: true,
    enableHint: true,
});

const emit = defineEmits(['action']);

const { locale, t } = useI18n();

const isEnglishLocale = computed(() => locale.value.toLowerCase().startsWith('en'));

const resolveLocalizedValue = (italianValue: string | null, englishValue: string | null): string => {
    const normalizedItalianValue = italianValue?.trim() ?? '';
    const normalizedEnglishValue = englishValue?.trim() ?? '';

    if (isEnglishLocale.value && normalizedEnglishValue.length > 0) {
        return normalizedEnglishValue;
    }

    if (normalizedItalianValue.length > 0) {
        return normalizedItalianValue;
    }

    return normalizedEnglishValue;
};

const resolveItemTitle = (item: OperationNoticeItem): string => resolveLocalizedValue(item.title_it, item.title_en);
const resolveItemMessage = (item: OperationNoticeItem): string => resolveLocalizedValue(item.message_it, item.message_en);

const alertItems = computed<OperationNoticeItem[]>(() => [
    {
        key: 'prescription-status-not-valid',
        title_it: 'Prescrizione in stato non valido',
        title_en: 'Prescription status is not valid',
        message_it: 'Stato attuale: Bozza. Invia la prescrizione per procedere con i passaggi successivi.',
        message_en: 'Current status: Draft. Send the prescription to proceed with the next steps.',
        action: null,
        action_cta: null,
        show: () => props.operation.status !== 'draft' && props.operation.latest_prescription?.status === 'draft',
    },
    {
        key: 'prescription-status-not-valid',
        title_it: 'Prescrizione in stato non valido',
        title_en: 'Prescription status is not valid',
        message_it: 'Stato attuale: Inviata. Conferma la prescrizione per procedere con i passaggi successivi.',
        message_en: 'Current status: Sent. Confirm the prescription to proceed with the next steps.',
        action: null,
        action_cta: null,
        show: () => !['draft', 'requested'].includes(props.operation.status ?? '') && props.operation.latest_prescription?.status === 'sent',
    },
    {
        key: 'quote-not-present',
        title_it: 'Preventivo non presente',
        title_en: 'Quote not present',
        message_it: 'Crea un preventivo.',
        message_en: 'Create a quote.',
        action: null,
        action_cta: null,
        show: () => ['waiting_approval', 'production', 'completed'].includes(props.operation.status ?? '') && !(props.operation.quotes?.length ?? 0),
    },
    {
        key: 'quote-not-sent',
        title_it: 'Preventivo non inviato',
        title_en: 'Quote not sent',
        message_it: 'Invia il preventivo.',
        message_en: 'Send the quote.',
        action: null,
        action_cta: null,
        show: () =>
            ['waiting_approval', 'production', 'completed'].includes(props.operation.status ?? '') &&
            (props.operation.quotes?.length ?? 0) > 0 &&
            !(props.operation.quotes?.some((quote) => quote.status === 'sent') ?? false) &&
            !(props.operation.quotes?.some((quote) => quote.status === 'accepted') ?? false),
    },
    {
        key: 'production-not-present',
        title_it: 'Produzione non confermata',
        title_en: 'Production not confirmed',
        message_it: 'Conferma la produzione.',
        message_en: 'Confirm the production.',
        action: null,
        action_cta: null,
        show: () =>
            ['production', 'completed'].includes(props.operation.status ?? '') &&
            !(props.operation.productions?.some((production) => ['confirmed', 'completed'].includes(production.status ?? '')) ?? false),
    },
    // {
    //     key: 'order-not-present',
    //     title_it: 'Ordine non presente',
    //     title_en: 'Order not present',
    //     message_it: 'Crea un ordine.',
    //     message_en: 'Create an order.',
    //     action: null,
    //     action_cta: null,
    //     show: () => ['production', 'completed'].includes(props.operation.status ?? '') && (props.operation.orders?.length ?? 0) === 0,
    // },
    // {
    //     key: 'order-not-confirmed',
    //     title_it: 'Ordine non confermato',
    //     title_en: 'Order not confirmed',
    //     message_it: "Conferma l'ordine.",
    //     message_en: 'Confirm the order.',
    //     action: null,
    //     action_cta: null,
    //     show: () =>
    //         ['production', 'completed'].includes(props.operation.status ?? '') &&
    //         (props.operation.orders?.length ?? 0) > 0 &&
    //         (props.operation.orders?.some((order) => order.status === 'pending') ?? false),
    // },
    {
        key: 'invoice-not-present',
        title_it: 'Fattura non presente',
        title_en: 'Invoice not present',
        message_it: 'Crea una fattura.',
        message_en: 'Create an invoice.',
        action: null,
        action_cta: null,
        show: () => ['completed'].includes(props.operation.status ?? '') && (props.operation.invoices?.length ?? 0) === 0,
    },
    {
        key: 'invoice-not-sent',
        title_it: 'Fattura non inviata',
        title_en: 'Invoice not sent',
        message_it: 'Invia la fattura.',
        message_en: 'Send the invoice.',
        action: null,
        action_cta: null,
        show: () =>
            ['completed'].includes(props.operation.status ?? '') &&
            (props.operation.invoices?.length ?? 0) > 0 &&
            !(props.operation.invoices?.some((invoice) => invoice.status === 'sent') ?? false),
    },
]);

const hintItems = computed<OperationNoticeItem[]>(() => [
    {
        key: 'send-prescription',
        title_it: props.scope === 'admin' ? 'Invia la prescrizione' : 'Invia la prescrizione',
        title_en: props.scope === 'admin' ? 'Send the prescription' : 'Send the prescription',
        message_it:
            props.scope === 'admin'
                ? 'Invia la prescrizione per procedere con i passaggi successivi.'
                : 'Invia la prescrizione per procedere con i passaggi successivi.',
        message_en:
            props.scope === 'admin'
                ? 'Send the prescription to proceed with the next steps.'
                : 'Send the prescription to proceed with the next steps.',
        action: () => emit('action', 'send-prescription'),
        action_cta: t('Invia prescrizione'),
        show: () => props.operation.status === 'draft' && props.operation.latest_prescription?.status === 'draft',
    },
    {
        key: 'confirm-taking-over',
        title_it: props.scope === 'admin' ? 'Conferma presa in carico' : 'Prescrizione in fase di verifica',
        title_en: props.scope === 'admin' ? 'Confirm the taking over' : 'Prescription in verification phase',
        message_it:
            props.scope === 'admin'
                ? 'Conferma la presa in carico per procedere con i passaggi successivi.'
                : 'Riceverai una notifica non appena la lavorazione verrà presa in carico.',
        message_en:
            props.scope === 'admin'
                ? 'Confirm the taking over to proceed with the next steps.'
                : 'You will receive a notification as soon as the operation is taken over.',
        action: props.scope === 'admin' ? () => emit('action', 'confirm-taking-over') : null,
        action_cta: t('Conferma presa in carico'),
        show: () => ['requested'].includes(props.operation.status ?? '') && props.operation.latest_prescription?.status === 'sent',
    },
    {
        key: 'assign-supplier',
        title_it: props.scope === 'admin' ? 'Assegna un fornitore' : 'Elaborazione della proposta commerciale',
        title_en: props.scope === 'admin' ? 'Assign a supplier' : 'Commercial proposal processing',
        message_it:
            props.scope === 'admin'
                ? 'Assegna un fornitore per procedere con i passaggi successivi.'
                : 'Riceverai una notifica non appena il preventivo sarà disponibile.',
        message_en:
            props.scope === 'admin'
                ? 'Assign a supplier to proceed with the next steps.'
                : 'You will receive a notification as soon as the commercial proposal is processed.',
        action: () => emit('action', 'assign-supplier'),
        action_cta: t('Assegna fornitore'),
        show: () =>
            ['in_progress'].includes(props.operation.status ?? '') &&
            !props.operation.selected_supplier &&
            props.operation.latest_prescription?.status === 'confirmed' &&
            props.scope === 'admin',
    },
    {
        key: 'create-quote',
        title_it: props.scope === 'admin' ? 'Crea un preventivo' : 'Elaborazione della proposta commerciale',
        title_en: props.scope === 'admin' ? 'Create a quote' : 'Commercial proposal processing',
        message_it:
            props.scope === 'admin'
                ? 'Crea un preventivo per procedere con i passaggi successivi.'
                : 'Riceverai una notifica non appena il preventivo sarà disponibile.',
        message_en:
            props.scope === 'admin'
                ? 'Create a quote to proceed with the next steps.'
                : 'You will receive a notification as soon as the quote is available.',
        action: props.scope === 'admin' ? () => emit('action', 'create-quote') : null,
        action_cta: t('Crea preventivo'),
        show: () =>
            ['in_progress'].includes(props.operation.status ?? '') &&
            (props.operation.quotes?.length ?? 0) === 0 &&
            !(props.operation.quotes?.some((quote) => quote.status === 'sent') ?? false) &&
            props.operation.latest_prescription?.status === 'confirmed',
    },
    {
        key: 'send-quote',
        title_it: props.scope === 'admin' ? 'Invia il preventivo' : 'Invia la proposta commerciale',
        title_en: props.scope === 'admin' ? 'Send the quote' : 'Send the commercial proposal',
        message_it:
            props.scope === 'admin'
                ? 'Invia il preventivo per procedere con i passaggi successivi.'
                : 'Riceverai una notifica non appena il preventivo sarà disponibile.',
        message_en:
            props.scope === 'admin'
                ? 'Send the quote to proceed with the next steps.'
                : 'You will receive a notification as soon as the quote is available.',
        action: () => emit('action', 'send-quote'),
        action_cta: t('Invia preventivo'),
        show: () =>
            props.operation.status === 'in_progress' &&
            (props.operation.quotes?.length ?? 0) > 0 &&
            (props.operation.quotes?.every((quote) => quote.status === 'draft') ?? false),
    },
    {
        key: 'quote-sent',
        title_it: props.scope === 'admin' ? 'Attesa accettazione cliente' : 'Preventivo disponibile',
        title_en: props.scope === 'admin' ? 'Waiting for customer approval' : 'Quote available',
        message_it:
            props.scope === 'admin'
                ? "Per procedere con l'ordine occorre attendere la risposta del cliente."
                : "Per procedere con l'ordine occorre accettare il preventivo fornito da Mech & Human.",
        message_en:
            props.scope === 'admin'
                ? "You must wait for the customer's response to proceed with the order."
                : 'You must accept the quote provided by Mech & Human to proceed with the order.',
        action: props.scope === 'admin' ? null : () => emit('action', 'quote-sent'),
        action_cta: t('Guarda'),
        show: () =>
            props.operation.status === 'waiting_approval' &&
            (props.operation.orders?.length ?? 0) === 0 &&
            !(props.operation.orders?.some((order) => order.status === 'sent') ?? false) &&
            !(props.operation.quotes?.some((quote) => quote.status === 'accepted') ?? false),
    },
    {
        key: 'quote-rejected',
        title_it: props.scope === 'admin' ? 'Preventivo rifiutato' : 'Preventivo rifiutato',
        title_en: props.scope === 'admin' ? 'Quote rejected' : 'Quote rejected',
        message_it: props.scope === 'admin' ? 'Il cliente ha rifiutato il preventivo' : 'Hai rifiutato il preventivo di Mech & Human. ',
        message_en:
            props.scope === 'admin'
                ? 'The customer has rejected the quote'
                : 'You have rejected the quote provided by Mech & Human. You can create a new quote.',
        action: () => emit('action', 'quote-rejected'),
        action_cta: t('Procedi'),
        show: () =>
            props.operation.status === 'waiting_approval' &&
            (props.operation.orders?.length ?? 0) === 0 &&
            !(props.operation.orders?.some((order) => order.status === 'sent') ?? false) &&
            (props.operation.quotes?.some((quote) => quote.status === 'rejected') ?? false),
    },
    {
        key: 'create-production',
        title_it: props.scope === 'admin' ? 'Conferma la produzione' : 'Preventivo accettato',
        title_en: props.scope === 'admin' ? 'Confirm the production' : 'Quote accepted',
        message_it:
            props.scope === 'admin'
                ? 'Conferma la produzione per procedere con i passaggi successivi.'
                : 'Hai accettato il preventivo di Mech & Human. Riceverai una notifica non appena sarà avviata la produzione.',
        message_en:
            props.scope === 'admin'
                ? 'Confirm the production to proceed with the next steps.'
                : 'You have accepted the quote provided by Mech & Human. You will receive a notification as soon as the production is started.',
        action: props.scope === 'admin' ? () => emit('action', 'create-production') : null,
        action_cta: t('Conferma produzione'),
        show: () =>
            props.operation.status === 'waiting_approval' &&
            (props.operation.productions?.length ?? 0) === 0 &&
            !(props.operation.productions?.some((production) => production.status === 'confirmed') ?? false) &&
            (props.operation.quotes?.some((quote) => quote.status === 'accepted') ?? false),
    },
    // {
    //     key: 'create-order',
    //     title_it: props.scope === 'admin' ? 'Crea un ordine' : 'Preventivo accettato',
    //     title_en: props.scope === 'admin' ? 'Create an order' : 'Quote accepted',
    //     message_it:
    //         props.scope === 'admin'
    //             ? 'Crea un ordine per procedere con i passaggi successivi.'
    //             : 'Hai accettato il preventivo di Mech & Human. Riceverai una notifica non appena sarà avviata la produzione.',
    //     message_en:
    //         props.scope === 'admin'
    //             ? 'Create an order to proceed with the next steps.'
    //             : 'You have accepted the quote provided by Mech & Human. You will receive a notification as soon as the production is started.',
    //     action: props.scope === 'admin' ? () => emit('action', 'create-order') : null,
    //     action_cta: t('Crea ordine'),
    //     show: () =>
    //         props.operation.status === 'waiting_approval' &&
    //         (props.operation.orders?.length ?? 0) === 0 &&
    //         !(props.operation.orders?.some((order) => order.status === 'sent') ?? false) &&
    //         (props.operation.quotes?.some((quote) => quote.status === 'accepted') ?? false),
    // },
    // {
    //     key: 'confirm-order',
    //     title_it: props.scope === 'admin' ? "Conferma l'ordine" : 'Preventivo accettato',
    //     title_en: props.scope === 'admin' ? 'Confirm the order' : 'Quote accepted',
    //     message_it:
    //         props.scope === 'admin'
    //             ? "Conferma l'ordine per procedere con i passaggi successivi."
    //             : 'Hai accettato il preventivo di Mech & Human. Riceverai una notifica non appena sarà avviata la produzione.',
    //     message_en:
    //         props.scope === 'admin'
    //             ? 'Confirm the order to proceed with the next steps.'
    //             : 'You have accepted the quote provided by Mech & Human. You will receive a notification as soon as the production is started.',
    //     action: props.scope === 'admin' ? () => emit('action', 'confirm-order') : null,
    //     action_cta: t('Conferma ordine'),
    //     show: () =>
    //         props.operation.status === 'waiting_approval' &&
    //         (props.operation.orders?.length ?? 0) > 0 &&
    //         (props.operation.orders?.some((order) => order.status === 'pending') ?? false) &&
    //         (props.operation.quotes?.some((quote) => quote.status === 'accepted') ?? false),
    // },
    {
        key: 'create-invoice',
        title_it: props.scope === 'admin' ? 'Crea la fattura' : 'Ordine in produzione',
        title_en: props.scope === 'admin' ? 'Create the invoice' : 'Order in production',
        message_it:
            props.scope === 'admin'
                ? 'Crea la fattura per procedere con i passaggi successivi.'
                : 'La tua lavorazione è in produzione. Riceverai una notifica alla conferma della spedizione.',
        message_en:
            props.scope === 'admin'
                ? 'Create the invoice to proceed with the next steps.'
                : 'Your operation is in production. You will receive a notification when the shipment is confirmed.',
        action: props.scope === 'admin' ? () => emit('action', 'create-invoice') : () => emit('action', 'order-in-production'),
        action_cta: props.scope === 'admin' ? t('Crea fattura') : t('Guarda'),
        show: () =>
            props.operation.status === 'production' &&
            (props.operation.invoices?.length ?? 0) === 0 &&
            !(props.operation.invoices?.some((invoice) => invoice.status === 'sent') ?? false),
    },
    {
        key: 'send-invoice',
        title_it: props.scope === 'admin' ? 'Invia la fattura' : 'Ordine in produzione',
        title_en: props.scope === 'admin' ? 'Send the invoice' : 'Order in production',
        message_it:
            props.scope === 'admin'
                ? 'Invia la fattura per procedere con i passaggi successivi.'
                : 'La tua lavorazione è in produzione. Riceverai una notifica alla conferma della spedizione.',
        message_en:
            props.scope === 'admin'
                ? 'Send the invoice to proceed with the next steps.'
                : 'Your operation is in production. You will receive a notification when the shipment is confirmed.',
        action: () => emit('action', 'send-invoice'),
        action_cta: t('Invia fattura'),
        show: () =>
            props.operation.status === 'production' &&
            (props.operation.invoices?.length ?? 0) > 0 &&
            (props.operation.invoices?.some((invoice) => invoice.status === 'draft') ?? false),
    },
    {
        key: 'operation-completed',
        title_it: props.scope === 'admin' ? 'Lavorazione completata' : 'Lavorazione completata',
        title_en: props.scope === 'admin' ? 'Operation completed' : 'Operation completed',
        message_it: props.scope === 'admin' ? 'La tua lavorazione è stata spedita.' : 'La tua lavorazione è stata spedita.',
        message_en: props.scope === 'admin' ? 'Your operation has been completed.' : 'Your operation has been completed.',
        action: props.scope === 'admin' ? null : () => emit('action', 'operation-completed'),
        action_cta: props.scope === 'admin' ? null : t('Visualizza fattura'),
        show: () => props.operation.status === 'completed',
    },
]);

const isItemVisible = (item: OperationNoticeItem): boolean => (typeof item.show === 'function' ? item.show() : item.show);
const visibleAlertItems = computed(() => {
    if (props.scope === 'workspace') {
        return alertItems.value.filter((item) => item.key.startsWith('prescription-')).filter(isItemVisible);
    }

    return alertItems.value.filter(isItemVisible);
});

const visibleHintItems = computed(() => {
    return hintItems.value.filter(isItemVisible);
});
const hasVisibleItems = computed(() => visibleAlertItems.value.length > 0 || visibleHintItems.value.length > 0);
</script>

<template>
    <div v-if="hasVisibleItems" class="operation-notice">
        <template v-if="enableAlert">
            <div v-for="item in visibleAlertItems" :key="`alert-${item.key}`" class="operation-notice__item operation-notice__item--alert">
                <div class="operation-notice__alert-left">
                    <BbIcon type="warning" class="operation-notice__alert-icon" size="sm" />
                    <p class="operation-notice__alert-title">{{ resolveItemTitle(item) }}</p>
                    <p class="operation-notice__alert-message">{{ resolveItemMessage(item) }}</p>
                </div>

                <BbButton v-if="item.action" class="operation-notice__alert-button" size="sm" @click="item.action">
                    {{ item.action_cta ?? t('Procedi') }}
                </BbButton>
            </div>
        </template>

        <template v-if="enableHint">
            <div v-for="item in visibleHintItems" :key="`hint-${item.key}`" class="operation-notice__item operation-notice__item--hint">
                <div class="operation-notice__hint-left">
                    <p class="operation-notice__hint-title">{{ resolveItemTitle(item) }}</p>
                    <p class="operation-notice__hint-message">{{ resolveItemMessage(item) }}</p>
                </div>

                <BbButton v-if="item.action" class="operation-notice__hint-button" size="sm" @click="item.action">
                    {{ item.action_cta ?? t('Procedi') }}
                </BbButton>
            </div>
        </template>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operation-notice {
    @apply flex w-full flex-col flex-wrap gap-3;
}

.operation-notice__item {
    @apply flex w-full flex-wrap items-center justify-between gap-4 rounded-md border;
}

.operation-notice__item--alert {
    @apply border-yellow-300 bg-yellow-50 px-2 py-1.5;
}

.operation-notice__item--hint {
    @apply border-sky-300 bg-sky-50 px-4 py-3;
}

.operation-notice__alert-left {
    @apply flex flex-wrap items-center gap-2;
}

.operation-notice__hint-left {
    @apply flex flex-col flex-wrap;
}

.operation-notice__alert-title {
    @apply text-sm font-semibold text-yellow-700;
}

.operation-notice__hint-title {
    @apply text-sm font-semibold text-sky-800;
}

.operation-notice__alert-icon {
    @apply m-0 p-0 text-yellow-700;
}

.operation-notice__hint-icon {
    @apply text-sky-800;
}

.operation-notice__alert-message {
    @apply text-sm text-yellow-600;
}

.operation-notice__hint-message {
    @apply mt-1 text-sm text-sky-700;
}

.operation-notice__alert-button {
    @apply self-center border-yellow-300 bg-yellow-100 text-sm font-normal text-yellow-600 hover:bg-yellow-200! hover:text-yellow-700!;
}

.operation-notice__hint-button {
    @apply self-center border-sky-300 bg-sky-100 text-sm font-normal text-sky-700 hover:bg-sky-200! hover:text-sky-700!;
}
</style>
