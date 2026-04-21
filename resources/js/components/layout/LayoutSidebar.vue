<template>
    <div class="layout-sidebar" :class="{ 'layout-sidebar--expanded': open }">
        <SidebarToggle :aria-label="`${open ? t('Chiudi') : t('Espandi')} sidebar`" :open="open" @click="uiStore.toggleSidebar" />
        <BaseButton :aria-label="t('Vai alla homepage')" :href="route('home')" @click="onLinkClick">
            <div class="logo-container">
                <AppLogo :stacked="!open" :class="open ? 'w-40' : 'w-8'" />
            </div>
        </BaseButton>
        <div class="layout-sidebar__content" @click="onContentClick">
            <template v-for="item in items" :key="item.key">
                <template v-if="item.can === true">
                    <template v-if="!!item.children?.length">
                        <SidebarLink
                            :collapsed="!open"
                            :disabled="item.disabled"
                            :href="item.href"
                            :icon="item.icon"
                            :open="item.open"
                            :section="!!item.children"
                            :target="item.target"
                            :text="item.text"
                            :active="item.active"
                            @click="onLinkClick($event, item)"
                        />
                        <div
                            class="layout-sidebar__section-children-container"
                            :class="{
                                'layout-sidebar__section-children-container--collapsed': !item.open,
                            }"
                        >
                            <div class="layout-sidebar__section-children">
                                <SidebarLink
                                    v-for="child in item.children"
                                    :key="child.key"
                                    :collapsed="!open"
                                    :disabled="child.disabled"
                                    :href="child.href"
                                    :icon="child.icon"
                                    :section="!!child.children"
                                    :target="child.target"
                                    :text="child.text"
                                    :active="child.active"
                                    @click="onLinkClick($event, child)"
                                />
                            </div>
                        </div>
                    </template>
                    <SidebarLink
                        v-else
                        :collapsed="!open"
                        :disabled="item.disabled"
                        :href="item.href"
                        :icon="item.icon"
                        :section="!!item.children"
                        :target="item.target"
                        :text="item.text"
                        :active="item.active"
                        @click="onLinkClick($event, item)"
                    />
                </template>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import type { SidebarItem } from '@/components/layout/sidebar/SidebarLink.vue';
