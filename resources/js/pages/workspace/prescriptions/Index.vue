<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <h1 class="workspace-view__title">{{ t('Prescrizioni') }}</h1>
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="filters.query"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra le prescrizioni')"
            />
            <BbButton icon="trash" @click="resetFilters">{{ t('Pulisci') }}</BbButton>
        </div>

        <div class="mb-6">
            <ul class="space-y-4">
                <BbTable :columns="columns" item-value="id" :items="prescriptions.data ?? []" :loading="loading" actions>
                    <template #no-data>{{ t('Nessuna prescrizione trovata') }}</template>
                    <template #typology="{ item }">
                        <PrescriptionTypologyBadge :typology="item.typology" size="xs" />
                    </template>
                    <template #status="{ item }">
                        <PrescriptionStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="openShow(item.id)">
                                {{ t('Visualizza') }}
                            </BbButton>
                        </div>
                    </template>
                </BbTable>
            </ul>
        </div>

        <XPagination
            v-model="page"
            :disabled="loading"
            :per-page="prescriptions.per_page"
            :total-items="prescriptions.total"
            :total-pages="prescriptions.last_page"
        />
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import { Pagination } from '@/types/Pagination';
import type { Prescription } from '@/types/Prescription';
import { router } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbTable, BbTextInput } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Prescrizioni' }, () => [page]),
});

type Props = {
    prescriptions: Pagination<Prescription>;
};

const props = defineProps<Props>();

const filtersDefault = {
    query: route().params.query ?? null,
};

const indexRoute = route('workspace.prescriptions.index', { building: workspace.value?.slug });
const { loading, page, filters, resetFilters } = useIndexPage(indexRoute, filtersDefault, props.prescriptions);

const columns = ref<BbTableColumn[]>([
    {
        key: 'operation.batch_number',
        label: t('Lotto lavorazione'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'typology',
        label: t('Tipologia'),
    },
    {
        key: 'status',
        label: t('Stato'),
    },
    {
        key: 'ref',
        label: t('Riferimento'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'name',
        label: t('Nome'),
        formatter: (d, i, item) => {
            const parts = [item.name, item.surname].filter(Boolean);
            return parts.length ? parts.join(' ') : '--';
        },
    },
    {
        key: 'created_at',
        label: t('Creato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);

const openShow = (id: Prescription['id']) => {
    router.get(
        route('workspace.prescriptions.show', {
            building: workspace.value?.slug,
            prescription: id,
        }),
    );
};
</script>
