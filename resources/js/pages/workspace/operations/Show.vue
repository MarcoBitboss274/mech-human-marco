<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <div>
                <div class="flex flex-wrap items-center gap-4">
                    <h1 class="workspace-view__title">{{ t(operation.latest_prescription?.typology ?? '') }}</h1>
                    <OperationStatusBadge :status="operation.status" size="sm" />
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <span class="text-sm text-gray-600"
                        >{{ t('Lotto') }}: <span class="font-semibold text-gray-900">{{ operation.batch_number ?? '--' }}</span></span
                    >
                    <span class="text-sm text-gray-600"
                        >{{ t('Riferimento') }}:
                        <span class="font-semibold text-gray-900">{{ operation.latest_prescription?.ref ?? '--' }}</span></span
                    >
                </div>
            </div>
            <div class="flex items-center gap-2">
                <BbButton icon="chat" @click="chatSliderOpen = true">{{ t('Chat') }}</BbButton>
                <BbButton icon="arrow-left" @click="backToIndex">{{ t('Torna alla lista') }}</BbButton>
            </div>
        </div>

        <div class="mt-6">
            <OperationNotice scope="workspace" :operation="operation" @action="(action: string) => handleAction(action)" />
        </div>

        <div class="mt-6">
            <OperationProgress :operation="operation" />
        </div>

        <div class="mt-6">
            <BbTab v-model="tab" :items="tabs">
                <template #overview>
                    <OverviewTab
                        :operation="props.operation"
                        :overview="props.overview"
                        @change-tab="(key: string) => (tab = key)"
                        @operation:updated="reloadOperation"
                    />
                </template>
                <template #prescription>
                    <PrescriptionTab :operation="operation" @updated="reloadOperation" />
                </template>
                <template #quote>
                    <QuotesTab :operation="operation" @quote:updated="reloadOperation" />
                </template>
                <!-- <template #order>
                    <OrdersTab :operation="operation" />
                </template> -->
                <template #production>
                    <ProductionsTab :operation="operation" />
                </template>
                <template #invoice>
                    <InvoicesTab :operation="operation" />
                </template>
            </BbTab>
        </div>

        <ChatSlider v-model="chatSliderOpen" :operation-id="operation.id" :current-user-id="currentUserId" :can-read="true" :can-send="true" />
    </div>
</template>

<script setup lang="ts">
import ChatSlider from '@/components/chat/ChatSlider.vue';
import OperationNotice from '@/components/operations/OperationNotice.vue';
import OperationProgress from '@/components/operations/OperationProgress.vue';
import OperationStatusBadge from '@/components/operations/OperationStatusBadge.vue';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import InvoicesTab from '@/pages/workspace/operations/partials/InvoicesTab.vue';
import OverviewTab from '@/pages/workspace/operations/partials/OverviewTab.vue';
import PrescriptionTab from '@/pages/workspace/operations/partials/PrescriptionTab.vue';
import ProductionsTab from '@/pages/workspace/operations/partials/ProductionsTab.vue';
import QuotesTab from '@/pages/workspace/operations/partials/QuotesTab.vue';
import type { Operation } from '@/types/Operation';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton, BbTab, type BbTabItem } from 'bitboss-ui';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Dettaglio lavorazione' }, () => [page]),
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
};

const props = defineProps<Props>();

const backToIndex = () => {
    router.get(route('workspace.operations.index', { building: workspace.value?.slug }));
};

const reloadOperation = () => {
    router.reload({
        only: ['operation', 'overview'],
    });
};

const tab = ref<string>('overview');
const chatSliderOpen = ref(false);
type PageProps = {
    auth: {
        user?: {
            id: number;
        };
    };
};

const currentUserId = computed(() => usePage<PageProps>().props.auth.user?.id ?? 0);

const showQuoteTab = computed(() => true);
// const showOrderTab = computed(() => true);
const showInvoiceTab = computed(() => true);

const tabs = computed<BbTabItem[]>(() => [
    { key: 'overview', label: t('Overview') },
    { key: 'prescription', label: t('Prescrizione') },
    ...(showQuoteTab.value ? [{ key: 'quote', label: t('Preventivo') }] : []),
    // ...(showOrderTab.value ? [{ key: 'order', label: t('Ordine') }] : []),
    { key: 'production', label: t('Produzione') },
    ...(showInvoiceTab.value ? [{ key: 'invoice', label: t('Fattura') }] : []),
]);

const handleAction = (action: string) => {
    if (action === 'send-prescription') tab.value = 'prescription';
    if (action === 'quote-sent') tab.value = 'quote';
    if (action === 'quote-rejected') tab.value = 'quote';
    if (action === 'create-order') tab.value = 'order';
    if (action === 'confirm-order') tab.value = 'order';
    if (action === 'create-invoice') tab.value = 'invoice';
    if (action === 'send-invoice') tab.value = 'invoice';
    if (action === 'operation-completed') tab.value = 'invoice';
    if (action === 'order-in-production') tab.value = 'order';
    if (action === 'create-production') tab.value = 'production';
    if (action === 'confirm-production') tab.value = 'production';
};

onMounted(() => {
    const query = new URLSearchParams(window.location.search);
    const shouldOpenChat = query.get('chat') === 'open';

    if (shouldOpenChat) {
        chatSliderOpen.value = true;
    }
});
</script>

<style>
@reference '@/../css/base.css';

.operations-show__placeholder {
    @apply min-h-[8rem] py-4;
}
</style>
