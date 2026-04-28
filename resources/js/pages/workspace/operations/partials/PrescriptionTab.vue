<template>
    <div v-if="latestPrescription" class="operations-show__details">
        <div class="operations-show__details-actions">
            <BbButton v-if="canEdit" size="xs" icon="pencil" @click="openEditWizard">
                {{ t('Modifica') }}
            </BbButton>
            <BbButton
                v-if="canSubmit"
                append:icon="play"
                size="xs"
                :disabled="processing"
                @click="openSubmitConfirmation"
            >
                {{ submitLabel }}
            </BbButton>
        </div>

        <div v-if="hasActiveRevision" class="operations-show__revision-banner">
            <p class="operations-show__revision-banner-title">
                {{ t('Revisione richiesta dall\'amministrazione') }}
            </p>
            <ul class="operations-show__revision-banner-list">
                <li
                    v-for="reason in activeRevisionReasons"
                    :key="reason.id"
                    class="operations-show__revision-banner-item"
                >
                    <p class="operations-show__revision-banner-date">{{ formatDate(reason.created_at) }}</p>
                    <p class="operations-show__revision-banner-text">{{ reason.content }}</p>
                </li>
            </ul>
            <p class="operations-show__revision-banner-hint">
                {{ t('Applica le modifiche richieste e reinvia la prescrizione.') }}
            </p>
        </div>

        <div class="operations-show__details-grid">
            <div class="operations-show__details-main">
                <PrescriptionDetailsCard :prescription="latestPrescription" :operation="operation" />
            </div>
            <div class="operations-show__details-aside">
                <div>
                    <p class="operations-show__details-aside-title">{{ t('Fatturazione') }}</p>
                </div>
                <div class="separator"></div>
                <div>
                    <p class="operations-show__details-aside-title">{{ t('Indirizzo di spedizione') }}</p>
                </div>
                <div class="separator"></div>
                <div>
                    <p class="operations-show__details-aside-title">{{ t('Informazioni aggiuntive') }}</p>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="operations-show__empty">
        {{ t('Nessuna prescrizione collegata') }}
    </div>

    <BbDialog v-model="confirmSubmitModal" size="md" :title="t('Disclaimer Legale e Consenso')">
        <div class="create-wizard__disclaimer-dialog">
            <p class="create-wizard__disclaimer-text">{{ t('Disclaimer legale lavorazione') }}</p>
            <BbCheckbox v-model="legalConsentChecked" :label="t('Dichiaro di aver letto e accettato i termini e le condizioni.')" />
            <div class="create-wizard__disclaimer-actions">
                <BbButton type="button" variant="outline" @click="closeSubmitConfirmation">
                    {{ t('Annulla') }}
                </BbButton>
                <BbButton type="button" :disabled="!legalConsentChecked || processing" @click="confirmAndSendPrescription">
                    {{ t('Conferma e invia') }}
                </BbButton>
            </div>
        </div>
    </BbDialog>
</template>

<script setup lang="ts">
import PrescriptionDetailsCard from '@/components/prescriptions/PrescriptionDetailsCard.vue';
import { useMainToast } from '@/composables/useMainToast';
import { useWorkspace } from '@/composables/useWorkspace';
import type { Operation } from '@/types/Operation';
import type { Prescription, PrescriptionRevisionReason } from '@/types/Prescription';
import { router } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbDialog } from 'bitboss-ui';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { success, error } = useMainToast();
const { workspace } = useWorkspace();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'updated'): void;
}>();

const latestPrescription = computed<Prescription | undefined>(() => props.operation.prescriptions?.[0]);
const processing = ref(false);
const confirmSubmitModal = ref(false);
const legalConsentChecked = ref(false);

const activeRevision = computed(() => latestPrescription.value?.active_revision ?? null);
const hasActiveRevision = computed(() => !!activeRevision.value);
const activeRevisionReasons = computed<PrescriptionRevisionReason[]>(() => activeRevision.value?.reasons ?? []);

const canEdit = computed(() => {
    const status = latestPrescription.value?.status;
    return status === 'draft' || hasActiveRevision.value;
});

const canSubmit = computed(() => {
    const status = latestPrescription.value?.status;
    return status === 'draft' || hasActiveRevision.value;
});

const submitLabel = computed(() =>
    hasActiveRevision.value ? t('Invia modifiche') : t('Invia prescrizione'),
);

const formatDate = (value: string | null | undefined): string => {
    if (!value) return '--';
    return new Date(value).toLocaleString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const openSubmitConfirmation = () => {
    if (processing.value) return;

    confirmSubmitModal.value = true;
};

const closeSubmitConfirmation = () => {
    confirmSubmitModal.value = false;
};

watch(confirmSubmitModal, (isOpen) => {
    if (!isOpen) {
        legalConsentChecked.value = false;
    }
});

const openEditWizard = () => {
    router.get(
        route('workspace.operations.edit-wizard', {
            building: workspace.value?.slug,
            operation: props.operation.id,
        }),
    );
};

const confirmAndSendPrescription = () => {
    if (!latestPrescription.value?.id || processing.value) {
        return;
    }

    if (!legalConsentChecked.value) {
        return;
    }

    closeSubmitConfirmation();
    processing.value = true;
    const isRevision = hasActiveRevision.value;
    router.post(
        route('workspace.prescriptions.send', {
            building: workspace.value?.slug,
            prescription: latestPrescription.value.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success(isRevision ? 'Modifiche inviate' : 'Prescrizione inviata con successo');
                emit('updated');
            },
            onError: (errors: Record<string, string>) => {
                error(errors?.prescription ?? 'Si è verificato un errore');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>

<style>
@reference '@/../css/base.css';

.operations-show__empty {
    @apply py-8 text-center text-gray-500;
}

.operations-show__details {
    @apply py-4;
}

.operations-show__details-actions {
    @apply mb-4 flex justify-end gap-2;
}

.operations-show__details-grid {
    @apply grid gap-4 xl:grid-cols-3;
}

.operations-show__details-main {
    @apply xl:col-span-2;
}

.operations-show__details-aside {
    @apply xl:col-span-1;
}

.operations-show__details-aside-title {
    @apply text-lg font-semibold text-gray-900;
}

.operations-show__revision-banner {
    @apply mb-4 rounded-md border border-orange-400 bg-orange-50 p-4;
}

.operations-show__revision-banner-title {
    @apply text-sm font-semibold text-orange-800;
}

.operations-show__revision-banner-list {
    @apply mt-2 flex flex-col gap-2;
}

.operations-show__revision-banner-item {
    @apply rounded-md border border-orange-200 bg-white px-4 py-2;
}

.operations-show__revision-banner-date {
    @apply text-xs text-orange-700;
}

.operations-show__revision-banner-text {
    @apply whitespace-pre-line text-sm text-orange-900;
}

.operations-show__revision-banner-hint {
    @apply mt-2 text-xs text-orange-700;
}
</style>
