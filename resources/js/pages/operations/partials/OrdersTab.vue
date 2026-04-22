<script setup lang="ts">
import OperationOrderStatusBadge from '@/components/operations/OperationOrderStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import { useSelect } from '@/composables/useSelect';
import type { Operation } from '@/types/Operation';
import type { Order } from '@/types/Order';
import { currency } from '@/utils/formatters/currency';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbPopover, BbSelect, BbTextInput } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { can } = usePermissions();
const { success, error } = useMainToast();
const { select: selectOrderStatuses } = useSelect('order-statuses');

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'order:updated'): void;
}>();

const orders = computed<Order[]>(() => props.operation.orders ?? []);
const confirmingOrderId = ref<number | null>(null);

const modal = ref(false);
const selectedOrder = ref<Order | null>(null);
const form = useForm<{ amount: string | null; description: string | null; status: string | null }>({
    amount: null,
    description: null,
    status: 'pending',
});

const statusModal = ref(false);
const selectedStatusOrder = ref<Order | null>(null);
const statusForm = useForm<{ status: string | null }>({
    status: null,
});

const openModal = (order?: Order) => {
    selectedOrder.value = order ?? null;
    form.clearErrors();
    form.amount = order?.amount !== null && order?.amount !== undefined ? String(order.amount) : null;
    form.description = order?.description ?? null;
    form.status = order?.status ?? 'pending';
    modal.value = true;
};

const closeModal = () => {
    selectedOrder.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'pending';
    modal.value = false;
};

const saveOrder = () => {
    if (selectedOrder.value?.id) {
        form.put(
            route('operations.orders.update', {
                operation: props.operation.id,
                order: selectedOrder.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();
                    success('Ordine aggiornato con successo');
                    emit('order:updated');
                },
                onError: () => {
                    error('Si è verificato un errore');
                },
            },
        );
        return;
    }

    form.post(route('operations.orders.store', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            success('Ordine aggiunto con successo');
            emit('order:updated');
        },
        onError: () => {
            error('Si è verificato un errore');
        },
    });
};

