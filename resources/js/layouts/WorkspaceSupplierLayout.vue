<script setup lang="ts">
import WorkspaceSupplierHeaderLayout from '@/layouts/workspace-supplier/WorkspaceSupplierHeaderLayout.vue';

defineProps({
    title: {
        type: String,
        default: 'Area Fornitore',
    },
});
</script>

<template>
    <WorkspaceSupplierHeaderLayout :title="title">
        <slot />
    </WorkspaceSupplierHeaderLayout>
</template>
