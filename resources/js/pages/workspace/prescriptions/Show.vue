<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <h1 class="workspace-view__title">{{ t('Dettaglio prescrizione') }}</h1>
            <BbButton icon="arrow-left" @click="backToIndex">{{ t('Torna alla lista') }}</BbButton>
        </div>

        <div class="prescriptions-show__content">
            <div class="prescriptions-show__details">
                <div class="prescriptions-show__details-grid">
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Tipologia') }}</span>
                        <PrescriptionTypologyBadge :typology="prescription.typology" />
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Riferimento') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.ref ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Stato') }}</span>
                        <PrescriptionStatusBadge :status="prescription.status" :in-revision="!!prescription.active_revision" />
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Nome') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.name ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Cognome') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.surname ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Età') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.age ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Genere') }}</span>
                        <PrescriptionGenderBadge :gender="prescription.gender" />
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Inviata il') }}</span>
                        <span class="prescriptions-show__value">{{ date(prescription.send_at) ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Scade il') }}</span>
                        <span class="prescriptions-show__value">{{ date(prescription.expire_at) ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Ragione sociale') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.company_name ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Indirizzo') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.address ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Città') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.city ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Provincia') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.province ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Cap') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.cap ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Note aggiungtive') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.notes ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Lavorazione') }}</span>
                        <span class="prescriptions-show__value">
                            <OperationStatusBadge :status="prescription.operation?.status" />
                        </span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Struttura') }}</span>
                        <span class="prescriptions-show__value">{{ prescription.building?.name ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Utente') }}</span>
                        <span class="prescriptions-show__value">
                            {{ prescription.user ? [prescription.user.name, prescription.user.surname].filter(Boolean).join(' ') : '--' }}
                        </span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Creato il') }}</span>
                        <span class="prescriptions-show__value">{{ dateTime(prescription.created_at) ?? '--' }}</span>
                    </div>
                    <div class="prescriptions-show__details-item">
                        <span class="prescriptions-show__label">{{ t('Aggiornato il') }}</span>
                        <span class="prescriptions-show__value">{{ dateTime(prescription.updated_at) ?? '--' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import OperationStatusBadge from '@/components/operations/OperationStatusBadge.vue';
import PrescriptionGenderBadge from '@/components/prescriptions/PrescriptionGenderBadge.vue';
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import type { Prescription } from '@/types/Prescription';
import { date, dateTime } from '@/utils/formatters/date';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Dettaglio prescrizione' }, () => [page]),
});

type Props = {
    prescription: Prescription;
};

defineProps<Props>();

const backToIndex = () => {
    router.get(
        route('workspace.prescriptions.index', {
            building: workspace.value?.slug,
        }),
    );
};
</script>

<style>
@reference '@/../css/base.css';

.prescriptions-show__content {
    @apply mt-6;
}

.prescriptions-show__details {
    @apply py-4;
}

.prescriptions-show__details-grid {
    @apply grid gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.prescriptions-show__details-item {
    @apply flex flex-col gap-1;
}

.prescriptions-show__label {
    @apply text-sm font-medium text-gray-500;
}

.prescriptions-show__value {
    @apply text-gray-900;
}
</style>
