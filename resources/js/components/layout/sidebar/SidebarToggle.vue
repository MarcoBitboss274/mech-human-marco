<template>
    <BaseButton class="sidebar-toggle" :class="{ 'sidebar-toggle--active': open }" @click="emit('click')">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                <rect height="18" rx="2" width="18" x="3" y="3" />
                <path d="M9 3v18m7-6l-3-3l3-3" />
            </g>
        </svg>
    </BaseButton>
</template>

<script setup lang="ts">
import { BaseButton } from 'bitboss-ui';

type Props = {
    open: boolean;
};
defineProps<Props>();

const emit = defineEmits<{
    (e: 'click'): void;
}>();
</script>

<style>
@reference '@/../css/base.css';
.sidebar-toggle {
    @apply h-7 w-7 rounded-[var(--bb-radius)] p-1;
    color: color-mix(in sRGB, var(--bb-text), transparent 50%);

    &.sidebar-toggle--active {
        svg {
            @apply rotate-0;
        }
    }

    svg {
        @apply rotate-180;
    }
}
</style>
