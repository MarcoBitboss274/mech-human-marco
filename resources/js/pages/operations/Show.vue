<template>
    <div class="admin-view boxed">
        <div class="admin-view__header">
            <div>
                <div class="flex flex-wrap items-center gap-4">
                    <h1 class="page__title">{{ t(props.operation.latest_prescription?.typology ?? '') }}</h1>
                    <OperationStatusBadge :id="isAdmin ? props.operation.id : null" :status="props.operation.status" size="sm" />
                    <div
                        v-if="activeRevisionOpenedAt"
                        class="flex w-fit items-center justify-center whitespace-nowrap rounded-md border !border-orange-500 !bg-orange-200 px-3 py-1 !text-sm leading-none !text-orange-700"
                    >
                        {{ t('In revisione dal') }}: {{ activeRevisionOpenedAt }}
                    </div>
                    <OperationCanceledBadge :canceled_at="props.operation.canceled_at" />
                    <OperationArchivedBadge :archived_at="props.operation.archived_at" />
                </div>

                <div class="flex items-center gap-2">
                    <span class="page__subtitle"
                        >{{ t('Lotto') }}: <span class="font-bold">{{ props.operation.batch_number ?? '--' }}</span></span
                    >
                    <span class="page__subtitle"
                        >{{ t('Riferimento') }}: <span class="font-bold">{{ props.operation.latest_prescription?.ref ?? '--' }}</span></span
                    >
                </div>
            </div>
            <div class="flex items-center gap-2">
                <BbButton v-if="props.chat.can_read" icon="chat" @click="chatSliderOpen = true">{{ t('Chat') }}</BbButton>
                <BbButton v-if="canShowCancel" icon="trash" :title="t('Cancella')" @click="openConfirmDialog('cancel')" />
                <BbButton v-if="canShowReactivate" icon="undo" :title="t('Riattiva')" @click="openConfirmDialog('reactivate')" />
                <BbButton v-if="canShowArchive" icon="archive" :title="t('Archivia')" @click="openConfirmDialog('archive')" />
                <BbButton v-if="canShowReopen" icon="folder-open" :title="t('Riapri')" @click="openConfirmDialog('reopen')" />
                <BbButton v-if="can('operations.activity.view')" icon="activity" @click="activitySliderOpen = true">{{ t('Attività') }}</BbButton>
                <BbButton icon="arrow-left" @click="router.get(route('operations.index'))">{{ t('Torna alla lista') }}</BbButton>
            </div>
        </div>

        <div>
            <OperationNotice :enable-hint="isAdmin" :operation="props.operation" @action="(action: string) => handleAction(action)" />
        </div>

        <div class="operations-show__status-section">
            <OperationProgress :operation="props.operation" />
        </div>

        <div class="operations-show__content">
            <BbTab v-model="tab" :items="tabs">
                <template #overview>
                    <OverviewTab
                        :operation="props.operation"
                        :overview="props.overview"
                        @change-tab="(key: string) => (tab = key)"
                        @operation:updated="reloadOperation"
                    />
                </template>
                <template v-if="can('operations.prescription.view')" #prescription>
                    <PrescriptionTab :operation="props.operation" @updated="reloadOperation" />
                </template>
                <template v-if="can('operations.supplier.view')" #supplier>
                    <SuppliersTab :operation="props.operation" />
                </template>
                <template v-if="can('operations.quote.view')" #quote>
                    <QuotesTab :operation="props.operation" @quote:updated="reloadOperation" />
                </template>
                <!-- <template v-if="can('operations.order.view')" #order>
                    <OrdersTab :operation="props.operation" @order:updated="reloadOperation" />
                </template> -->
                <template v-if="can('operations.production.view')" #production>
                    <ProductionsTab :operation="props.operation" @production:updated="reloadOperation" />
                </template>
                <template v-if="can('operations.invoice.view')" #invoice>
                    <InvoicesTab :operation="props.operation" @invoice:updated="reloadOperation" />
                </template>
            </BbTab>
        </div>

        <OperationChatTabbed
            v-model="chatSliderOpen"
            :operation-id="props.operation.id"
            :current-user-id="currentUserId"
            :can-read-customer="props.chat.can_read"
            :can-send-customer="props.chat.can_send"
            :can-read-supplier="props.chat.can_read_supplier"
            :can-send-supplier="props.chat.can_send_supplier"
            :has-supplier="!!hasSelectedSupplier"
        />

        <ActivitySlider v-model="activitySliderOpen" model-type="operation" :model-id="props.operation.id" />

        <BbDialog v-model="confirmDialogOpen" :title="t('Conferma')">
            <p class="mb-4 max-w-[520px]">
                {{ t('Sei sicuro di voler {action} questa lavorazione?', { action: confirmActionText }) }}
            </p>

            <div class="flex justify-end gap-2">
                <BbButton variant="secondary" @click="confirmDialogOpen = false">{{ t('Annulla') }}</BbButton>
                <BbButton variant="danger" @click="confirmOperationAction">{{ t('Conferma') }}</BbButton>
            </div>
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import ActivitySlider from '@/components/activity/ActivitySlider.vue';
import OperationChatTabbed from '@/components/chat/OperationChatTabbed.vue';
import OperationArchivedBadge from '@/components/operations/OperationArchivedBadge.vue';
import OperationCanceledBadge from '@/components/operations/OperationCanceledBadge.vue';
import OperationNotice from '@/components/operations/OperationNotice.vue';
import OperationProgress from '@/components/operations/OperationProgress.vue';
import OperationStatusBadge from '@/components/operations/OperationStatusBadge.vue';
import { useOperationStatus } from '@/composables/useOperationStatus';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import InvoicesTab from '@/pages/operations/partials/InvoicesTab.vue';
import OverviewTab from '@/pages/operations/partials/OverviewTab.vue';
import PrescriptionTab from '@/pages/operations/partials/PrescriptionTab.vue';
import ProductionsTab from '@/pages/operations/partials/ProductionsTab.vue';
import QuotesTab from '@/pages/operations/partials/QuotesTab.vue';
import SuppliersTab from '@/pages/operations/partials/SuppliersTab.vue';
import type { Operation } from '@/types/Operation';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbTab, type BbTabItem } from 'bitboss-ui';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Dettaglio lavorazione' }, () => [page]),
});

