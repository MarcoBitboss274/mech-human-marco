<template>
    <div class="admin-view boxed supplier-operation-show">
        <div class="admin-view__header">
            <div>
                <div class="flex flex-wrap items-center gap-4">
                    <h1 class="page__title">{{ t(operation.latest_prescription?.typology ?? '') }}</h1>
                    <div
                        v-if="isCanceled"
                        class="flex w-fit items-center justify-center whitespace-nowrap rounded-md border border-red-500 !bg-red-200 px-3 py-1 text-sm leading-none !text-red-700"
                    >
                        {{ t('Annullata') }}
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-8">
                    <span class="page__subtitle"
                        >{{ t('Lotto') }}: <span class="font-bold">{{ operation.batch_number ?? '--' }}</span></span
                    >
                    <span class="page__subtitle"
                        >{{ t('Riferimento') }}: <span class="font-bold">{{ operation.latest_prescription?.ref ?? '--' }}</span></span
                    >
                    <span class="page__subtitle inline-flex items-center gap-2">
                        {{ t('Stato produzione:') }}
                        <ProductionStatusBadge
                            :status="operation.production_status"
                            :timestamp="productionTimestamp"
                            size="sm"
                            verbose
                        />
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <BbButton icon="activity" @click="activityOpen = true">{{ t('Attività') }}</BbButton>
                <BbButton icon="chat" @click="chatOpen = true">{{ t('Chat') }}</BbButton>
                <BbButton icon="arrow-left" :title="t('Torna alla lista')" @click="goBack" />
            </div>
        </div>

        <SupplierOperationAlert :operation="operation" />

        <ActivitySlider v-model="activityOpen" model-type="supplier_operation" :model-id="operation.id" />

        <div class="mt-2">
            <BbTab v-model="tab" :items="tabs">
                <template #case>
                    <div v-if="latestPrescription" class="py-4">
                        <PrescriptionDetailsCard
                            :prescription="latestPrescription"
                            :operation="operation"
                            hide-patient-data
                            hide-requester
                            hide-building
                            hide-status-badge
                        />
                    </div>
                    <div v-else class="py-8 text-center text-gray-500">
                        {{ t('Nessuna prescrizione collegata') }}
                    </div>
                </template>

                <template #my-documents>
                    <section class="supplier-operation-show__section">
                        <h2 class="supplier-operation-show__section-title">{{ t('Documenti da caricare') }}</h2>
                        <p class="supplier-operation-show__hint">
                            {{ t('Carica i documenti necessari a M&H per procedere con il preventivo.') }}
                        </p>

                        <div v-if="canUpload" class="supplier-operation-show__upload">
                            <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
                            <BbButton :disabled="uploading" prepend:icon="upload" @click="fileInput?.click()">
                                {{ uploading ? t('Caricamento…') : t('Carica documento') }}
                            </BbButton>
                        </div>

                        <p v-if="!supplierDocuments.length" class="supplier-operation-show__empty">
                            {{ t('Nessun documento caricato.') }}
                        </p>
                        <ul v-else class="supplier-operation-show__doc-list">
                            <li v-for="doc in supplierDocuments" :key="doc.id" class="supplier-operation-show__doc-row">
                                <div class="flex flex-col">
                                    <a :href="doc.url" target="_blank" rel="noopener">{{ doc.name }}</a>
                                    <span class="text-xs text-gray-500">
                                        {{ doc.uploaded_by ?? '--' }} · {{ formatDateTime(doc.uploaded_at) }}
                                    </span>
                                </div>
                                <BbButton
                                    v-if="canDelete"
                                    variant="danger"
                                    size="xs"
                                    :disabled="deletingId === doc.id"
                                    @click="deleteDocument(doc)"
                                >
                                    {{ t('Elimina') }}
                                </BbButton>
                            </li>
                        </ul>
                    </section>
                </template>
            </BbTab>
        </div>

        <ChatSlider
            v-model="chatOpen"
            chat-scope="supplier"
            :title="t('Chat con M&H')"
            :operation-id="operation.id"
            :current-user-id="currentUserId"
            :can-read="true"
            :can-send="true"
        />
    </div>
</template>

