<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <h1 class="workspace-view__title">{{ t('Dettaglio ordine') }}</h1>
            <BbButton icon="arrow-left" @click="backToIndex">{{ t('Torna alla lista') }}</BbButton>
        </div>

        <div class="orders-show__content">
            <div class="orders-show__details">
                <div class="orders-show__details-grid">
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">ID</span>
                        <span class="orders-show__value">{{ order.id }}</span>
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Codice') }}</span>
                        <span class="orders-show__value">{{ order.code ?? '--' }}</span>
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Lavorazione') }}</span>
                        <span class="orders-show__value">{{ order.operation?.id ?? '--' }}</span>
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Stato') }}</span>
                        <OperationOrderStatusBadge :status="order.status" />
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Confermato il') }}</span>
                        <span class="orders-show__value">{{ dateTime(order.confirmed_at) ?? '--' }}</span>
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Importo') }}</span>
                        <span class="orders-show__value">
                            {{
                                order.amount !== null && order.amount !== undefined
                                    ? currency({ value: Number(order.amount), minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                    : '--'
                            }}
                        </span>
                    </div>
                    <div class="orders-show__details-item orders-show__details-item--full">
                        <span class="orders-show__label">{{ t('Descrizione') }}</span>
                        <span class="orders-show__value">{{ order.description ?? '--' }}</span>
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Creato il') }}</span>
                        <span class="orders-show__value">{{ dateTime(order.created_at) ?? '--' }}</span>
                    </div>
                    <div class="orders-show__details-item">
                        <span class="orders-show__label">{{ t('Aggiornato il') }}</span>
                        <span class="orders-show__value">{{ dateTime(order.updated_at) ?? '--' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import OperationOrderStatusBadge from '@/components/operations/OperationOrderStatusBadge.vue';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import type { Order } from '@/types/Order';
import { currency } from '@/utils/formatters/currency';
import { dateTime } from '@/utils/formatters/date';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Dettaglio ordine' }, () => [page]),
});

type Props = {
    order: Order;
};

defineProps<Props>();

const backToIndex = () => {
    router.get(
        route('workspace.orders.index', {
            building: workspace.value?.slug,
        }),
    );
};
</script>

<style>
@reference '@/../css/base.css';

.orders-show__content {
    @apply mt-6;
}

.orders-show__details {
    @apply py-4;
}

.orders-show__details-grid {
    @apply grid gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.orders-show__details-item {
    @apply flex flex-col gap-1;
}

.orders-show__details-item--full {
    @apply sm:col-span-2 lg:col-span-3;
}

.orders-show__label {
    @apply text-sm font-medium text-gray-500;
}

.orders-show__value {
    @apply text-gray-900;
}
</style>
