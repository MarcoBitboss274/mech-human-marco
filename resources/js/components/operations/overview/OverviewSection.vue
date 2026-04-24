<script setup lang="ts">
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

type Props = {
    title: string;
    goToLabel?: string;
    goToDisabled?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    goToLabel: '',
    goToDisabled: false,
});

const emit = defineEmits<{
    (e: 'go-to'): void;
}>();

const { t } = useI18n();

const resolvedGoToLabel = () => props.goToLabel || t('Vai alla scheda');

const handleGoTo = () => {
    if (props.goToDisabled) return;
    emit('go-to');
};
</script>

<template>
    <section class="overview-section">
        <header class="overview-section__header">
            <div class="overview-section__title-group">
                <h3 class="overview-section__title">{{ title }}</h3>
                <BbButton
                    class="overview-section__arrow-btn"
                    icon="arrow-right"
                    size="xs"
                    type="button"
                    :disabled="goToDisabled"
                    :title="resolvedGoToLabel()"
                    :aria-label="resolvedGoToLabel()"
                    @click="handleGoTo"
                />
            </div>
            <div v-if="$slots.counters" class="overview-section__counters">
                <slot name="counters" />
            </div>
        </header>

        <div class="overview-section__body">
            <slot />
        </div>
    </section>
</template>

<style>
@reference '@/../css/base.css';

.overview-section {
    @apply flex flex-col gap-2 border-b border-gray-200 py-8;
}

.overview-section:last-of-type {
    @apply border-b-0;
}

.overview-section__header {
    @apply flex items-center justify-between gap-2;
}

.overview-section__title-group {
    @apply flex items-center gap-2;
}

.overview-section__title {
    @apply text-base font-semibold uppercase tracking-wide text-gray-700;
}

.overview-section__arrow-btn .bb-button__icon {
    transform: rotate(-45deg);
}

.overview-section__counters {
    @apply flex flex-wrap items-center gap-2 text-sm text-gray-500;
}

.overview-section__body {
    @apply flex flex-col gap-2 text-sm text-gray-700;
}
</style>
