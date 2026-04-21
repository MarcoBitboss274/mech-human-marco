<script setup lang="ts">
import PrescriptionGenderBadge from '@/components/prescriptions/PrescriptionGenderBadge.vue';
import type { Operation } from '@/types/Operation';
import type { Prescription } from '@/types/Prescription';
import { date } from '@/utils/formatters/date';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PrescriptionStatusBadge from './PrescriptionStatusBadge.vue';

const { t } = useI18n();

type Props = {
    prescription: Prescription;
    operation: Operation;
};

const props = defineProps<Props>();

const typologyLabel = computed(() => {
    if (!props.prescription.typology) {
        return '--';
    }

    return t(props.prescription.typology);
});

const requesterFullName = computed(() => {
    if (!props.prescription.user) {
        return '--';
    }

    const fullName = [props.prescription.user.name, props.prescription.user.surname].filter(Boolean).join(' ');

    return fullName || '--';
});

const formatOdontogram = (value: number[] | null | undefined): string => {
    if (!value || value.length === 0) {
        return '--';
    }

    return value.join(', ');
};

const v = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '--';
    }

    return String(value);
};
</script>

<template>
    <div class="prescription-details-card">
        <section class="prescription-details-card__section">
            <div class="flex items-baseline justify-start gap-2">
                <h3 class="prescription-details-card__title">
                    {{ `${t('Lavorazione')} ${typologyLabel}` }}
                </h3>
                <PrescriptionStatusBadge :status="prescription.status" />
            </div>

            <div class="prescription-details-card__fields">
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Data invio') }}</span>
                    <span class="prescription-details-card__value">{{ date(prescription.send_at) ?? '--' }}</span>
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Data scadenza') }}</span>
                    <span class="prescription-details-card__value" :class="{ 'line-through': operation?.status === 'completed' }">{{
                        date(prescription.expire_at) ?? '--'
                    }}</span>
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Richiedente') }}</span>
                    <span class="prescription-details-card__value">{{ requesterFullName }}</span>
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Struttura') }}</span>
                    <span class="prescription-details-card__value">{{ prescription.building?.name ?? '--' }}</span>
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Riferimento interno') }}</span>
                    <span class="prescription-details-card__value">{{ prescription.ref ?? '--' }}</span>
                </div>
            </div>
        </section>

        <section class="prescription-details-card__section">
            <h3 class="prescription-details-card__title">{{ t('Dati paziente') }}</h3>
            <div class="prescription-details-card__fields">
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Nome') }}</span>
                    <span class="prescription-details-card__value">{{ prescription.name ?? '--' }}</span>
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Cognome') }}</span>
                    <span class="prescription-details-card__value">{{ prescription.surname ?? '--' }}</span>
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Sesso') }}</span>
                    <PrescriptionGenderBadge :gender="prescription.gender" />
                </div>
                <div class="prescription-details-card__field">
                    <span class="prescription-details-card__label">{{ t('Età') }}</span>
                    <span class="prescription-details-card__value">{{ prescription.age ?? '--' }}</span>
                </div>
            </div>
        </section>

        <section class="prescription-details-card__section">
            <h3 class="prescription-details-card__title">{{ `${t('Dati tecnici')} ${typologyLabel}` }}</h3>
            <div class="prescription-details-card__technical">
                <template v-if="prescription.typology === 'protrusor'">
                    <div class="prescription-details-card__fields">
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Tipologia protrusore') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.protrusor_typology) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Odontogramma') }}</span>
                            <span class="prescription-details-card__value">{{ formatOdontogram(prescription.protrusor_details?.odontogram) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Jig di Deprogrammazione muscolare') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.jig) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Denti residui arcata Superiore') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.remaining_upper_teeth) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Denti residui arcata Inferiore') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.remaining_lower_teeth) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Arco Palatale in metallo') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.transpalatal_arch) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Avanzamento mandibolare') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.mandibular_advancement) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Avanzamento mandibolare 2') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.protrusor_details?.mandibular_advancement_2) }}</span>
                        </div>
                    </div>
                </template>
                <template v-else-if="prescription.typology === 'lybra_aligner'">
                    <div class="prescription-details-card__fields">
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Cut line') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.lybra_aligner_details?.cut_line) }}</span>
                        </div>
                    </div>
                </template>
                <template v-else-if="prescription.typology === 'guided_surgery'">
                    <div class="prescription-details-card__fields">
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Tipologia di chirurgia') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.guided_surgery_details?.surgery_typology) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Odontogramma') }}</span>
                            <span class="prescription-details-card__value">{{
                                formatOdontogram(prescription.guided_surgery_details?.odontogram)
                            }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Linea implantare desiderata') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.guided_surgery_details?.desired_implant_line) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Informazioni aggiuntive sulla protes') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.guided_surgery_details?.additional_info) }}</span>
                        </div>
                    </div>
                </template>
                <template v-else-if="prescription.typology === '3d_mesh'">
                    <div class="prescription-details-card__fields">
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Dimensioni 3D Mesh') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.dimension) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Odontogramma') }}</span>
                            <span class="prescription-details-card__value">{{
                                formatOdontogram(prescription.three_d_mesh_details?.odontogram)
                            }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Finitura esterna') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.outer_finish) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Finitura interna') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.inner_finish) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Pattern') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.pattern) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Inviti a rottura') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.stress_breakers) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Modello 3D') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.['3d_model']) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Diametro delle viti da osteosintesi') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.screw_diameter) }}</span>
                        </div>
                        <div class="prescription-details-card__field md:col-span-2">
                            <span class="prescription-details-card__label">{{ t('Preferenze di giorni e orari per progettazione condivisa') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.three_d_mesh_details?.shared_project_note) }}</span>
                        </div>
                    </div>
                </template>
                <template v-else-if="prescription.typology === 'prosthesis'">
                    <div class="prescription-details-card__fields">
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Tipologia di protesi') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.prosthesis_details?.typology) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Dettaglio Corone e Ponti') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.prosthesis_details?.crowns_and_bridges_details) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Dettaglio Full-arch') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.prosthesis_details?.full_bridge_details) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Odontogramma') }}</span>
                            <span class="prescription-details-card__value">{{ formatOdontogram(prescription.prosthesis_details?.odontogram) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Modello 3D normal per corone e ponti') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.prosthesis_details?.['3d_normal_model']) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Modello 3D excellent per arcate complete') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.prosthesis_details?.['3d_excellent_model']) }}</span>
                        </div>
                    </div>
                </template>
                <template v-else-if="prescription.typology === 'semi_finished_prostheses'">
                    <div class="prescription-details-card__fields">
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Tipologia del semilavorato') }}</span>
                            <span class="prescription-details-card__value">{{ v(prescription.semi_finished_prostheses_details?.typology) }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Dettaglio Corone e Ponti') }}</span>
                            <span class="prescription-details-card__value">{{
                                v(prescription.semi_finished_prostheses_details?.crowns_and_bridges_details)
                            }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Dettaglio Full-arch') }}</span>
                            <span class="prescription-details-card__value">{{
                                v(prescription.semi_finished_prostheses_details?.full_bridge_details)
                            }}</span>
                        </div>
                        <div class="prescription-details-card__field">
                            <span class="prescription-details-card__label">{{ t('Odontogramma') }}</span>
                            <span class="prescription-details-card__value">
                                {{ formatOdontogram(prescription.semi_finished_prostheses_details?.odontogram) }}
                            </span>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="prescription-details-card__placeholder">{{ t('Placeholder') }}</div>
                </template>
            </div>
        </section>

        <section class="prescription-details-card__section prescription-details-card__section--last">
            <h3 class="prescription-details-card__title">{{ t('Allegati') }}</h3>
        </section>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.prescription-details-card {
    @apply rounded-xl border border-gray-200 bg-white shadow-sm;
}

.prescription-details-card__section {
    @apply border-b border-gray-200 p-5;
}

.prescription-details-card__section--last {
    @apply border-b-0;
}

.prescription-details-card__title {
    @apply mb-4 text-base font-semibold text-gray-900;
}

.prescription-details-card__fields {
    @apply grid gap-4 md:grid-cols-2 lg:grid-cols-3;
}

.prescription-details-card__field {
    @apply flex flex-col gap-1;
}

.prescription-details-card__label {
    @apply text-sm font-medium text-gray-500;
}

.prescription-details-card__value {
    @apply text-gray-900;
}

.prescription-details-card__technical {
    @apply min-h-16;
}

.prescription-details-card__placeholder {
    @apply text-sm text-gray-500;
}
</style>
