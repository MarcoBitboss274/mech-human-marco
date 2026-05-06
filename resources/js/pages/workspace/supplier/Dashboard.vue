<template>
    <div>
        <h1 class="page__title">{{ t('Dashboard') }}</h1>
        <p class="page__subtitle">{{ t('Benvenuto nell\'area di lavoro del fornitore.') }}</p>
        <div class="rounded border border-dashed border-gray-300 p-6 text-sm text-gray-500">
            {{ t('Qui troverai un riepilogo operativo delle lavorazioni a te assegnate. La sezione è in costruzione.') }}
        </div>
    </div>
</template>

<script setup lang="ts">
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    supplier: { id: number; name: string | null };
}>();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Area Fornitore' }, () => [page]),
});
</script>
