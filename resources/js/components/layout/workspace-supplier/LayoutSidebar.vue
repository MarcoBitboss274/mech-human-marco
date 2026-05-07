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
                    <SidebarLink
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

const { currentUser } = usePermissions();

const breakpoints = useBreakpoints(breakpointsTailwind);

const uiStore = useUiStore();

const open = computed(() => uiStore.sidebarExpanded);

const supplierRole = computed<string | null>(() => {
    const auth = usePage().props.auth as { supplier_role?: string | null } | undefined;
    return auth?.supplier_role ?? null;
});

const isSupplierAdmin = computed(() => supplierRole.value === 'admin');

type Item = SidebarItem & { key: string; children?: Item[]; can?: boolean };

const onLinkClick = async (event: MouseEvent, item: Item) => {
    if (item && item.children?.length) {
        item.open = !item.open;
        return;
    }
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

const items = computed<Item[]>(() => {
    const list: Item[] = [];
    list.push({
        key: 'dashboard',
        text: t('Dashboard'),
        icon: 'chart-bar',
        href: route('workspace.supplier.dashboard'),
        can: true,
        active: usePage().url.startsWith('/workspace/supplier/dashboard'),
    });
    list.push({
        key: 'operations',
        text: t('Lavorazioni'),
        icon: 'circle-stack',
        href: route('workspace.supplier.operations.index'),
        can: true,
        active: usePage().url.startsWith('/workspace/supplier/operations'),
    });
    list.push({
        key: 'profile',
        text: t('Profilo'),
        icon: 'users',
        href: route('workspace.supplier.profile.index'),
        can: true,
        active: usePage().url.startsWith('/workspace/supplier/profile'),
    });
    list.push({
        key: 'settings',
        text: t('Impostazioni'),
        icon: 'cog',
        href: route('workspace.supplier.settings.index'),
        can: isSupplierAdmin.value,
        active: usePage().url.startsWith('/workspace/supplier/settings'),
    });
    list.push({
        key: 'team',
        text: t('Team'),
        icon: 'users',
        href: route('workspace.supplier.team.index'),
        can: isSupplierAdmin.value,
        active: usePage().url.startsWith('/workspace/supplier/team'),
    });
    return list;
});

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
