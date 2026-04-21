<script setup lang="ts">
import AppLayout from '@/layouts/app/DefaultLayout.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <AppLayout :title="title">
        <slot />
    </AppLayout>
</template>
