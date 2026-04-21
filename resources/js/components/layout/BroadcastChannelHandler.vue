<template>
    <span class="broadcast-channel-handler" />
</template>

<script setup lang="ts">
import { logout, profile } from '@/api/user';
import { useBroadCastChannel } from '@/composables/useBroadcastChannelInstance';
import { useUser } from '@/Stores/user';

/**
 * This component is used to handle global actions across all opened tabs.
 * If a user is logged in in a tab you want him to be logged in in the other ones as well.
 * The same if the user is logged out or verifies an email you want the latest data to be available to all tabs.
 */

const userStore = useUser();

const { on } = useBroadCastChannel();

on('user:profile', async () => {
    const response = await profile();
    userStore.setCurrent(response.data);
});

on('user:logout', async () => {
    await logout();
    window.location.reload();
});

on('user:email-verified', async () => {
    /**
     * When a user verifies his email we set the email_verified_at
     * to the current date and avoid retrieving the last state from the backend
     */
    if (userStore.current) {
        userStore.current.email_verified_at = new Date().toISOString();
    }
});
</script>

<style>
@reference '@/../css/base.css';
.broadcast-channel-handler {
    @apply sr-only;
}
</style>
