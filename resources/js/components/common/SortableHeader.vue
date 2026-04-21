<template>
    <span
        class="sortable-header"
        :class="{
            'sortable-header--sorted': !!order,
            [`sortable-header--${order}`]: !!order,
        }"
    >
        <BaseButton @click="$emit('sort')">
            <BbIcon size="20" type="sort_desc" />
            <slot />
        </BaseButton>
    </span>
</template>

<script setup lang="ts">
import { BaseButton, BbIcon } from 'bitboss-ui';

defineProps<{
    order?: 'asc' | 'desc';
}>();

defineEmits<{
    (e: 'sort'): void;
}>();
</script>

<style>
@reference '@/../css/base.css';
.sortable-header {
    --icon-size: 24px;
    &:hover,
    & button:focus-visible {
        .bb-icon {
            @apply text-mix-200 mr-1;
        }
    }
    &.sortable-header--sorted {
        button .bb-icon {
            @apply text-mix-800 mr-1;
        }
    }
    &.sortable-header--desc {
        .bb-icon {
            @apply rotate-180;
        }
    }
    .base-btn {
        color: inherit;
    }
    .bb-icon {
        @apply float-left -mr-[var(--icon-size)] text-transparent;

        transition:
            margin-right 0.3s ease-in-out,
            color 0.3s ease-in-out;
    }
}
</style>