type OverviewPayload = {
    actors: {
        requester: { name: string | null; email: string | null } | null;
        building: { name: string | null; email: string | null } | null;
        agent: { name: string | null; email: string | null } | null;
        supplier: { name: string | null; email: string | null } | null;
    };
    summary: {
        prescription: { empty: boolean; count: number; status: string | null; updated_at: string | null; main_id: number | null };
        suppliers?: { empty: boolean; count: number; status: string | null; updated_at: string | null; main_id: number | null };
        quotes: { empty: boolean; count: number; status: string | null; updated_at: string | null; main_id: number | null };
        production: { empty: boolean; count: number; status: string | null; updated_at: string | null; main_id: number | null };
        invoices: { empty: boolean; count: number; status: string | null; updated_at: string | null; main_id: number | null };
    };
};

type Props = {
    operation: Operation;
    overview: OverviewPayload;
    chat: {
        can_read: boolean;
        can_send: boolean;
        can_read_supplier: boolean;
        can_send_supplier: boolean;
    };
};

const props = defineProps<Props>();

const { enableSuppliersTab, enableQuotesTab, enableOrdersTab, enableInvoicesTab } = useOperationStatus(() => props.operation);
const { can, isAdmin } = usePermissions();

const activeRevisionOpenedAt = computed(() => {
    const openedAt = props.operation.latest_prescription?.active_revision?.opened_at;
    return openedAt ? new Date(openedAt).toLocaleDateString('it-IT') : null;
});

const canShowCancel = computed(() => can('operations.cancel') && !!props.operation.can_be_canceled && !props.operation.canceled_at);
const canShowReactivate = computed(() => can('operations.cancel') && !!props.operation.can_be_canceled && !!props.operation.canceled_at);
const canShowArchive = computed(() => can('operations.archive') && !!props.operation.can_be_archived && !props.operation.archived_at);
const canShowReopen = computed(() => can('operations.archive') && !!props.operation.can_be_archived && !!props.operation.archived_at);

