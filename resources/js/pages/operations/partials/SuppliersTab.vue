<script setup lang="ts">
import OperationSupplierStatusBadge from '@/components/operations/OperationSupplierStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import { useSelect } from '@/composables/useSelect';
import type { Operation } from '@/types/Operation';
import type { OperationSupplier } from '@/types/Supplier';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbIcon, BbSelect } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { can } = usePermissions();
const { success, error } = useMainToast();
const { select: selectSuppliers } = useSelect('suppliers');
const { select: selectOperationSupplierStatuses } = useSelect('operation-supplier-statuses');

type SupplierDocument = {
    id: number;
    name: string;
    file_name: string;
    size: number;
    uploaded_at: string | null;
    uploaded_by: string | null;
    url: string;
};

type Props = {
    operation: Operation & {
        selected_supplier?: OperationSupplier | null;
        supplier_documents?: SupplierDocument[];
    };
};

const props = defineProps<Props>();

const current = computed<OperationSupplier | null>(() => (props.operation.selected_supplier ?? null) as OperationSupplier | null);
const documents = computed<SupplierDocument[]>(() => props.operation.supplier_documents ?? []);

const addModal = ref(false);
const swapModal = ref(false);
const statusModal = ref(false);
const removeConfirm = ref(false);

const addForm = useForm<{ supplier_id: number | null }>({ supplier_id: null });
const swapForm = useForm<{ supplier_id: number | null }>({ supplier_id: null });
const statusForm = useForm<{ supplier_id: number | null; status: string | null }>({ supplier_id: null, status: null });

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const deletingId = ref<number | null>(null);

const openAdd = () => {
    addForm.reset();
    addForm.clearErrors();
    addModal.value = true;
};

const submitAdd = () => {
    addForm.post(route('operations.suppliers.store', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            addModal.value = false;
            success(t('Fornitore aggiornato.'));
        },
        onError: () => error(t('Si è verificato un errore.')),
    });
};

const openSwap = () => {
    swapForm.reset();
    swapForm.clearErrors();
    swapModal.value = true;
};

const submitSwap = () => {
    swapForm.patch(route('operations.suppliers.swap', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            swapModal.value = false;
            success(t('Fornitore aggiornato.'));
        },
        onError: () => error(t('Si è verificato un errore.')),
    });
};

const removeCurrent = () => {
    if (!current.value) return;
    router.delete(
        route('operations.suppliers.destroy', { operation: props.operation.id, supplier: current.value.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                removeConfirm.value = false;
                success(t('Fornitore rimosso.'));
            },
            onError: () => error(t('Si è verificato un errore.')),
        },
    );
};

const openStatus = () => {
    if (!current.value) return;
    statusForm.clearErrors();
    statusForm.supplier_id = current.value.id;
    statusForm.status = current.value.pivot?.status ?? null;
    statusModal.value = true;
};

const submitStatus = () => {
    statusForm.patch(route('operations.suppliers.status', { operation: props.operation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            statusModal.value = false;
            success(t('Stato fornitore aggiornato.'));
        },
        onError: () => error(t('Si è verificato un errore.')),
    });
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    uploading.value = true;
    router.post(route('operations.supplier-documents.store', { operation: props.operation.id }), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            success(t('Documento caricato.'));
            target.value = '';
        },
        onError: () => error(t('Caricamento fallito. Riprova.')),
        onFinish: () => {
            uploading.value = false;
        },
    });
};

const deleteDocument = (doc: SupplierDocument) => {
    if (deletingId.value === doc.id) return;

    deletingId.value = doc.id;
    router.delete(
        route('operations.supplier-documents.destroy', { operation: props.operation.id, media: doc.id }),
        {
            preserveScroll: true,
            onSuccess: () => success(t('Documento rimosso.')),
            onError: () => error(t('Si è verificato un errore.')),
            onFinish: () => {
                deletingId.value = null;
            },
        },
    );
};

const address = (supplier: OperationSupplier | null): string => {
    if (!supplier) return '';
    const location = [supplier.cap, supplier.city].filter(Boolean).join(' ');
    const province = supplier.province ? `(${supplier.province})` : null;
    return [supplier.address, location, province].filter(Boolean).join(', ');
};

const formatDateTime = (d: string | null | undefined): string =>
    d ? new Date(d).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' }) : '--';
</script>

