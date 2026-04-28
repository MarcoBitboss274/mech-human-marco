<template>
    <div v-if="latestPrescription" class="operations-show__details">
        <div class="operations-show__details-actions">
            <BbButton v-if="canEdit" size="xs" icon="pencil" :disabled="processing" @click="openEditWizard">
                {{ t('Modifica') }}
            </BbButton>
            <BbButton
                v-if="hasActiveRevision"
                append:icon="play"
                size="xs"
                :disabled="processing"
                @click="submitRevision"
            >
                {{ t('Invia modifiche') }}
            </BbButton>
            <BbButton
                v-if="canSubmitDraft"
                append:icon="play"
                size="xs"
                :disabled="processing"
                @click="openSubmitConfirmation"
            >
                {{ t('Invia prescrizione') }}
            </BbButton>
            <BbButton
                v-if="canConfirm"
                append:icon="play"
                size="xs"
                :disabled="processing"
                @click="confirmPrescription"
            >
                {{ t('Conferma presa in carico') }}
            </BbButton>
            <BbButton
                v-if="canRequestRevision"
                variant="outline"
                size="xs"
                :disabled="processing"
                @click="openRevisionDialog('open')"
            >
                {{ t('Richiedi revisione') }}
            </BbButton>
            <BbButton
                v-if="canAddReason"
                variant="outline"
                size="xs"
                :disabled="processing"
                @click="openRevisionDialog('add')"
            >
                {{ t('Chiedi nuove modifiche') }}
            </BbButton>
            <BbButton
                v-if="hasActiveRevision"
                size="xs"
                :disabled="processing"
                @click="openCloseRevisionDialog"
            >
                {{ t('Chiudi revisione') }}
            </BbButton>
        </div>
        <div v-if="showNewChangesBadge" class="operations-show__new-changes-banner">
            <span class="operations-show__new-changes-banner-title">{{ t('Nuove modifiche dal customer') }}</span>
            <span class="operations-show__new-changes-banner-date">
                {{ t('Inviate il') }} {{ formatDate(activeRevision?.last_submitted_at) }}
            </span>
        </div>
        <div v-if="hasActiveRevision" class="operations-show__revision-card">
            <p class="operations-show__revision-card-title">
                {{ t('Storico motivi revisione') }}
            </p>
            <ul class="operations-show__revision-card-list">
                <li
                    v-for="reason in activeRevisionReasons"
                    :key="reason.id"
                    class="operations-show__revision-card-item"
                >
                    <p class="operations-show__revision-card-date">{{ formatDate(reason.created_at) }}</p>
                    <p class="operations-show__revision-card-text">{{ reason.content }}</p>
                </li>
            </ul>
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

    <BbDialog v-model="revisionDialog" size="md" :title="revisionDialogTitle">
        <div class="operations-show__revision-dialog">
            <BbTextarea
                v-model="revisionReason"
                autocomplete="off"
                :label="t('Motivo della revisione')"
                :placeholder="t('Descrivi cosa deve essere corretto...')"
                :errors="revisionReasonErrors"
            />
            <p class="operations-show__revision-dialog-hint">
                {{ revisionReason.length }}/2000
            </p>
            <div class="operations-show__revision-dialog-actions">
                <BbButton type="button" variant="outline" :disabled="processing" @click="closeRevisionDialog">
                    {{ t('Annulla') }}
                </BbButton>
                <BbButton
                    type="button"
                    :disabled="!revisionReasonValid || processing"
                    @click="submitRevisionRequest"
                >
                    {{ t('Invia richiesta') }}
                </BbButton>
            </div>
        </div>
    </BbDialog>

    <BbDialog v-model="closeRevisionDialog" size="md" :title="t('Chiudi revisione')">
        <div class="operations-show__revision-dialog">
            <p>{{ t('Vuoi chiudere la revisione? Lo stato della prescrizione non verrà modificato.') }}</p>
            <div class="operations-show__revision-dialog-actions">
                <BbButton type="button" variant="outline" :disabled="processing" @click="closeRevisionDialog = false">
                    {{ t('Annulla') }}
                </BbButton>
                <BbButton type="button" :disabled="processing" @click="submitCloseRevision">
                    {{ t('Chiudi revisione') }}
                </BbButton>
            </div>
        </div>
    </BbDialog>
</template>

<script setup lang="ts">
import PrescriptionDetailsCard from '@/components/prescriptions/PrescriptionDetailsCard.vue';
import { useMainToast } from '@/composables/useMainToast';
import type { Operation } from '@/types/Operation';
import type { Prescription, PrescriptionRevisionReason } from '@/types/Prescription';
import { router } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbDialog, BbTextarea } from 'bitboss-ui';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { success, error } = useMainToast();

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

const revisionDialog = ref(false);
const revisionDialogMode = ref<'open' | 'add'>('open');
const revisionReason = ref('');
const revisionReasonErrors = ref<string[]>([]);
const closeRevisionDialog = ref(false);

const activeRevision = computed(() => latestPrescription.value?.active_revision ?? null);
const hasActiveRevision = computed(() => !!activeRevision.value);
const activeRevisionReasons = computed<PrescriptionRevisionReason[]>(() => activeRevision.value?.reasons ?? []);

const canEdit = computed(() => {
    const status = latestPrescription.value?.status;
    return status === 'draft' || hasActiveRevision.value;
});

