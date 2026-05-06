<template>
    <EmptyLayout :title="title">
        <div class="supplier-workspace">
            <aside class="supplier-workspace__sidebar">
                <div class="supplier-workspace__sidebar-header">
                    <AppLogo :stacked="false" class="w-32" />
                </div>
                <nav class="supplier-workspace__nav">
                    <SidebarLink
                        v-for="item in items"
                        :key="item.key"
                        :collapsed="false"
                        :href="item.href"
                        :icon="item.icon"
                        :text="item.text"
                        :active="item.active"
                    />
                </nav>
                <div class="supplier-workspace__sidebar-footer">
                    <BbButton variant="ghost" prepend:icon="logout" @click="logout">{{ t('Logout') }}</BbButton>
                </div>
            </aside>
            <main class="supplier-workspace__main">
                <slot />
            </main>
        </div>
    </EmptyLayout>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import SidebarLink from '@/components/layout/sidebar/SidebarLink.vue';
import { usePermissions } from '@/composables/usePermissions';
import EmptyLayout from '@/layouts/app/EmptyLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps({
    title: { type: String, default: 'Area Fornitore' },
});

const { t } = useI18n();
const page = usePage();
const { isSupplier } = usePermissions();

const supplierRole = computed<string | null>(() => {
    const auth = page.props.auth as { supplier_role?: string | null } | undefined;
    return auth?.supplier_role ?? null;
});

const isSupplierAdmin = computed(() => isSupplier.value && supplierRole.value === 'admin');

const isActive = (routeName: string) => {
    return typeof route().current === 'function' && route().current(routeName);
};

const items = computed(() => {
    const list = [
        {
            key: 'dashboard',
            text: t('Dashboard'),
            icon: 'home',
            href: route('workspace.supplier.dashboard'),
            active: isActive('workspace.supplier.dashboard'),
        },
        {
            key: 'profile',
            text: t('Profilo'),
            icon: 'users',
            href: route('workspace.supplier.profile.index'),
            active: isActive('workspace.supplier.profile.*'),
        },
    ];

    if (isSupplierAdmin.value) {
        list.push({
            key: 'settings',
            text: t('Impostazioni'),
            icon: 'settings',
            href: route('workspace.supplier.settings.index'),
            active: isActive('workspace.supplier.settings.*'),
        });
        list.push({
            key: 'team',
            text: t('Team'),
            icon: 'users',
            href: route('workspace.supplier.team.index'),
            active: isActive('workspace.supplier.team.*'),
        });
    }

    return list;
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<style scoped>
.supplier-workspace {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: 100vh;
    background: var(--bb-panel);
}

.supplier-workspace__sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 16px;
    background: var(--bb-surface, #fff);
    border-right: 2px solid var(--bb-border, #e2e8f0);
}

.supplier-workspace__sidebar-header {
    padding: 8px 4px;
    border-bottom: 2px solid var(--bb-border, #e2e8f0);
}

.supplier-workspace__nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.supplier-workspace__sidebar-footer {
    padding-top: 16px;
    border-top: 2px solid var(--bb-border, #e2e8f0);
}

.supplier-workspace__main {
    padding: 24px 32px;
    overflow-x: auto;
}
</style>