type OperationAction = 'cancel' | 'reactivate' | 'archive' | 'reopen';

const confirmDialogOpen = ref(false);
const confirmAction = ref<OperationAction | null>(null);

const confirmActionText = computed(() => {
    switch (confirmAction.value) {
        case 'cancel':
            return t('cancellare');
        case 'reactivate':
            return t('riattivare');
        case 'archive':
            return t('archiviare');
        case 'reopen':
            return t('riaprire');
        default:
            return '';
    }
});

const openConfirmDialog = (action: OperationAction) => {
    confirmAction.value = action;
    confirmDialogOpen.value = true;
};

const confirmOperationAction = () => {
    if (!confirmAction.value) return;

    const operationId = props.operation.id;

    if (confirmAction.value === 'cancel') {
        router.patch(route('operations.cancel', { operation: operationId }), {}, { preserveScroll: true });
    }
    if (confirmAction.value === 'reactivate') {
        router.patch(route('operations.reactivate', { operation: operationId }), {}, { preserveScroll: true });
    }
    if (confirmAction.value === 'archive') {
        router.patch(route('operations.archive', { operation: operationId }), {}, { preserveScroll: true });
    }
    if (confirmAction.value === 'reopen') {
        router.patch(route('operations.reopen', { operation: operationId }), {}, { preserveScroll: true });
    }

    confirmDialogOpen.value = false;
    confirmAction.value = null;
};

const reloadOperation = () => {
    router.reload({
        only: ['operation', 'overview'],
    });
};

const tab = ref<string>('overview');
const chatSliderOpen = ref(false);
const activitySliderOpen = ref(false);
const tabs = computed<BbTabItem[]>(() => [
    { key: 'overview', label: t('Overview') },
    ...(can('operations.prescription.view') ? [{ key: 'prescription', label: t('Prescrizione') }] : []),
    ...(can('operations.supplier.view') ? [{ key: 'supplier', label: t('Fornitore'), disabled: !enableSuppliersTab.value }] : []),
    ...(can('operations.quote.view') ? [{ key: 'quote', label: t('Preventivo'), disabled: !enableQuotesTab.value }] : []),
    // ...(can('operations.order.view') ? [{ key: 'order', label: t('Ordine'), disabled: !enableOrdersTab.value }] : []),
    ...(can('operations.production.view') ? [{ key: 'production', label: t('Produzione'), disabled: !enableOrdersTab.value }] : []),
    ...(can('operations.invoice.view') ? [{ key: 'invoice', label: t('Fattura'), disabled: !enableInvoicesTab.value }] : []),
]);

const handleAction = (action: string) => {
    if (action === 'send-prescription') tab.value = 'prescription';
    if (action === 'confirm-taking-over') tab.value = 'prescription';
    if (action === 'assign-supplier') tab.value = 'supplier';
    if (action === 'create-quote') tab.value = 'quote';
    if (action === 'send-quote') tab.value = 'quote';
    if (action === 'create-order') tab.value = 'order';
    if (action === 'confirm-order') tab.value = 'order';
    if (action === 'create-invoice') tab.value = 'invoice';
    if (action === 'send-invoice') tab.value = 'invoice';
    if (action === 'quote-sent') tab.value = 'quote';
    if (action === 'quote-rejected') tab.value = 'quote';
    if (action === 'create-production') tab.value = 'production';
    if (action === 'confirm-production') tab.value = 'production';
};

const page = usePage<any>();
const currentUserId = computed(() => page.props.auth.user?.id ?? 0);
const hasSelectedSupplier = computed(() => !!(props.operation as any).selected_supplier);

onMounted(() => {
    const query = new URLSearchParams(window.location.search);
    const shouldOpenChat = query.get('chat') === 'open';

    if (shouldOpenChat && props.chat.can_read) {
        chatSliderOpen.value = true;
    }
});
</script>

<style>
@reference '@/../css/base.css';

.operations-show__status-section {
    @apply mt-6;
}

.operations-show__content {
    @apply mt-6;
}

.operations-show__placeholder {
    @apply min-h-[8rem] py-4;
}
</style>
