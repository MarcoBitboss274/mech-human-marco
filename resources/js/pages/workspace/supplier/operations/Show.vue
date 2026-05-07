<template>
    <div class="supplier-operation-show">
        <div class="supplier-operation-show__header">
            <BbButton variant="secondary" prepend:icon="arrow-left" @click="goBack">
                {{ t('Torna alla lista') }}
            </BbButton>
            <h1 class="page__title">{{ t('Lavorazione') }} #{{ operation.batch_number ?? operation.id }}</h1>
        </div>

        <SupplierOperationAlert :operation="operation" :on-go-to-upload="scrollToUpload" />

        <div class="supplier-operation-show__grid">
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Tipologia') }}</span>
                <PrescriptionTypologyBadge
                    v-if="operation.latest_prescription?.typology"
                    :typology="operation.latest_prescription.typology"
                    size="xs"
                />
                <span v-else class="supplier-operation-show__value">--</span>
            </div>
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Codice di lotto') }}</span>
                <span class="supplier-operation-show__value">{{ operation.batch_number ?? '--' }}</span>
            </div>
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Riferimento') }}</span>
                <span class="supplier-operation-show__value">{{ operation.latest_prescription?.ref ?? '--' }}</span>
            </div>
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Stato') }}</span>
                <span class="supplier-operation-show__value">{{ supplierStatusLabel(operation.supplier_visible_status) }}</span>
            </div>
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Scadenza prescrizione') }}</span>
                <span class="supplier-operation-show__value">{{ formatDate(operation.latest_prescription?.expire_at) }}</span>
            </div>
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Data di assegnazione') }}</span>
                <span class="supplier-operation-show__value">{{ formatDate(operation.assigned_at) }}</span>
            </div>
        </div>

        <section class="supplier-operation-show__section">
            <h2 class="supplier-operation-show__section-title">{{ t('Documenti del caso') }}</h2>
            <p v-if="!caseDocuments.length" class="supplier-operation-show__empty">
                {{ t('Nessun documento del caso disponibile.') }}
            </p>
            <ul v-else class="supplier-operation-show__doc-list">
                <li v-for="doc in caseDocuments" :key="doc.id" class="supplier-operation-show__doc-row">
                    <a :href="doc.url" target="_blank" rel="noopener">{{ doc.name }}</a>
                </li>
            </ul>
        </section>

        <section v-if="mhDocuments.length" class="supplier-operation-show__section">
            <h2 class="supplier-operation-show__section-title">{{ t('Documenti aggiuntivi e note di M&H') }}</h2>
            <ul class="supplier-operation-show__doc-list">
                <li v-for="doc in mhDocuments" :key="doc.id" class="supplier-operation-show__doc-row">
                    <a :href="doc.url" target="_blank" rel="noopener">{{ doc.name }}</a>
                </li>
            </ul>
        </section>

        <div class="supplier-operation-show__chat-actions">
            <BbButton icon="chat" variant="secondary" @click="chatOpen = true">
                {{ t('Chat con M&H') }}
            </BbButton>
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

        <section ref="uploadSection" class="supplier-operation-show__section">
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
    </div>
</template>

<script setup lang="ts">
import ChatSlider from '@/components/chat/ChatSlider.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { Operation } from '@/types/Operation';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
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
    supplier_visible_status: string | null;
    batch_number: string | null;
    assigned_at: string | null;
    latest_prescription?: {
        ref: string | null;
        typology: string | null;
        send_at: string | null;
        expire_at: string | null;
    } | null;
};

const props = defineProps<{
    supplier: { id: number; name: string | null };
    operation: ExtendedOperation;
    supplier_documents: SupplierDocument[];
}>();

const supplierDocuments = computed(() => props.supplier_documents ?? []);
const caseDocuments = computed<SupplierDocument[]>(() => []);
const mhDocuments = computed<SupplierDocument[]>(() => []);

const fileInput = ref<HTMLInputElement | null>(null);
const uploadSection = ref<HTMLElement | null>(null);
const uploading = ref(false);
const deletingId = ref<number | null>(null);
const chatOpen = ref(false);

const inertiaPage = usePage<any>();
const currentUserId = computed<number>(() => inertiaPage.props.auth?.user?.id ?? 0);

const isEditableState = computed(() => {
    const status = props.operation.supplier_visible_status;
    return status === 'assigned_waiting_documents' || status === 'documents_sent';
});

const canUpload = computed(() => isEditableState.value);
const canDelete = computed(() => isEditableState.value);

const supplierStatusLabels: Record<string, string> = {
    assigned_waiting_documents: t('Assegnata, in attesa documenti'),
    documents_sent: t('Documenti inviati, in valutazione M&H'),
    under_evaluation: t('In valutazione M&H'),
    production_confirmed: t('Produzione confermata'),
    completed: t('Completata'),
    canceled: t('Annullata'),
};

const supplierStatusLabel = (key: string | null | undefined): string => {
    if (!key) return '--';
    return supplierStatusLabels[key] ?? key;
};

const formatDate = (d: string | null | undefined): string => (d ? new Date(d).toLocaleDateString('it-IT') : '--');
const formatDateTime = (d: string | null | undefined): string =>
    d ? new Date(d).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' }) : '--';

const goBack = () => {
    router.get(route('workspace.supplier.operations.index'));
};

const scrollToUpload = () => {
    uploadSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
.supplier-operation-show__header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.supplier-operation-show__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin: 24px 0;
}

.supplier-operation-show__field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.supplier-operation-show__label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.supplier-operation-show__value {
    font-size: 14px;
    color: var(--bb-text);
}

.supplier-operation-show__section {
    margin-top: 32px;
    padding: 16px;
    border: 2px solid var(--bb-border-light, #e5e7eb);
    border-radius: 8px;
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
