<script setup lang="ts">
import axios from 'axios';
import { BbOffCanvas } from 'bitboss-ui';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type ActivityCauser = {
    id: number;
    name: string | null;
    surname: string | null;
};

type ActivityItem = {
    id: number;
    created_at: string;
    event: string | null;
    description: string | null;
    causer: ActivityCauser | null;
};

type Props = {
    title?: string;
    modelType: string;
    modelId: number;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Attività',
});

const modelValue = defineModel<boolean>('modelValue', {
    required: true,
    default: false,
});

const activities = ref<ActivityItem[]>([]);
const loading = ref(false);
const errorMessage = ref<string | null>(null);

const formatDateTime = (value: string) => {
    if (!value) {
        return '--';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '--';
    }

    return date.toLocaleString('it-IT', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const eventLabels: Record<string, string> = {
    prescription_revision_requested: 'Richiesta di revisione prescrizione',
    prescription_resubmitted: 'Prescrizione revisionata reinviata',
    prescription_confirmed: 'Prescrizione confermata',
};

const activityLabel = (activity: ActivityItem) => {
    if (activity.description && activity.description.trim() !== '') {
        return activity.description;
    }

    if (activity.event && eventLabels[activity.event]) {
        return eventLabels[activity.event];
    }

    return activity.event ?? '--';
};

const causerLabel = (activity: ActivityItem) => {
    if (!activity.causer) {
        return 'Sistema';
    }

    const fullName = [activity.causer.name, activity.causer.surname].filter(Boolean).join(' ').trim();
    return fullName || 'Sistema';
};

const loadActivities = async () => {
    loading.value = true;
    errorMessage.value = null;
    try {
        const response = await axios.get(route('activity-log.index'), {
            params: {
                model_type: props.modelType,
                model_id: props.modelId,
            },
        });

        activities.value = response.data.activities ?? [];
    } catch (error: any) {
        const status = error?.response?.status;
        if (status === 401) {
            errorMessage.value = 'Sessione scaduta. Ricarica la pagina.';
        } else if (status === 403) {
            errorMessage.value = 'Non hai i permessi per visualizzare le attività.';
        } else {
            errorMessage.value = 'Impossibile caricare le attività.';
        }

        activities.value = [];
    } finally {
        loading.value = false;
    }
};

watch(
    () => modelValue.value,
    async (isOpen) => {
        if (isOpen) {
            await loadActivities();
        }
    },
);

const hasEmptyState = computed(() => !loading.value && !errorMessage.value && activities.value.length === 0);
</script>

<template>
    <BbOffCanvas v-model="modelValue" direction="right" :title="props.title" overlay-classes="activity-slider-offcanvas">
        <div class="activity-slider">
            <div v-if="loading" class="activity-slider__empty">Caricamento attività...</div>
            <div v-else-if="errorMessage" class="activity-slider__empty">{{ errorMessage }}</div>
            <div v-else-if="hasEmptyState" class="activity-slider__empty">Nessuna attività per questo elemento.</div>

            <div v-else class="activity-slider__list">
                <div v-for="activity in activities" :key="activity.id" class="activity-slider__row">
                    <div class="activity-slider__meta">
                        <span class="activity-slider__date">{{ formatDateTime(activity.created_at) }}</span>
                        <span class="activity-slider__event">{{ activityLabel(activity) }}</span>
                    </div>
                    <div class="activity-slider__causer">
                        <span class="activity-slider__causer-label">{{ causerLabel(activity) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </BbOffCanvas>
</template>

<style>
@reference '@/../css/base.css';

.activity-slider-offcanvas {
    .bb-offcanvas__body {
        @apply flex h-full p-0;
    }
}

.activity-slider {
    @apply flex min-h-0 flex-1 flex-col bg-slate-50;
}

.activity-slider__empty {
    @apply p-4 text-sm text-slate-500;
}

.activity-slider__list {
    @apply flex-1 space-y-3 overflow-y-auto p-4;
}

.activity-slider__row {
    @apply rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-200;
}

.activity-slider__meta {
    @apply flex flex-col gap-1;
}

.activity-slider__date {
    @apply text-xs text-slate-500;
}

.activity-slider__event {
    @apply text-sm font-medium text-slate-900;
}

.activity-slider__causer {
    @apply mt-2 text-sm text-slate-700;
}

.activity-slider__causer-label {
    @apply font-medium;
}
</style>
