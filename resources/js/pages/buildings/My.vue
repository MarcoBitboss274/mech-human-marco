<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Building } from '@/types/Building';
import { useI18n } from 'vue-i18n';

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Le mie strutture' }, () => [page]),
});

const { t } = useI18n();

type Props = {
    buildings: Building[];
};

const props = defineProps<Props>();
</script>

<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div class="">
                <h1 class="page__title">{{ t('Le mie strutture') }}</h1>
            </div>
        </div>

        <div class="mb-6">
            <template v-if="props.buildings.length > 0">
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div
                        v-for="building in props.buildings"
                        :key="building.id"
                        class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-12 shadow-sm sm:px-6 sm:pt-6"
                    >
                        <p class="text-lg font-medium text-gray-900">{{ building.name }}</p>
                    </div>
                </div>
            </template>
            <template v-else>
                <p>{{ t('Nessuna struttura trovata') }}</p>
            </template>
        </div>
    </div>
</template>