<script setup lang="ts">
import ActivitySlider from '@/components/activity/ActivitySlider.vue';
import ChatSlider from '@/components/chat/ChatSlider.vue';
import PrescriptionDetailsCard from '@/components/prescriptions/PrescriptionDetailsCard.vue';
import ProductionStatusBadge from '@/components/productions/ProductionStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { Operation } from '@/types/Operation';
import type { Prescription } from '@/types/Prescription';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton, BbTab, type BbTabItem } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SupplierOperationAlert from './partials/SupplierOperationAlert.vue';

const { t } = useI18n();
const { success, error } = useMainToast();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Lavorazione' }, () => [page]),
});

type SupplierDocument = {
    id: number;
    name: string;
    file_name: string;
    size: number;
    uploaded_at: string | null;
    uploaded_by: string | null;
    url: string;
};

type ExtendedOperation = Operation & {
    production_status: string | null;
    production_confirmed_at: string | null;
    production_canceled_at: string | null;
    production_completed_at: string | null;
    batch_number: string | null;
    assigned_at: string | null;
    canceled_at: string | null;
    latest_prescription?: {
        ref: string | null;
        typology: string | null;
        send_at: string | null;
        expire_at: string | null;
        active_revision?: { id: number; opened_at?: string | null; closed_at?: string | null } | null;
    } | null;
    prescriptions?: Prescription[];
};

const props = defineProps<{
    supplier: { id: number; name: string | null };
    operation: ExtendedOperation;
    supplier_documents: SupplierDocument[];
}>();

const supplierDocuments = computed(() => props.supplier_documents ?? []);
const latestPrescription = computed<Prescription | undefined>(() => props.operation.prescriptions?.[0]);

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const deletingId = ref<number | null>(null);
const chatOpen = ref(false);
const activityOpen = ref(false);

const tab = ref<string>('case');
const tabs = computed<BbTabItem[]>(() => [
    { key: 'case', label: t('Caso') },
    { key: 'my-documents', label: t('Miei documenti') },
]);

const inertiaPage = usePage<any>();
const currentUserId = computed<number>(() => inertiaPage.props.auth?.user?.id ?? 0);

const isCanceled = computed(() => !!props.operation.canceled_at);

// Documenti editabili solo in pre-produzione (production_status null o canceled).
// Allineato al vincolo backend in OperationService::ensureSupplierDocumentsEditable.
const productionIsActive = computed(
    () => props.operation.production_status === 'confirmed' || props.operation.production_status === 'completed',
);
const canUpload = computed(() => !productionIsActive.value && !isCanceled.value);
const canDelete = computed(() => !productionIsActive.value && !isCanceled.value);

const productionTimestamp = computed<string | null>(() => {
    switch (props.operation.production_status) {
        case 'confirmed':
            return props.operation.production_confirmed_at;
        case 'canceled':
            return props.operation.production_canceled_at;
        case 'completed':
            return props.operation.production_completed_at;
        default:
            return null;
    }
});

const formatDateTime = (d: string | null | undefined): string =>
    d ? new Date(d).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' }) : '--';

const goBack = () => {
    router.get(route('workspace.supplier.operations.index'));
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('file', file);

    router.post(route('workspace.supplier.operations.documents.store', { operation: props.operation.id }), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            success(t('Documento caricato.'));
            target.value = '';
        },
        onError: () => {
            error(t('Caricamento fallito. Riprova.'));
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
};

const deleteDocument = (doc: SupplierDocument) => {
    if (deletingId.value === doc.id) return;

    deletingId.value = doc.id;
    router.delete(
        route('workspace.supplier.operations.documents.destroy', {
            operation: props.operation.id,
            media: doc.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => success(t('Documento rimosso.')),
            onError: () => error(t('Non è più possibile rimuovere questo documento.')),
            onFinish: () => {
                deletingId.value = null;
            },
        },
    );
};

</script>

<style scoped>
.supplier-operation-show__section {
    margin-top: 32px;
}

.supplier-operation-show__section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
}

.supplier-operation-show__hint {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 12px;
}

.supplier-operation-show__upload {
    margin-bottom: 12px;
}

.supplier-operation-show__empty {
    font-size: 14px;
    color: #6b7280;
}

.supplier-operation-show__doc-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.supplier-operation-show__doc-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 4px;
    background: #f9fafb;
}
</style>
