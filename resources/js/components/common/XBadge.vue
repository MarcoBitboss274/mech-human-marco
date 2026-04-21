<template>
    <component :is="tag" class="x-badge" :style="{ '--color': color }">
        <slot />
        <BbIcon v-if="props['append:icon']" class="x-badge__icon" :type="props['append:icon']" />
    </component>
</template>

<script setup lang="ts">
import { BbIcon } from 'bitboss-ui';

type Props = {
    color: string;
    'append:icon'?: string;
    tag?: string;
};
const props = withDefaults(defineProps<Props>(), {
    tag: 'span',
});
</script>

<style>
@reference '@/../css/base.css';
.x-badge {
    --gap: 8px;
    --icon-size: 16px;
    --ring-color: color-mix(in srgb, var(--color) calc(var(--bb-ring-opacity) * 100%), transparent);
    @apply inline-flex items-center gap-[var(--gap)] rounded-md bg-[var(--color)] px-2 text-sm font-semibold text-black;

    box-shadow: 0px 0px 0px 0px var(--ring-color);
    outline: 2px solid transparent;
    outline-offset: 2px;
    transition-duration: 150ms;
    transition-property: color, background-color, border-color, text-decoration-color, box-shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);

    &:hover,
    &:focus-visible {
        .x-badge__icon {
            @apply ml-0 opacity-100;
        }
    }

    &:focus-visible {
        box-shadow: 0px 0px 0px var(--bb-ring-size) var(--ring-color);
    }

    .x-badge__icon {
        --size: var(--icon-size) !important;
        @apply -ml-[calc((var(--gap)+var(--icon-size)))] rounded-sm opacity-0 transition-all;
    }
}
</style>
