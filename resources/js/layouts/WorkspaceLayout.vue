<script setup lang="ts">
import WorkspaceHeaderLayout from '@/layouts/workspace/WorkspaceHeaderLayout.vue';

defineProps({
    title: {
        type: String,
        default: 'Title',
    },
});
</script>

<template>
    <WorkspaceHeaderLayout :title="title">
        <slot />
    </WorkspaceHeaderLayout>
</template>
