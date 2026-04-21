<template>
    <aside
        class="multiselection-floating-container"
        :class="{
            'multiselection-floating-container--active': context.selected.length > 0 || context.all,
        }"
    >
        <span class="multiselection-floating-container__label">
            {{ label }}
        </span>
        <BbDropdown v-if="items.length > 0" :items="items" :offset="20" placement="top-end" theme="multiselection-floating-container">
            <template #activator="{ props }">
                <BaseButton v-bind="props" class="base-btn--link">{{ dropdownLabel }}</BaseButton>
            </template>
        </BbDropdown>
        <slot />
    </aside>
</template>

<script setup lang="ts">
import { useTableContext } from '@/composables/useTableContext';
import { BaseButton, BbDropdown, type BbDropdownItem } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
type Props = {
    dropdownLabel?: string;
    items?: BbDropdownItem[];
    contextKey?: string;
};

const props = withDefaults(defineProps<Props>(), {
    dropdownLabel: () => t('Azioni'),
    items: () => [],
    contextKey: undefined,
});

const context = useTableContext<any>(props.contextKey);

const label = computed(() => t('Alcuni elementi selezionati'));
</script>

<style>
@reference '@/../css/base.css';

.multiselection-floating-container {
    --destop-max-w: 600px;
    --transition-duration: 0.4s;
    @apply fixed bottom-10 left-1/2 z-[var(--bb-overlay-z-index)] flex min-h-[50px] w-full max-w-[min(90%,var(--destop-max-w))] -translate-x-1/2 translate-y-20 scale-75 items-center rounded-[var(--bb-radius)] border border-[var(--bb-border)] bg-[var(--bb-primary)] px-2 py-2 text-[var(--bb-contrasting)] opacity-0 shadow-lg md:left-[calc(50%+var(--sidebar-min-w)/2)] md:gap-x-2;
    transition:
        transform var(--transition-duration) cubic-bezier(0.4, 0, 0.2, 1),
        opacity var(--transition-duration) cubic-bezier(0.4, 0, 0.2, 1);
    &.multiselection-floating-container--active {
        @apply translate-y-0 scale-100 opacity-100;
        transition:
            transform var(--transition-duration) calc(var(--transition-duration) / 4) cubic-bezier(0.4, 0, 0.2, 1),
            opacity var(--transition-duration) calc(var(--transition-duration) / 3) cubic-bezier(0.4, 0, 0.2, 1);
    }

    .multiselection-floating-container__label {
        @apply inline-block flex-auto;
    }

    .bb-dropdown__wrapper {
        @apply mr-2 flex;
        .bb-button {
            @apply text-inherit;
        }
    }
}
.default-layout--sidebar-expanded {
    .multiselection-floating-container {
        @apply -z-[20] md:left-[calc(50%+var(--sidebar-max-w)/2)] md:z-[var(--bb-overlay-z-index)];
    }
}

.bb-dropdown__bubble-container--multiselection-floating-container {
    --bb-arrow: 0;
    --bb-border: var(--bb-primary);
}
</style>
