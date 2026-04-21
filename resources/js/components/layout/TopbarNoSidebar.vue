<template>
    <header class="topbar-no-sidebar centered-container">
        <div>
            <div class="topbar-no-sidebar__left">
                <BaseButton :aria-label="t('Vai alla home')" class="app-logo" :href="route('home')">
                    <AppLogo class="app-logo" />
                </BaseButton>
            </div>
            <BbDropdown v-if="userStore.current" :items="userDropdownItems" :offset="15" theme="user-dropdown" :width="140">
                <template #activator="{ props }">
                    <BaseButton class="user-dropdown-button" v-bind="props">
                        <BbAvatar
                            :alt="`Immagine del profilo di ${userStore.current?.name}`"
                            size="40"
                            :sizes="userStore.current!.avatar_srcset ? '40px' : undefined"
                            :src="userStore.current?.avatar_src"
                            :srcset="userStore.current!.avatar_srcset ?? undefined"
                        >
                            <span class="fallback-avatar"> {{ userStore.current?.name?.charAt(0) }}{{ userStore.current?.surname?.charAt(0) }} </span>
                        </BbAvatar>
                    </BaseButton>
                </template>
            </BbDropdown>
        </div>
    </header>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import { useBroadCastChannel } from '@/composables/useBroadcastChannelInstance';
import { useUser } from '@/stores/user';
import { BaseButton, BbAvatar, BbDropdown, type BbDropdownItem } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const userStore = useUser();
const { post } = useBroadCastChannel();

const userDropdownItems: BbDropdownItem[] = [
    {
        key: 'profile',
        text: t('Profilo'),
        'prepend:icon': 'users',
        to: { name: 'profile' },
    },
    {
        key: 'logout',
        text: t('Logout'),
        'prepend:icon': 'logout',
        onClick: () => {
            post('user:logout');
        },
    },
];
</script>

<style>
@reference '@/../css/base.css';
.topbar-no-sidebar {
    @apply relative h-[var(--topbar-h)] items-center bg-[var(--bb-panel)] shadow-md dark:shadow-gray-800;

    > div {
        @apply flex items-center justify-between;
        .topbar-no-sidebar__left {
            @apply flex items-center gap-4;

            .app-logo {
                @apply h-8 w-auto;
            }
        }

        .topbar-no-sidebar__right {
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
}
.bb-dropdown__bubble-container--user-dropdown {
    --bb-arrow: 0;
}
</style>
