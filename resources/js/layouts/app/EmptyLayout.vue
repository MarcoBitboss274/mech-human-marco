<template>
    <div class="empty-layout">
        <Head :title="title" />
        <slot />
        <BbConfirm />
        <BbToast placement="bottom" />
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BbConfirm, BbToast } from 'bitboss-ui';

defineProps({
    title: String,
});
</script>

<style>
@reference '@/../css/base.css';
.empty-layout {
    @apply grid;
}
</style>
