<script setup lang="ts">
import { dateTime } from '@/utils/formatters/date';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Props = {
    title: string;
    empty: boolean;
    count?: number;
    updatedAt?: string | null;
    emptyLabel?: string;
    disabled?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    count: 0,
    updatedAt: null,
    emptyLabel: '',
    disabled: false,
});

const emit = defineEmits<{
    (e: 'click'): void;
}>();

const { t } = useI18n();

const resolvedEmptyLabel = computed(() => props.emptyLabel || t('Non ancora presente'));
const showCount = computed(() => !props.empty && props.count > 1);
const clickable = computed(() => !props.disabled && !props.empty);

const handleClick = () => {
    if (!clickable.value) return;
    emit('click');
};
</script>

<template>
    <button
        type="button"
        class="operations-overview__summary"
        :class="{ 'operations-overview__summary--clickable': clickable, 'operations-overview__summary--empty': empty }"
        :disabled="!clickable"
        @click="handleClick"
    >
        <div class="operations-overview__summary-header">
            <h3 class="operations-overview__summary-title">{{ title }}</h3>
            <span v-if="showCount" class="operations-overview__summary-count">{{ count }}</span>
        </div>

        <div v-if="empty" class="operations-overview__summary-empty">{{ resolvedEmptyLabel }}</div>
        <div v-else class="operations-overview__summary-body">
            <div class="operations-overview__summary-status">
                <slot name="status" />
            </div>
            <p v-if="updatedAt" class="operations-overview__summary-date">
                {{ t('Aggiornato il') }} {{ dateTime(updatedAt) }}
            </p>
        </div>
    </button>
</template>

<style>
@reference '@/../css/base.css';

.operations-overview__summary {
    @apply block w-full rounded-lg border border-gray-200 bg-white p-4 text-left;
}

.operations-overview__summary--clickable {
    @apply cursor-pointer transition hover:border-gray-300 hover:shadow-sm;
}

.operations-overview__summary--empty {
    @apply cursor-default;
}

.operations-overview__summary-header {
    @apply flex items-center justify-between gap-2;
}

.operations-overview__summary-title {
    @apply text-base font-semibold text-gray-900;
}

.operations-overview__summary-count {
    @apply rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700;
}

.operations-overview__summary-empty {
    @apply mt-2 text-sm italic text-gray-400;
}

.operations-overview__summary-body {
    @apply mt-2 flex flex-col gap-2;
}

.operations-overview__summary-status {
    @apply flex flex-wrap items-center gap-2;
}

.operations-overview__summary-date {
    @apply text-xs text-gray-500;
}
</style>
