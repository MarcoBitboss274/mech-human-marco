<script setup lang="ts">
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import type { Building } from '@/types/Building';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Dashboard' }, () => [page]),
});

defineProps<{
    buildings: Building[];
}>();
</script>

<template>
    <div>
        <div class="mx-auto mt-10 flex max-w-2xl flex-col gap-4">
            <div v-for="building in buildings" :key="building.id" class="space-y-2 rounded-md border border-gray-200 p-4">
                <p>{{ building.name }}</p>
                <p>{{ building.vat }}</p>
                <p>{{ building.approved ? t('Approvato') : t('In attesa di approvazione') }}</p>
                <BbButton v-if="building.approved" :href="route('workspace.building.index', { building: building.slug })">{{ t('Visualizza') }}</BbButton>
            </div>
        </div>
    </div>
</template>