<template>
    <div class="operation-suppliers">
        <div class="operation-suppliers__header">
            <h2 class="operation-suppliers__title">{{ t('Gestione fornitore') }}</h2>
            <BbButton v-if="!current && can('operations.supplier.manage')" append:icon="plus" size="xs" @click="openAdd">
                {{ t('Aggiungi fornitore') }}
            </BbButton>
            <BbButton
                v-else-if="current && can('operations.supplier.manage')"
                append:icon="refresh"
                size="xs"
                variant="secondary"
                @click="openSwap"
            >
                {{ t('Cambia fornitore') }}
            </BbButton>
        </div>

        <div v-if="!current" class="operation-suppliers__empty">
            {{ t('Nessun fornitore assegnato.') }}
        </div>

        <article v-else class="operation-suppliers__card operation-suppliers__card--selected">
            <div class="operation-suppliers__card-header">
                <div>
                    <h3 class="operation-suppliers__card-title">{{ current.name ?? '--' }}</h3>
                    <p class="operation-suppliers__card-vat">{{ current.vat ?? '--' }}</p>
                </div>
                <div class="operation-suppliers__actions">
                    <BbButton
                        v-if="can('operations.supplier.manage')"
                        size="xs"
                        variant="danger"
                        @click="removeConfirm = true"
                    >
                        {{ t('Rimuovi') }}
                    </BbButton>
                </div>
            </div>

            <div class="operation-suppliers__status">
                <button
                    v-if="can('operations.supplier.manage')"
                    type="button"
                    class="operation-suppliers__status-button"
                    @click="openStatus"
                >
                    <OperationSupplierStatusBadge :status="current.pivot?.status" size="xs" />
                </button>
                <OperationSupplierStatusBadge v-else :status="current.pivot?.status" size="xs" />
            </div>

            <div class="operation-suppliers__meta">
                <div class="operation-suppliers__meta-item">
                    <BbIcon type="emails" size="sm" />
                    <span>{{ current.mail ?? '--' }}</span>
                </div>
                <div class="operation-suppliers__meta-item">
                    <BbIcon type="phone" size="sm" />
                    <span>{{ current.phone ?? '--' }}</span>
                </div>
                <div class="operation-suppliers__meta-item">
                    <BbIcon type="building" size="sm" />
                    <span>{{ address(current) || '--' }}</span>
                </div>
            </div>
        </article>

        <section class="operation-suppliers__documents">
            <div class="operation-suppliers__documents-header">
                <h3>{{ t('Documenti fornitore') }}</h3>
                <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
                <BbButton
                    v-if="can('operations.supplier.manage')"
                    :disabled="uploading"
                    size="xs"
                    prepend:icon="upload"
                    @click="fileInput?.click()"
                >
                    {{ uploading ? t('Caricamento…') : t('Carica documento') }}
                </BbButton>
            </div>

            <p v-if="!documents.length" class="operation-suppliers__empty">
                {{ t('Nessun documento caricato.') }}
            </p>
            <ul v-else class="operation-suppliers__doc-list">
                <li v-for="doc in documents" :key="doc.id" class="operation-suppliers__doc-row">
                    <div class="flex flex-col">
                        <a :href="doc.url" target="_blank" rel="noopener">{{ doc.name }}</a>
                        <span class="text-xs text-gray-500">
                            {{ doc.uploaded_by ?? '--' }} · {{ formatDateTime(doc.uploaded_at) }}
                        </span>
                    </div>
                    <BbButton
                        v-if="can('operations.supplier.manage')"
                        size="xs"
                        variant="danger"
                        :disabled="deletingId === doc.id"
                        @click="deleteDocument(doc)"
                    >
                        {{ t('Elimina') }}
                    </BbButton>
                </li>
            </ul>
        </section>

        <BbDialog v-model="addModal" :title="t('Aggiungi fornitore')" size="md">
            <form class="operation-suppliers__dialog" @submit.prevent="submitAdd">
                <BbSelect
                    v-model="addForm.supplier_id"
                    item-text="label"
                    item-value="value"
                    :items="selectSuppliers"
                    :label="t('Fornitore')"
                    :errors="addForm.errors?.supplier_id"
                />
                <div class="operation-suppliers__dialog-actions">
                    <BbButton type="button" variant="outline" @click="addModal = false">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="addForm.processing || !addForm.supplier_id">
                        {{ t('Aggiungi') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="swapModal" :title="t('Cambia fornitore')" size="md">
            <form class="operation-suppliers__dialog" @submit.prevent="submitSwap">
                <p class="text-sm text-gray-600">
                    {{ t('Cambiando fornitore, i documenti caricati dal precedente resteranno visibili al nuovo fornitore.') }}
                </p>
                <BbSelect
                    v-model="swapForm.supplier_id"
                    item-text="label"
                    item-value="value"
                    :items="selectSuppliers"
                    :label="t('Nuovo fornitore')"
                    :errors="swapForm.errors?.supplier_id"
                />
                <div class="operation-suppliers__dialog-actions">
                    <BbButton type="button" variant="outline" @click="swapModal = false">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="submit" :disabled="swapForm.processing || !swapForm.supplier_id">
                        {{ t('Cambia') }}
                    </BbButton>
                </div>
            </form>
        </BbDialog>

        <BbDialog v-model="removeConfirm" :title="t('Rimuovi fornitore')" size="sm">
            <div class="operation-suppliers__dialog">
                <p>{{ t('Sei sicuro di voler rimuovere il fornitore corrente?') }}</p>
                <div class="operation-suppliers__dialog-actions">
                    <BbButton type="button" variant="outline" @click="removeConfirm = false">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton variant="danger" @click="removeCurrent">{{ t('Rimuovi') }}</BbButton>
                </div>
            </div>
        </BbDialog>

        <BbDialog v-model="statusModal" :title="t('Aggiorna stato fornitore')" size="sm">
            <form class="operation-suppliers__dialog" @submit.prevent="submitStatus">
                <BbSelect
                    v-model="statusForm.status"
                    item-text="label"
                    item-value="value"
                    :items="selectOperationSupplierStatuses"
                    :label="t('Stato')"
                    :errors="statusForm.errors?.status"
                />
                <div class="operation-suppliers__dialog-actions">
                    <BbButton type="button" variant="outline" @click="statusModal = false">
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
    @apply py-6 text-sm text-gray-500;
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

.operation-suppliers__documents {
    @apply mt-6 rounded-lg border border-gray-200 bg-white p-4;
}

.operation-suppliers__documents-header {
    @apply mb-3 flex items-center justify-between gap-3;
}

.operation-suppliers__documents-header h3 {
    @apply text-base font-semibold text-gray-900;
}

.operation-suppliers__doc-list {
    @apply flex flex-col gap-2;
}

.operation-suppliers__doc-row {
    @apply flex items-center justify-between gap-3 rounded bg-gray-50 px-3 py-2 text-sm;
}

.operation-suppliers__dialog {
    @apply flex flex-col gap-4;
}

.operation-suppliers__dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