const canSubmitDraft = computed(() => latestPrescription.value?.status === 'draft' && !hasActiveRevision.value);

const canConfirm = computed(() => latestPrescription.value?.status === 'sent' && !hasActiveRevision.value);

const canRequestRevision = computed(() => {
    const status = latestPrescription.value?.status;
    if (hasActiveRevision.value) return false;
    return status === 'sent' || status === 'confirmed';
});

const canAddReason = computed(() => hasActiveRevision.value);

const showNewChangesBadge = computed(() => {
    const rev = activeRevision.value;
    if (!rev?.last_submitted_at) return false;
    const lastReason = rev.reasons[rev.reasons.length - 1];
    const acknowledgement = lastReason?.created_at ?? rev.opened_at;
    return new Date(rev.last_submitted_at).getTime() > new Date(acknowledgement).getTime();
});

const revisionDialogTitle = computed(() =>
    revisionDialogMode.value === 'open'
        ? t('Richiedi revisione al customer')
        : t('Chiedi nuove modifiche al customer'),
);

const revisionReasonValid = computed(() => {
    const length = revisionReason.value.trim().length;
    return length >= 5 && length <= 2000;
});

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

const openRevisionDialog = (mode: 'open' | 'add') => {
    if (processing.value) return;

    revisionDialogMode.value = mode;
    revisionReason.value = '';
    revisionReasonErrors.value = [];
    revisionDialog.value = true;
};

const closeRevisionDialogFn = () => {
    revisionDialog.value = false;
};

watch(revisionDialog, (isOpen) => {
    if (!isOpen) {
        revisionReason.value = '';
        revisionReasonErrors.value = [];
    }
});

const openCloseRevisionDialog = () => {
    if (processing.value) return;
    closeRevisionDialog.value = true;
};

const openEditWizard = () => {
    if (processing.value) {
        return;
    }

    router.get(route('operations.edit-wizard', { operation: props.operation.id }));
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
    router.post(
        route('prescriptions.send', { prescription: latestPrescription.value.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Prescrizione inviata con successo');
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

const confirmPrescription = () => {
    if (!latestPrescription.value?.id || processing.value) {
        return;
    }

    processing.value = true;
    router.post(
        route('prescriptions.confirm', { prescription: latestPrescription.value.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Presa in carico confermata con successo');
                emit('updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const submitRevisionRequest = () => {
    if (!latestPrescription.value?.id || processing.value) {
        return;
    }

    if (!revisionReasonValid.value) {
        return;
    }

    const routeName =
        revisionDialogMode.value === 'open'
            ? 'prescriptions.revisions.open'
            : 'prescriptions.revisions.add-reason';

    processing.value = true;
    revisionReasonErrors.value = [];
    router.post(
        route(routeName, { prescription: latestPrescription.value.id }),
        { reason: revisionReason.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                success(
                    revisionDialogMode.value === 'open'
                        ? 'Revisione aperta e notificata al customer'
                        : 'Nuove modifiche richieste al customer',
                );
                closeRevisionDialogFn();
                emit('updated');
            },
            onError: (errors: Record<string, string>) => {
                if (errors?.reason) {
                    revisionReasonErrors.value = [errors.reason];
                    return;
                }
                error(errors?.prescription ?? 'Si è verificato un errore');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const submitRevision = () => {
    if (!latestPrescription.value?.id || processing.value) {
        return;
    }

    processing.value = true;
    router.post(
        route('prescriptions.send', { prescription: latestPrescription.value.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Revisione inviata');
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

const submitCloseRevision = () => {
    if (!latestPrescription.value?.id || processing.value) {
        return;
    }

    processing.value = true;
    router.post(
        route('prescriptions.revisions.close', { prescription: latestPrescription.value.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success('Revisione chiusa');
                closeRevisionDialog.value = false;
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

.operations-show__dialog {
    @apply flex flex-col gap-4;
}

.operations-show__dialog-grid {
    @apply grid gap-4 sm:grid-cols-2;
}

.operations-show__dialog-actions {
    @apply flex justify-end gap-2;
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

.operations-show__new-changes-banner {
    @apply mb-4 flex w-full items-center justify-between rounded-md border border-amber-500 bg-amber-100 px-4 py-2 text-sm text-amber-800;
}

.operations-show__new-changes-banner-title {
    @apply font-semibold;
}

.operations-show__new-changes-banner-date {
    @apply text-xs text-amber-700;
}

.operations-show__revision-card {
    @apply mb-4 rounded-md border border-orange-400 bg-orange-50 p-4;
}

.operations-show__revision-card-title {
    @apply text-sm font-semibold text-orange-800;
}

.operations-show__revision-card-list {
    @apply mt-2 flex flex-col gap-2;
}

.operations-show__revision-card-item {
    @apply rounded-md border border-orange-200 bg-white px-4 py-2;
}

.operations-show__revision-card-date {
    @apply text-xs text-orange-700;
}

.operations-show__revision-card-text {
    @apply whitespace-pre-line text-sm text-orange-900;
}

.operations-show__revision-dialog {
    @apply flex flex-col gap-4;
}

.operations-show__revision-dialog-hint {
    @apply text-right text-xs text-gray-500;
}

.operations-show__revision-dialog-actions {
    @apply flex justify-end gap-2;
}
</style>