const removeOrder = (orderId: number) => {
    router.delete(
        route('operations.orders.destroy', {
            operation: props.operation.id,
            order: orderId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Ordine eliminato con successo');
                emit('order:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const confirmOrder = (order: Order) => {
    if (!order.id || confirmingOrderId.value !== null) {
        return;
    }

    confirmingOrderId.value = order.id;
    router.post(
        route('orders.confirm', { order: order.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Ordine confermato con successo');
                emit('order:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
            onFinish: () => {
                confirmingOrderId.value = null;
            },
        },
    );
};

const openStatusModal = (order: Order) => {
    selectedStatusOrder.value = order;
    statusForm.clearErrors();
    statusForm.status = order.status ?? 'pending';
    statusModal.value = true;
};

const closeStatusModal = () => {
    selectedStatusOrder.value = null;
    statusForm.reset();
    statusForm.clearErrors();
    statusModal.value = false;
};

const saveStatus = () => {
    if (!selectedStatusOrder.value?.id) {
        return;
    }

    statusForm.patch(
        route('operations.orders.status', {
            operation: props.operation.id,
            order: selectedStatusOrder.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeStatusModal();
                success('Stato ordine aggiornato con successo');
                emit('order:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};
</script>

<template>
    <div class="operation-orders">
        <div class="operation-orders__header">
            <h2 class="operation-orders__title">{{ t('Ordini') }}</h2>
            <BbButton v-if="can('operations.order.manage')" append:icon="plus" size="xs" @click="openModal()">
                {{ t('Aggiungi ordine') }}
            </BbButton>
        </div>

        <div v-if="orders.length === 0" class="operation-orders__empty">
            {{ t('Nessun ordine presente') }}
        </div>

        <div v-else class="operation-orders__list">
            <article v-for="order in orders" :key="order.id" class="operation-orders__card">
                <div class="operation-orders__card-header">
                    <h3 class="operation-orders__card-title">{{ t('Ordine') }} {{ order.code ?? `#${order.id}` }}</h3>
                    <div class="operation-orders__actions">
                        <BbButton v-if="can('operations.order.manage')" size="xs" icon="pencil_line" @click="openModal(order)">
                            {{ t('Modifica') }}
                        </BbButton>
                        <BbButton
                            v-if="can('operations.order.manage') && order.status === 'pending'"
                            append:icon="play"
                            size="xs"
                            :disabled="confirmingOrderId === order.id"
                            @click="confirmOrder(order)"
                        >
                            {{ t('Conferma') }}
                        </BbButton>
                        <BbPopover v-if="can('operations.order.manage')">
                            <template #activator="{ props }">
                                <BbButton size="xs" variant="danger" v-bind="props">
                                    {{ t('Elimina') }}
                                </BbButton>
                            </template>
                            <template #default="{ close }">
                                <p class="mb-2 max-w-[250px]">
                                    {{ t('Sei sicuro di voler eliminare') }}
                                    <strong> {{ t('Ordine') }} {{ order.code ?? `#${order.id}` }}?</strong>
                                </p>
                                <div class="text-right">
                                    <BbButton
                                        variant="danger"
                                        size="xs"
                                        @click="
                                            () => {
                                                removeOrder(order.id);
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

                <div class="operation-orders__status">
                    <button
                        v-if="can('operations.order.manage')"
                        type="button"
                        class="operation-orders__status-button"
                        @click="openStatusModal(order)"
                    >
                        <OperationOrderStatusBadge :status="order.status" size="xs" />
                    </button>
                    <OperationOrderStatusBadge v-else :status="order.status" size="xs" />
                </div>

                <div class="operation-orders__meta">
                    <div class="operation-orders__meta-item">
                        <span class="operation-orders__meta-label">{{ t('Codice') }}</span>
                        <span>{{ order.code ?? '--' }}</span>
                    </div>
                    <div class="operation-orders__meta-item">
                        <span class="operation-orders__meta-label">{{ t('Importo') }}</span>
                        <span>
                            {{
                                order.amount !== null && order.amount !== undefined
                                    ? currency({ value: Number(order.amount), minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                    : '--'
                            }}
                        </span>
                    </div>
                    <div class="operation-orders__meta-item">
                        <span class="operation-orders__meta-label">{{ t('Descrizione') }}</span>
                        <span>{{ order.description ?? '--' }}</span>
                    </div>
                </div>
            </article>
        </div>

        <BbDialog v-model="modal" :title="selectedOrder?.id ? t('Modifica ordine') : t('Aggiungi ordine')" size="md">
            <form class="operation-orders__dialog" @submit.prevent="saveOrder">
                <BbTextInput v-model="form.amount" type="number" autocomplete="off" :label="t('Importo')" :errors="form.errors?.amount" />
                <BbTextInput v-model="form.description" autocomplete="off" :label="t('Descrizione')" :errors="form.errors?.description" />
                <BbSelect
                    v-if="selectedOrder?.id"
                    v-model="form.status"
                    item-text="label"
                    item-value="value"
                    :items="selectOrderStatuses"
                    :label="t('Stato')"
                    :errors="form.errors?.status"
                />
                <div class="operation-orders__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeModal">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="form.processing">
                        {{ t('Salva') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="statusModal" :title="t('Aggiorna stato ordine')" size="sm">
            <form class="operation-orders__dialog" @submit.prevent="saveStatus">
                <BbSelect
                    v-model="statusForm.status"
                    item-text="label"
                    item-value="value"
                    :items="selectOrderStatuses"
                    :label="t('Stato')"
                    :errors="statusForm.errors?.status"
                />
                <div class="operation-orders__dialog-actions">
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

.operation-orders {
    @apply py-4;
}

.operation-orders__header {
    @apply mb-4 flex items-center justify-between gap-3;
}

.operation-orders__title {
    @apply text-lg font-semibold text-gray-900;
}

.operation-orders__empty {
    @apply py-10 text-center text-gray-500;
}

.operation-orders__list {
    @apply space-y-4;
}

.operation-orders__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-orders__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-orders__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-orders__actions {
    @apply flex items-center gap-2;
}

.operation-orders__status {
    @apply mt-3;
}

.operation-orders__status-button {
    @apply rounded-md;
}

.operation-orders__meta {
    @apply mt-3 space-y-2;
}

.operation-orders__meta-item {
    @apply flex flex-col gap-1 text-sm text-gray-700;
}

.operation-orders__meta-label {
    @apply text-xs text-gray-500;
}

.operation-orders__dialog {
    @apply flex flex-col gap-4;
}

.operation-orders__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
