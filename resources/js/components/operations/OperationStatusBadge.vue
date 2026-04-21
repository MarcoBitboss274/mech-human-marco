<script setup lang="ts">
import { useSelect } from '@/composables/useSelect';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbSelect } from 'bitboss-ui';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { select: selectOperationStatuses } = useSelect('operation-statuses');

type Props = {
    id?: number | null;
    status: string | null | undefined;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | undefined;
};

const props = withDefaults(defineProps<Props>(), {
    id: null,
});

const isEditMode = computed(() => props.id !== null);

const form = useForm<{ status: string | null }>({
    status: props.status ?? null,
});

const confirmModal = ref(false);
const pendingStatus = ref<string | null>(null);
const internalStatusSync = ref(false);

watch(
    () => props.status,
    (nextStatus) => {
        if (nextStatus === form.status) {
            return;
        }

        internalStatusSync.value = true;
        form.status = nextStatus ?? null;
    },
);

watch(
    () => form.status,
    (nextStatus, previousStatus) => {
        if (!isEditMode.value) {
            return;
        }

        if (internalStatusSync.value) {
            internalStatusSync.value = false;
            return;
        }

        if (nextStatus === previousStatus) {
            return;
        }

        pendingStatus.value = nextStatus ?? null;
        internalStatusSync.value = true;
        form.status = previousStatus ?? null;
        confirmModal.value = true;
    },
);

const cancelChange = () => {
    confirmModal.value = false;
    pendingStatus.value = null;
};

const confirmChange = () => {
    if (!props.id) {
        cancelChange();
        return;
    }

    if (pendingStatus.value === null) {
        cancelChange();
        return;
    }

    const previousStatus = form.status ?? null;
    const nextStatus = pendingStatus.value;

    internalStatusSync.value = true;
    form.status = nextStatus;

    form.patch(route('operations.status', { operation: props.id }), {
        preserveScroll: true,
        onSuccess: () => {
            cancelChange();
        },
        onError: () => {
            internalStatusSync.value = true;
            form.status = previousStatus;
            cancelChange();
        },
    });
};

const statusVariantClass = computed(() => {
    const currentStatus = props.status ?? null;

    switch (currentStatus) {
        case 'draft':
            return '!bg-gray-200 border-gray-500 !text-gray-700';
        case 'requested':
            return '!bg-yellow-200 border-yellow-500 !text-yellow-700';
        case 'in_progress':
            return '!bg-amber-200 border-amber-500 !text-amber-700';
        case 'waiting_approval':
            return '!bg-orange-200 border-orange-500 !text-orange-700';
        case 'production':
            return '!bg-purple-200 border-purple-500 !text-purple-700';
        case 'completed':
            return '!bg-green-200 border-green-500 !text-green-600';
        default:
            return '';
    }
});

const editVariantClass = computed(() => {
    const currentStatus = form.status ?? props.status ?? 'default';
    return `operation-status-badge-edit--${currentStatus}`;
});

const classes = computed(() => ({
    'px-3 py-1 rounded-md bg-gray-100 text-gray-500 w-fit text-sm leading-none flex items-center justify-center border whitespace-nowrap': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    [statusVariantClass.value]: true,
}));

const text = computed(() => {
    switch (props.status) {
        case 'draft':
            return t('Bozza');
        case 'requested':
            return t('Richiesta');
        case 'in_progress':
            return t('In lavorazione');
        case 'waiting_approval':
            return t('In approvazione');
        case 'production':
            return t('Produzione');
        case 'completed':
            return t('Completato');
        default:
            return '--';
    }
});
</script>

<template>
    <div v-if="!isEditMode" :class="classes">
        {{ text }}
    </div>

    <div v-else :class="['operation-status-badge-edit', editVariantClass]">
        <BbSelect
            v-model="form.status"
            class="operation-status-badge-edit__select"
            item-text="label"
            item-value="value"
            :items="selectOperationStatuses"
            :label="t('Stato')"
            :errors="form.errors?.status"
            :disabled="form.processing"
            :hide-label="true"
            :allow-writing="false"
        />

        <BbDialog v-model="confirmModal" size="sm" :title="t('Cambia stato')">
            <div class="operation-status-badge-edit__dialog">
                <p class="operation-status-badge-edit__dialog-text">
                    {{ t('Sei sicuro di voler cambiare stato?') }}
                </p>
                <div class="operation-status-badge-edit__dialog-actions">
                    <BbButton type="button" variant="outline" @click="cancelChange">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="button" :disabled="form.processing" @click="confirmChange">
                        {{ t('Procedi') }}
                    </BbButton>
                </div>
            </div>
        </BbDialog>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operation-status-badge-edit {
    --status-bg: #f3f4f6;
    --status-border: #9ca3af;
    --status-text: #4b5563;
    @apply w-[190px] max-w-[190px] min-w-[190px];
}

.operation-status-badge-edit--draft {
    --status-bg: #e5e7eb;
    --status-border: #6b7280;
    --status-text: #374151;
}

.operation-status-badge-edit--requested {
    --status-bg: #fef08a;
    --status-border: #eab308;
    --status-text: #a16207;
}

.operation-status-badge-edit--in_progress {
    --status-bg: #fde68a;
    --status-border: #f59e0b;
    --status-text: #b45309;
}

.operation-status-badge-edit--waiting_approval {
    --status-bg: #fed7aa;
    --status-border: #f97316;
    --status-text: #c2410c;
}

.operation-status-badge-edit--production {
    --status-bg: #e9d5ff;
    --status-border: #a855f7;
    --status-text: #7e22ce;
}

.operation-status-badge-edit--completed {
    --status-bg: #bbf7d0;
    --status-border: #22c55e;
    --status-text: #15803d;
}

.operation-status-badge-edit__select {
    @apply w-[190px] max-w-[190px] min-w-[190px] cursor-pointer;
}

.operation-status-badge-edit__select .bb-base-input-container__input {
    @apply h-auto! cursor-pointer rounded-md border px-3! py-1! text-sm leading-none;
    background-color: var(--status-bg);
    border-color: var(--status-border);
    color: var(--status-text);
}

.operation-status-badge-edit__select .bb-common-input-inner-container,
.operation-status-badge-edit__select .bb-base-select,
.operation-status-badge-edit__select .bb-base-select__input-container,
.operation-status-badge-edit__select .bb-base-select__text-input {
    background-color: var(--status-bg);
    color: var(--status-text);
    font-size: 0.875rem;
    @apply h-[18px]! min-h-0! cursor-pointer border-none! p-0! ring-0!;
}

.operation-status-badge-edit__select .bb-base-select__text-input::placeholder {
    color: var(--status-text);
    opacity: 0.7;
}

.operation-status-badge-edit__select .bb-base-select__chevron,
.operation-status-badge-edit__select .bb-clearable-button,
.operation-status-badge-edit__select .bb-clearable-button svg {
    color: var(--status-text);
    @apply m-0!;
}

.operation-status-badge-edit__select .bb-base-input-container__input:hover {
    border-color: var(--status-border);
}

.operation-status-badge-edit__select .bb-base-input-container__input:focus-within {
    border-color: var(--status-border);
    box-shadow: 0 0 0 2px color-mix(in oklab, var(--status-border) 25%, transparent);
}

.operation-status-badge-edit__dialog {
    @apply flex flex-col gap-4;
}

.operation-status-badge-edit__dialog-text {
    @apply text-sm text-gray-700;
}

.operation-status-badge-edit__dialog-actions {
    @apply flex items-center justify-end gap-2;
}
</style>
