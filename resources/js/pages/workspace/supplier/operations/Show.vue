<template>
    <div class="supplier-operation-show">
        <div class="supplier-operation-show__header">
            <BbButton variant="secondary" prepend:icon="arrow-left" @click="goBack">
                {{ t('Torna alla lista') }}
            </BbButton>
            <h1 class="page__title">{{ t('Lavorazione') }} #{{ operation.batch_number ?? operation.id }}</h1>
        </div>

        <BbAlert v-if="operation.supplier_visible_status === 'canceled'" theme="error" class="my-4">
            {{ t('Questa lavorazione è stata annullata da M&H.') }}
        </BbAlert>
        <BbAlert v-else-if="operation.supplier_visible_status === 'assigned_waiting_documents'" theme="warning" class="my-4">
            {{ t('Carica i documenti necessari a M&H per procedere con il preventivo.') }}
        </BbAlert>
        <BbAlert v-else-if="operation.supplier_visible_status === 'production_confirmed'" theme="success" class="my-4">
            {{ t('Produzione confermata. Puoi procedere con la lavorazione.') }}
        </BbAlert>

        <div class="supplier-operation-show__grid">
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Tipologia') }}</span>
                <PrescriptionTypologyBadge v-if="operation.latest_prescription?.typology" :typology="operation.latest_prescription.typology" size="xs" />
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
                <span class="supplier-operation-show__label">{{ t('Data invio prescrizione') }}</span>
                <span class="supplier-operation-show__value">{{ formatDate(operation.latest_prescription?.send_at) }}</span>
            </div>
            <div class="supplier-operation-show__field">
                <span class="supplier-operation-show__label">{{ t('Scadenza prescrizione') }}</span>
                <span class="supplier-operation-show__value">{{ formatDate(operation.latest_prescription?.expire_at) }}</span>
            </div>
        </div>

        <div class="supplier-operation-show__placeholder">
            <h2>{{ t('Documenti e comunicazione') }}</h2>
            <p>
                {{ t('La sezione completa con documenti del caso, upload documenti fornitore e chat con M&H è in sviluppo.') }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { Operation } from '@/types/Operation';
import { router } from '@inertiajs/vue3';
import { BbAlert, BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Lavorazione' }, () => [page]),
});

type ExtendedOperation = Operation & {
    supplier_visible_status: string | null;
    batch_number: string | null;
    latest_prescription?: {
        ref: string | null;
        typology: string | null;
        send_at: string | null;
        expire_at: string | null;
    } | null;
};

defineProps<{
    supplier: { id: number; name: string | null };
    operation: ExtendedOperation;
}>();

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

const formatDate = (d: string | null | undefined): string => {
    if (!d) return '--';
    return new Date(d).toLocaleDateString('it-IT');
};

const goBack = () => {
    router.get(route('workspace.supplier.operations.index'));
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

.supplier-operation-show__placeholder {
    margin-top: 32px;
    padding: 16px;
    border: 2px dashed var(--bb-border-light, #d1d5db);
    border-radius: 4px;
    color: #6b7280;
    font-size: 14px;
}

.supplier-operation-show__placeholder h2 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
    color: var(--bb-text);
}
</style>