import SidebarLink from '@/components/layout/sidebar/SidebarLink.vue';
import SidebarToggle from '@/components/layout/sidebar/SidebarToggle.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useUiStore } from '@/stores/ui';
import { wait } from '@/utils/functions/wait';
import { usePage } from '@inertiajs/vue3';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { BaseButton } from 'bitboss-ui';
import { computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { route } from 'ziggy-js';

const { t } = useI18n();

const { currentUser, can, isAgent } = usePermissions();

const breakpoints = useBreakpoints(breakpointsTailwind);

const uiStore = useUiStore();

const open = computed(() => uiStore.sidebarExpanded);

type Item = SidebarItem & { key: string; children?: Item[]; can?: boolean };

const onLinkClick = async (event: MouseEvent, item: Item) => {
    /* This is used when clicking on a section to toggle its children */
    if (item && item.children?.length) {
        item.open = !item.open;
        return;
    }
    /* This is used when clicking on a link in mobile mode so that the sidebar closes automatically */
    if (mobile.value) {
        await wait(150);
        uiStore.toggleSidebar();
    }
};

watch(currentUser, (n, o) => {
    if (n.id !== o.id) {
        window.location.reload();
    }
});

const items = computed<Item[]>(
    () =>
        [
            {
                key: 'dashboard',
                text: t('Dashboard'),
                icon: 'chart-bar',
                href: route('dashboard'),
                can: true,
                active: usePage().url.startsWith('/dashboard'),
            },
            {
                key: 'users',
                text: t('Utenti'),
                icon: 'users',
                href: route('users.index'),
                can: can('users.index'),
                active: usePage().url.startsWith('/users'),
            },
            {
                key: 'buildings',
                text: t('Strutture'),
                icon: 'building',
                href: route('buildings.index'),
                can: can('buildings.index'),
                active: usePage().url.startsWith('/buildings'),
            },
            {
                key: 'buildings-my',
                text: t('Le mie strutture'),
                icon: 'building',
                href: route('buildings.my'),
                can: isAgent.value,
                active: usePage().url.startsWith('/buildings'),
            },
            {
                key: 'operations',
                text: t('Lavorazioni'),
                icon: 'circle-stack',
                href: route('operations.index'),
                can: can('operations.index'),
                active: usePage().url.startsWith('/operations'),
            },
            {
                key: 'prescriptions',
                text: t('Prescrizioni'),
                icon: 'bandage',
                href: route('prescriptions.index'),
                can: can('prescriptions.index'),
                active: usePage().url.startsWith('/prescriptions'),
            },
            {
                key: 'quotes',
                text: t('Preventivi'),
                icon: 'quote',
                href: route('quotes.index'),
                can: can('quotes.index'),
                active: usePage().url.startsWith('/quotes'),
            },
            // {
            //     key: 'orders',
            //     text: t('Ordini'),
            //     icon: 'order',
            //     href: route('orders.index'),
            //     can: can('orders.index'),
            //     active: usePage().url.startsWith('/orders'),
            // },
            {
                key: 'productions',
                text: t('Produzioni'),
                icon: 'circle-stack',
                href: route('productions.index'),
                can: can('productions.index'),
                active: usePage().url.startsWith('/productions'),
            },
            {
                key: 'invoices',
                text: t('Fatture'),
                icon: 'wallet',
                href: route('invoices.index'),
                can: can('invoices.index'),
                active: usePage().url.startsWith('/invoices'),
            },
            {
                key: 'suppliers',
                text: t('Fornitori'),
                icon: 'truck',
                href: route('suppliers.index'),
                can: can('suppliers.index'),
                active: usePage().url.startsWith('/suppliers'),
            },
        ] satisfies Item[],
);

const mobile = breakpoints.smallerOrEqual('md');
watch(mobile, (value) => {
    if (value) {
        if (open.value) {
            uiStore.toggleSidebar();
        }
    }
});
onMounted(() => {
    if (!mobile.value && !open.value) {
        uiStore.toggleSidebar();
    }
});
watch(open, (value) => {
    if (!value) {
        items.value.forEach((item) => {
            item.open = false;
        });
    }
});

const onContentClick = (event: MouseEvent) => {
    if (!mobile.value && !open.value) {
        event.stopPropagation();
        uiStore.toggleSidebar();
    }
};
</script>

<style>
@reference '@/../css/base.css';

.layout-sidebar {
    --logo-h: 30px;
    --py: 16px;
    @apply relative flex w-[var(--sidebar-min-w)] flex-col bg-[var(--bb-panel)] px-[var(--min-px)] py-[var(--py)] shadow-md dark:shadow-gray-800;
    transition:
        width var(--transition-duration) var(--transition-easing),
        padding-left var(--transition-duration) var(--transition-easing),
        padding-right var(--transition-duration) var(--transition-easing),
        transform var(--transition-duration) var(--transition-easing);

    &.layout-sidebar--expanded {
        @apply w-full px-3 md:w-[var(--sidebar-max-w)];

        .sidebar-toggle {
            @apply inline-block;
        }

        .logo-container {
            @apply h-[var(--logo-h)] place-items-start items-center p-6;

            .app-logo {
                transition: opacity var(--transition-duration) var(--transition-easing);
            }
        }

        .layout-sidebar__content {
            &::after {
                @apply hidden;
            }

            > * {
                @apply mx-0;
            }
        }

        .layout-sidebar__footer {
            .user-info {
                @apply mx-0 w-full;

                .user-details {
                    @apply left-[44px];
                }
            }
        }
    }

    .sidebar-toggle {
        @apply absolute top-[calc(var(--py)+var(--logo-h)/2)] right-[var(--min-px)] z-[1] hidden -translate-y-1/2 md:right-0 md:inline-block md:translate-x-1/2;
    }

    .logo-container {
        @apply relative grid grid-cols-1 grid-rows-1 place-items-center py-2;

        &.app-logo {
            @apply absolute -left-full col-start-1 row-start-1 max-h-[40px] max-w-[150px] -translate-x-full opacity-0;
            transition: opacity var(--transition-duration) var(--transition-easing);

            .app-logo--visible {
                @apply relative left-0 translate-x-0 opacity-100;
            }
        }
    }

    .layout-sidebar__content {
        @apply relative -mx-1 mt-6 flex flex-auto flex-col space-y-1 overflow-clip px-1;

        .layout-sidebar__content::after {
            @apply absolute inset-0 z-10;
            content: '';
        }

        > * {
            transition:
                margin-left var(--transition-duration) var(--transition-easing),
                margin-right var(--transition-duration) var(--transition-easing);
        }

        .layout-sidebar__section-children-container {
            --transition-duration: 400ms;
            display: grid;
            grid-template-rows: 1fr;
            transition:
                margin-top var(--transition-duration) var(--transition-easing),
                opacity calc(var(--transition-duration) * 0.5) var(--transition-easing),
                grid-template-rows var(--transition-duration) var(--transition-easing);

            &.layout-sidebar__section-children-container--collapsed {
                @apply mt-0 opacity-0;
                grid-template-rows: 0fr;
                transition:
                    margin-top var(--transition-duration) 0s var(--transition-easing),
                    opacity calc(var(--transition-duration) * 0.5) 0s var(--transition-easing),
                    grid-template-rows var(--transition-duration) 0s var(--transition-easing);
            }

            .layout-sidebar__section-children {
                @apply space-y-2 overflow-hidden;
            }
        }
    }

    .layout-sidebar__footer {
        @apply border-t border-[var(--bb-border)] pt-3;

        .user-info {
            @apply relative mx-auto flex w-8 items-center overflow-clip;
            transition: width var(--transition-duration) var(--transition-easing);

            .user-details {
                @apply absolute left-full flex flex-col text-[var(--bb-text)];

                .user-name {
                    @apply text-sm font-medium whitespace-nowrap;
                }

                .user-quote {
                    @apply text-mix-700 text-xs whitespace-nowrap italic;
                }
            }
        }
    }
}
</style>
