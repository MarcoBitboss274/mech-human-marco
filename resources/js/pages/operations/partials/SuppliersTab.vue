<script setup lang="ts">
import OperationSupplierStatusBadge from '@/components/operations/OperationSupplierStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import { useSelect } from '@/composables/useSelect';
import type { Operation } from '@/types/Operation';
import type { OperationSupplier } from '@/types/Supplier';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbIcon, BbPopover, BbSelect } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { can } = usePermissions();
const { success, error } = useMainToast();
const { select: selectSuppliers } = useSelect('suppliers');
const { select: selectOperationSupplierStatuses } = useSelect('operation-supplier-statuses');

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();

const modal = ref(false);
const form = useForm<{ supplier_id: number | null }>({
    supplier_id: null,
});
const statusModal = ref(false);
const statusForm = useForm<{ supplier_id: number | null; status: string | null }>({
    supplier_id: null,
    status: null,
});

const suppliers = computed<OperationSupplier[]>(() => props.operation.suppliers ?? []);

const openModal = () => {
    form.reset();
    form.clearErrors();
    modal.value = true;
};

const closeModal = () => {
    form.reset();
    form.clearErrors();
    modal.value = false;
};

const openStatusModal = (supplier: OperationSupplier) => {
    statusForm.clearErrors();
    statusForm.supplier_id = supplier.id;
    statusForm.status = supplier.pivot?.status ?? null;
    statusModal.value = true;
};

const closeStatusModal = () => {
    statusForm.reset();
    statusForm.clearErrors();
    statusModal.value = false;
};

const addSupplier = () => {
    form.post(route('operations.suppliers.store', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            success('Fornitore aggiunto con successo');
        },
        onError: () => {
            error('Si è verificato un errore');
        },
    });
};

