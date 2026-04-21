<template>
    <header class="layout-topbar">
        <div class="layout-topbar__left">
            <BaseButton :aria-label="t('Menu')" class="hamburger-icon" @click="uiStore.toggleSidebar">
                <BbIcon type="hamburger" />
            </BaseButton>
            <BaseButton :aria-label="t('Vai alla home')" class="app-logo" :href="route('home')">
                <AppLogo class="app-logo" />
            </BaseButton>
        </div>

        <div class="flex flex-grow items-center justify-end">
            <BbPopover v-if="hasChatReadPermission && (userMeta.customer_buildings_count ?? 0) > 1">
                <template #activator="{ props: popoverProps }">
                    <BaseButton class="chat-notification-button" v-bind="popoverProps">
                        <BbIcon type="bell" />
                        <span v-if="chatStore.unreadTotal > 0" class="chat-notification-badge">{{ chatStore.unreadTotal }}</span>
                    </BaseButton>
                </template>

                <template #default="{ close }">
                    <div class="chat-notification-popover">
                        <div v-if="chatStore.unreadItems.length === 0" class="chat-notification-popover__empty">
                            {{ t('Nessun messaggio non letto') }}
                        </div>
                        <ul v-else class="chat-notification-popover__list">
                            <li v-for="item in chatStore.unreadItems" :key="item.operation_id">
                                <button
                                    type="button"
                                    class="chat-notification-popover__link"
                                    @click="
                                        () => {
                                            goToOperationChat(item.operation_id);
                                            close();
                                        }
                                    "
                                >
                                    <span>{{
                                        item.batch_number ? `${t('Lavorazione')} ${item.batch_number}` : `${t('Lavorazione')} #${item.operation_id}`
                                    }}</span>
                                    <strong>({{ item.unread_count }})</strong>
                                </button>
                            </li>
                        </ul>
                    </div>
                </template>
            </BbPopover>
        </div>

        <BbDropdown :items="userDropdownItems" :offset="15" theme="user-dropdown" :width="140">
            <template #activator="{ props }">
                <BaseButton class="user-dropdown-button" v-bind="props">
                    <BbAvatar :src="avatarUrl" :alt="`Immagine del profilo di ${user?.name}`" size="40">
                        <span class="fallback-avatar"> {{ user?.name?.charAt(0) }}{{ user?.surname?.charAt(0) }} </span>
                    </BbAvatar>
                </BaseButton>
            </template>
        </BbDropdown>
    </header>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useUserMeta } from '@/composables/useUserMeta';
import { useChatStore } from '@/stores/chat';
import { useUiStore } from '@/stores/ui';
import { router, usePage } from '@inertiajs/vue3';
import { BaseButton, BbAvatar, BbDropdown, BbIcon, BbPopover, type BbDropdownItem } from 'bitboss-ui';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { route } from 'ziggy-js';

const { t } = useI18n();

const uiStore = useUiStore();
const { currentUser: user, impersonating, can } = usePermissions();
const chatStore = useChatStore();
const { userMeta } = useUserMeta();

const page = usePage<{
    auth: {
        permissions?: string[];
    };
    avatar?: number | null;
}>();

const avatar = computed<number | null>(() => page.props.avatar ?? null);
const avatarUrl = computed<string | undefined>(() => (avatar.value ? route('media.index', { media: avatar.value }) : undefined));
const hasChatReadPermission = computed(() => true);

const userDropdownItems = ref<BbDropdownItem[]>([]);

onMounted(() => {
    if (user.value?.id) {
        chatStore.initializeRealtime(user.value.id);
        chatStore.fetchUnread();
    }

    if (userMeta.value.customer_buildings_count ?? 0 > 1) {
        userDropdownItems.value.push({
            key: 'profile',
            text: t('Profilo'),
            'prepend:icon': 'users',
            href: route('workspace.profile.index'),
        });
        userDropdownItems.value.push({
            key: 'new-building',
            text: t('Nuova struttura'),
            'prepend:icon': 'building',
            href: route('workspace.create-building'),
        });
        userDropdownItems.value.push({
            key: 'buildings',
            text: t('Strutture'),
            'prepend:icon': 'building',
            href: route('workspace.dashboard'),
        });
    }

    if (impersonating.value) {
        userDropdownItems.value.push({
            key: 'leave-impersonation',
            text: t("Lascia l'utente"),
            'prepend:icon': 'logout',
            onClick: () => {
                router.get(route('impersonate.leave'));
            },
        });
    }

    userDropdownItems.value.push({
        key: 'logout',
        text: t('Logout'),
        'prepend:icon': 'logout',
        onClick: () => {
            router.post(route('logout'));
        },
    });
});

onBeforeUnmount(() => {
    chatStore.teardownRealtime();
});

const goToOperationChat = (operationId: number) => {
    router.get(
        route('operations.show', {
            operation: operationId,
            chat: 'open',
        }),
    );
};
</script>

<style>
@reference '@/../css/base.css';
.layout-topbar {
    @apply flex h-[var(--topbar-h)] items-center gap-6 bg-[var(--bb-panel)] px-[var(--min-px)] shadow-md dark:shadow-gray-800;

    .layout-topbar__left {
        @apply flex items-center gap-4 md:hidden;

        .hamburger-icon {
            @apply text-mix-600 hover:text-mix-800 transition-colors;
        }

        .app-logo {
            @apply w-40;
        }
    }

    .layout-topbar__right {
        @apply ml-auto flex items-center gap-6;

        .nav-link {
            @apply text-mix-600 hover:text-mix-800 text-sm transition-colors;
        }
    }

    .bb-dropdown--user-dropdown {
        @apply ml-auto;

        .base-btn {
            @apply block;

            .fallback-avatar {
                @apply flex items-center justify-center rounded-full bg-[var(--bb-primary)] text-lg font-bold text-[var(--bb-contrasting)];
            }
        }
    }
}

.bb-dropdown__bubble-container--user-dropdown {
    --bb-arrow: 0;
}

.chat-notification-button {
    @apply text-mix-600 hover:text-mix-800 relative transition-colors;
}

.chat-notification-badge {
    @apply absolute -top-2 -right-2 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] leading-none font-semibold text-white;
}

.chat-notification-popover {
    @apply min-w-[220px];
}

.chat-notification-popover__empty {
    @apply text-sm text-slate-500;
}

.chat-notification-popover__list {
    @apply space-y-1;
}

.chat-notification-popover__link {
    @apply flex w-full items-center justify-between rounded-md px-2 py-1 text-left text-sm text-slate-700 transition hover:bg-slate-100;
}
</style>
