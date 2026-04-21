<template>
    <div class="attachments-step">
        <div class="attachments-step__mode">
            <div
                class="attachments-step__mode-option"
                :class="{ selected: manual === false }"
                role="button"
                tabindex="0"
                @click="setManual(false)"
                @keydown.enter.prevent="setManual(false)"
            >
                {{ t('Invio digitale') }}
            </div>
            <div
                class="attachments-step__mode-option"
                :class="{ selected: manual === true }"
                role="button"
                tabindex="0"
                @click="setManual(true)"
                @keydown.enter.prevent="setManual(true)"
            >
                {{ t('Invio analogico') }}
            </div>
        </div>

        <div v-if="!form.typology" class="create-wizard__empty-step">
            {{ t('Seleziona prima una tipologia per vedere gli allegati richiesti') }}
        </div>
        <template v-else>
            <ProtrusorAttachmentsStep v-if="form.typology === 'protrusor'" :form="form" :manual="manual" />
            <LybraAlignerAttachmentsStep v-else-if="form.typology === 'lybra_aligner'" :form="form" :manual="manual" />
            <GuidedSurgeryAttachmentsStep v-else-if="form.typology === 'guided_surgery'" :form="form" :manual="manual" />
            <ThreeDMeshAttachmentsStep v-else-if="form.typology === '3d_mesh'" :form="form" :manual="manual" />
            <ProsthesisAttachmentsStep v-else-if="form.typology === 'prosthesis'" :form="form" :manual="manual" />
            <SemiFinishedProsthesesAttachmentsStep v-else-if="form.typology === 'semi_finished_prostheses'" :form="form" :manual="manual" />
            <div v-else class="create-wizard__empty-step">
                {{ t('Tipologia non supportata per gli allegati') }}
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import GuidedSurgeryAttachmentsStep from '@/components/operations/wizard/prescription-attachments/GuidedSurgeryAttachmentsStep.vue';
import LybraAlignerAttachmentsStep from '@/components/operations/wizard/prescription-attachments/LybraAlignerAttachmentsStep.vue';
import ProsthesisAttachmentsStep from '@/components/operations/wizard/prescription-attachments/ProsthesisAttachmentsStep.vue';
import ProtrusorAttachmentsStep from '@/components/operations/wizard/prescription-attachments/ProtrusorAttachmentsStep.vue';
import SemiFinishedProsthesesAttachmentsStep from '@/components/operations/wizard/prescription-attachments/SemiFinishedProsthesesAttachmentsStep.vue';
import ThreeDMeshAttachmentsStep from '@/components/operations/wizard/prescription-attachments/ThreeDMeshAttachmentsStep.vue';
import type { OperationCreateWizardForm } from '@/types/Operation';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    form: OperationCreateWizardForm;
};

const props = defineProps<Props>();

const manual = computed<boolean | null>({
    get: () => props.form.manual,
    set: (value) => {
        props.form.manual = value;
    },
});

const setManual = (value: boolean) => {
    manual.value = value;
};
</script>

<style>
@reference '@/../css/base.css';

.attachments-step__mode {
    @apply mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2;
}

.attachments-step__mode-option {
    @apply w-full cursor-pointer rounded-lg border border-gray-200 bg-white p-2 text-center font-semibold text-gray-700 transition;
}

.attachments-step__mode-option:hover {
    @apply border-gray-300 bg-gray-50;
}

.attachments-step__mode-option.selected {
    @apply border-gray-900 bg-gray-900 text-white;
}
</style>
