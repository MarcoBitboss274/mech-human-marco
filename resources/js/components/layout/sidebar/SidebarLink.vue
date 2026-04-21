<template>
    <BaseButton
        class="sidebar-link"
        :class="{
            'sidebar-link--collapsed': collapsed,
            'sidebar-link--expanded': open,
            'sidebar-link--active': active,
        }"
        :disabled="disabled"
        :exact-active-class="section ? undefined : 'sidebar-link--active'"
        :href="href"
        :target="target"
        :text="text"
    >
        <span class="sidebar-link__content">
            <BbIcon v-if="icon" class="sidebar-link__icon" size="20px" :type="icon" />
            <span v-else class="sidebar-link__icon-spacer"></span>
            <span class="sidebar-link__text">{{ text }}</span>
            <BbIcon v-if="section" class="sidebar-link__icon-chevron" size="16" type="chevron_down" />
        </span>
    </BaseButton>
</template>

<script setup lang="ts">
import type { BaseButtonProps } from 'bitboss-ui';
import { BaseButton, BbIcon } from 'bitboss-ui';

export type SidebarItem = {
    icon?: string;
    collapsed?: boolean;
    section?: boolean;
    open?: boolean;
    active?: boolean;
} & Pick<BaseButtonProps, 'to' | 'href' | 'target' | 'disabled' | 'text'>;

defineProps<SidebarItem>();
</script>

<style>
@reference '@/../css/base.css';
.sidebar-link {
    @apply flex h-9 items-center rounded-[var(--bb-radius)] px-4 transition-all;
    background-color: var(--bb-panel);
    color: var(--bb-text);

    &:hover {
        background-color: color-mix(in sRGB, var(--bb-panel) 90%, var(--bb-text) 10%);
    }

    &:active {
        background-color: color-mix(in sRGB, var(--bb-panel) 80%, var(--bb-text) 20%);
    }
    &.sidebar-link--active {
        background-color: var(--bb-primary);
        color: var(--bb-contrasting);

        .bb-icon {
            color: color-mix(in sRGB, currentColor 70%, transparent 30%);
        }
    }

    .sidebar-link__content {
        @apply relative left-1/2 flex w-full -translate-x-1/2 items-center gap-3 overflow-hidden;

        .sidebar-link__icon {
            @apply flex-shrink-0;
            color: color-mix(in sRGB, currentColor, transparent 0%);
        }

        .sidebar-link__icon-spacer {
            @apply w-5 flex-shrink-0;
        }

        .sidebar-link__text {
            @apply flex-auto text-left text-sm transition-opacity;
        }

        .sidebar-link__icon-chevron {
        }
    }

    &--collapsed {
        @apply justify-center;
        .sidebar-link__text {
            @apply opacity-0;
        }
    }
    &--expanded {
        @apply font-bold text-[var(--bb-primary)];
    }
}
</style>
