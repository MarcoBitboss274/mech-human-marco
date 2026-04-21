<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <div>
                <h1 class="workspace-view__title">{{ t('Preventivi') }}</h1>
            </div>
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="filters.query"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra i preventivi')"
            />
            <BbButton icon="trash" @click="resetFilters">{{ t('Pulisci') }}</BbButton>
        </div>

        <div class="mb-6">
            <ul class="space-y-4">
                <BbTable :columns="columns" item-value="id" :items="quotes.data ?? []" :loading="loading" actions>
                    <template #no-data>{{ t('Nessun preventivo trovato') }}</template>
                    <template #status="{ item }">
                        <OperationQuoteStatusBadge :status="item.status" size="xs" />
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

        <XPagination v-model="page" :disabled="loading" :per-page="quotes.per_page" :total-items="quotes.total" :total-pages="quotes.last_page" />
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import { Pagination } from '@/types/Pagination';
import type { Quote } from '@/types/Quote';
import { router } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbTable, BbTextInput } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Preventivi' }, () => [page]),
});

type Props = {
    quotes: Pagination<Quote>;
};

const props = defineProps<Props>();

const filtersDefault = {
    query: route().params.query ?? null,
};

const indexRoute = route('workspace.quotes.index', { building: workspace.value?.slug });
const { loading, page, filters, resetFilters } = useIndexPage(indexRoute, filtersDefault, props.quotes);

const columns = ref<BbTableColumn[]>([
    {
        key: 'id',
        label: 'ID',
    },
    {
        key: 'operation.batch_number',
        label: t('Lotto lavorazione'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'status',
        label: t('Stato'),
    },
    {
        key: 'accepted_at',
        label: t('Accettato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
    {
        key: 'created_at',
        label: t('Creato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);

const openShow = (id: Quote['id']) => {
    router.get(
        route('workspace.quotes.show', {
            building: workspace.value?.slug,
            quote: id,
        }),
    );
};
</script>