const selectSupplier = (supplierId: number) => {
    router.patch(
        route('operations.suppliers.select', { operation: props.operation.id }),
        { supplier_id: supplierId },
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Fornitore selezionato con successo');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const removeSupplier = (supplierId: number) => {
    router.delete(
        route('operations.suppliers.destroy', {
            operation: props.operation.id,
            supplier: supplierId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Fornitore rimosso con successo');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const saveSupplierStatus = () => {
    statusForm.patch(route('operations.suppliers.status', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
            success('Stato fornitore aggiornato con successo');
        },
        onError: () => {
            error('Si è verificato un errore');
        },
    });
};

const address = (supplier: OperationSupplier) => {
    const location = [supplier.cap, supplier.city].filter(Boolean).join(' ');
    const province = supplier.province ? `(${supplier.province})` : null;
    return [supplier.address, location, province].filter(Boolean).join(', ');
};
</script>

<template>
    <div class="operation-suppliers">
        <div class="operation-suppliers__header">
            <h2 class="operation-suppliers__title">{{ t('Gestione fornitore') }}</h2>
            <BbButton v-if="can('operations.supplier.manage')" append:icon="plus" size="xs" @click="openModal">
                {{ t('Aggiungi fornitore') }}
            </BbButton>
        </div>

        <div v-if="suppliers.length === 0" class="operation-suppliers__empty">
            {{ t('Nessun fornitore presente') }}
        </div>

        <div v-else class="operation-suppliers__list">
            <article
                v-for="supplier in suppliers"
                :key="supplier.id"
                class="operation-suppliers__card"
                :class="{ 'operation-suppliers__card--selected': supplier.pivot?.selected }"
            >
                <div class="operation-suppliers__card-header">
                    <div>
                        <h3 class="operation-suppliers__card-title">{{ supplier.name ?? '--' }}</h3>
                        <p class="operation-suppliers__card-vat">{{ supplier.vat ?? '--' }}</p>
                    </div>
                    <div class="operation-suppliers__actions">
                        <template v-if="can('operations.supplier.manage')">
                            <BbButton size="xs" v-if="!supplier.pivot?.selected" @click="selectSupplier(supplier.id)">
                                {{ t('Scegli') }}
                            </BbButton>
                            <BbPopover>
                                <template #activator="{ props }">
                                    <BbButton size="xs" variant="danger" v-bind="props">
                                        {{ t('Rimuovi') }}
                                    </BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler eliminare') }}
                                        <strong> {{ supplier.name ?? '--' }}?</strong>
                                    </p>
                                    <div class="text-right">
                                        <BbButton
                                            variant="danger"
                                            size="xs"
                                            @click="
                                                () => {
                                                    removeSupplier(supplier.id);
                                                    close();
                                                }
                                            "
                                        >
                                            {{ t('Rimuovi') }}
                                        </BbButton>
                                    </div>
                                </template>
                            </BbPopover>
                        </template>
                    </div>
                </div>

                <div class="operation-suppliers__status">
                    <button
                        v-if="can('operations.supplier.manage')"
                        type="button"
                        class="operation-suppliers__status-button"
                        @click="openStatusModal(supplier)"
                    >
                        <OperationSupplierStatusBadge :status="supplier.pivot?.status" size="xs" />
                    </button>
                    <OperationSupplierStatusBadge v-else :status="supplier.pivot?.status" size="xs" />
                </div>

                <div class="operation-suppliers__meta">
                    <div class="operation-suppliers__meta-item">
                        <BbIcon type="emails" size="sm" />
                        <span>{{ supplier.mail ?? '--' }}</span>
                    </div>
                    <div class="operation-suppliers__meta-item">
                        <BbIcon type="phone" size="sm" />
                        <span>{{ supplier.phone ?? '--' }}</span>
                    </div>
                    <div class="operation-suppliers__meta-item">
                        <BbIcon type="building" size="sm" />
                        <span>{{ address(supplier) || '--' }}</span>
                    </div>
                </div>
            </article>
        </div>

        <BbDialog v-model="modal" :title="t('Aggiungi fornitore')" size="md">
            <form class="operation-suppliers__dialog" @submit.prevent="addSupplier">
                <BbSelect
                    v-model="form.supplier_id"
                    item-text="label"
                    item-value="value"
                    :items="selectSuppliers"
                    :label="t('Fornitore')"
                    :errors="form.errors?.supplier_id"
                />
                <div class="operation-suppliers__dialog-actions">
                    <BbButton type="button" variant="outline" @click="closeModal">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="form.processing || !form.supplier_id">
                        {{ t('Aggiungi') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="statusModal" :title="t('Aggiorna stato fornitore')" size="sm">
            <form class="operation-suppliers__dialog" @submit.prevent="saveSupplierStatus">
                <BbSelect
                    v-model="statusForm.status"
                    item-text="label"
                    item-value="value"
                    :items="selectOperationSupplierStatuses"
                    :label="t('Stato')"
                    :errors="statusForm.errors?.status"
                />
                <div class="operation-suppliers__dialog-actions">
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

.operation-suppliers {
    @apply py-4;
}

.operation-suppliers__header {
    @apply mb-4 flex items-center justify-between gap-3;
}

.operation-suppliers__title {
    @apply text-lg font-semibold text-gray-900;
}

.operation-suppliers__empty {
    @apply py-10 text-center text-gray-500;
}

.operation-suppliers__list {
    @apply space-y-4;
}

.operation-suppliers__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-suppliers__card--selected {
    @apply border-emerald-200 bg-emerald-50;
}

.operation-suppliers__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-suppliers__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-suppliers__card-vat {
    @apply text-xs text-gray-500;
}

.operation-suppliers__actions {
    @apply flex items-center gap-2;
}

.operation-suppliers__status {
    @apply mt-3;
}

.operation-suppliers__status-button {
    @apply rounded-md;
}

.operation-suppliers__meta {
    @apply mt-3 space-y-2;
}

.operation-suppliers__meta-item {
    @apply flex items-center gap-2 text-sm text-gray-700;
}

.operation-suppliers__dialog {
    @apply flex flex-col gap-4;
}

.operation-suppliers__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
