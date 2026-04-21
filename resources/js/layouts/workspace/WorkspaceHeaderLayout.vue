<script setup lang="ts">
import LayoutSidebar from '@/components/layout/workspace/LayoutSidebar.vue';
import TopbarDefault from '@/components/layout/workspace/TopbarDefault.vue';
import EmptyLayout from '@/layouts/app/EmptyLayout.vue';
import { useUiStore } from '@/stores/ui';

defineProps({
    title: {
        type: String,
        default: 'Title',
    },
});

const uiStore = useUiStore();
</script>

<template>
    <EmptyLayout :title="title">
        <div class="default-layout" :class="{ 'default-layout--sidebar-expanded': uiStore.sidebarExpanded }">
            <TopbarDefault />
            <div class="default-layout__page-container">
                <slot />
            </div>
            <LayoutSidebar />
        </div>
    </EmptyLayout>
</template>

<style>
@reference '@/../css/base.css';

.default-layout {
    @apply grid grid-cols-1 grid-rows-[auto_1fr] md:pl-[var(--sidebar-min-w)];
    transition: padding-left var(--transition-duration) var(--transition-easing);

    &.default-layout--sidebar-expanded {
        @apply md:pl-[var(--sidebar-max-w)];

        .layout-sidebar {
            @apply translate-x-0;
        }
    }

    .layout-sidebar {
        @apply fixed inset-y-0 left-0 z-[var(--bb-overlay-z-index)] -translate-x-full md:translate-x-0;
    }

    .layout-topbar {
        @apply z-[var(--bb-overlay-z-index)];
    }

    .default-layout__page-container {
        @apply grid max-w-[100%] grid-cols-[1fr] grid-rows-[1fr];
        min-height: 100vh;
        min-height: 100dvh;
        transition: min-height var(--transition-duration) var(--transition-easing);

        background: var(--bb-panel);

        > * {
            @apply px-[var(--min-px)] pt-6 pb-10 lg:px-5;
        }

        .page__title {
            @apply text-2xl font-bold text-[var(--bb-text)];
        }

        .page__subtitle {
            @apply text-mix-600 mb-6 text-base;
        }
    }
}
</style>
