<template>
    <nav aria-label="Progress" class="operation-progress">
        <ol role="list" class="operation-progress__list">
            <li v-for="(step, index) in steps" :key="step.status" class="operation-progress__item">
                <span :class="['operation-progress__label', step.stepState !== 'upcoming' && 'operation-progress__label--active']">
                    {{ step.label }}
                </span>

                <div class="operation-progress__track">
                    <div
                        v-if="index > 0"
                        :class="[
                            'operation-progress__line',
                            steps[index - 1]?.stepState === 'completed'
                                ? 'operation-progress__line--completed'
                                : 'operation-progress__line--upcoming',
                        ]"
                    />
                    <div
                        :class="[
                            'operation-progress__circle',
                            step.stepState === 'completed' && 'operation-progress__circle--completed',
                            step.stepState === 'current' && 'operation-progress__circle--current',
                            step.stepState === 'upcoming' && 'operation-progress__circle--upcoming',
                        ]"
                        :aria-current="step.stepState === 'current' ? 'step' : undefined"
                    >
                        <span
                            v-if="step.stepState !== 'upcoming'"
                            aria-hidden="true"
                            :class="[
                                'operation-progress__dot',
                                step.stepState === 'completed' && 'operation-progress__dot--completed',
                                step.stepState === 'current' && 'operation-progress__dot--current',
                            ]"
                        />
                        <span class="sr-only">{{ step.label }}</span>
                    </div>
                    <div
                        v-if="index < steps.length - 1"
                        :class="[
                            'operation-progress__line',
                            step.stepState === 'completed' ? 'operation-progress__line--completed' : 'operation-progress__line--upcoming',
                        ]"
                    />
                </div>
            </li>
        </ol>
    </nav>
</template>

<script setup lang="ts">
import type { Operation } from '@/types/Operation';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const statusSequence = ['draft', 'requested', 'in_progress', 'waiting_approval', 'production', 'completed'] as const;

const statusLabels: Record<(typeof statusSequence)[number], string> = {
    draft: 'Bozza',
    requested: 'Richiesta',
    in_progress: 'In lavorazione',
    waiting_approval: 'In approvazione',
    production: 'Produzione',
    completed: 'Completata',
};

const legacyStatusMap: Record<string, (typeof statusSequence)[number] | null> = {
    pending: 'requested',
    cancelled: null,
};

type StepState = 'completed' | 'current' | 'upcoming';

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();

const steps = computed(() => {
    const rawStatus = props.operation?.status ?? null;
    const status = rawStatus
        ? statusSequence.includes(rawStatus as (typeof statusSequence)[number])
            ? (rawStatus as (typeof statusSequence)[number])
            : (legacyStatusMap[rawStatus] ?? null)
        : null;
    const currentIndex = status ? statusSequence.indexOf(status) : -1;

    return statusSequence.map((stepStatus, index) => {
        let stepState: StepState = 'upcoming';
        if (currentIndex >= 0) {
            if (index < currentIndex) stepState = 'completed';
            else if (index === currentIndex) stepState = 'current';
        }

        return {
            status: stepStatus,
            label: t(statusLabels[stepStatus]),
            stepState,
        };
    });
});
</script>

<style>
@reference '@/../css/base.css';

.operation-progress__list {
    @apply flex items-stretch;
}

.operation-progress__item {
    @apply flex flex-1 flex-col items-center gap-2;
}

.operation-progress__item:first-child {
    @apply items-start;
}

.operation-progress__item:first-child .operation-progress__track {
    @apply pl-0;
}

.operation-progress__item:last-child {
    @apply items-end;
}

.operation-progress__item:last-child .operation-progress__track {
    @apply pr-0;
}

.operation-progress__label {
    @apply text-sm font-medium whitespace-nowrap text-gray-400;
}

.operation-progress__label--active {
    @apply text-gray-900;
}

.operation-progress__track {
    @apply flex w-full items-center;
}

.operation-progress__line {
    @apply h-0.5 flex-1;
}

.operation-progress__line--completed {
    @apply bg-amber-400;
}

.operation-progress__line--upcoming {
    @apply bg-gray-200;
}

.operation-progress__circle {
    @apply flex size-7 shrink-0 items-center justify-center rounded-full border-2;
}

.operation-progress__circle--completed {
    @apply border-amber-400;
}

.operation-progress__circle--current {
    @apply border-blue-500;
}

.operation-progress__circle--upcoming {
    @apply border-gray-300;
}

.operation-progress__dot {
    @apply size-2.5 rounded-full;
}

.operation-progress__dot--completed {
    @apply bg-amber-400;
}

.operation-progress__dot--current {
    @apply bg-blue-500;
}
</style>
