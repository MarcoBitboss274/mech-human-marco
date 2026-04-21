<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Dashboard' }, () => [page]),
});
</script>

<template>
    <div class="dashboard-view">
        <p>{{ t('Dashboard') }}</p>
    </div>
</template>
