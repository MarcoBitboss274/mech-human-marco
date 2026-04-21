<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <h1 class="workspace-view__title">{{ t('Dettaglio preventivo') }}</h1>
            <BbButton icon="arrow-left" @click="backToIndex">{{ t('Torna alla lista') }}</BbButton>
        </div>

        <div class="quotes-show__content">
            <div class="quotes-show__details">
                <div class="quotes-show__details-grid">
                    <div class="quotes-show__details-item">
                        <span class="quotes-show__label">ID</span>
                        <span class="quotes-show__value">{{ quote.id }}</span>
                    </div>
                    <div class="quotes-show__details-item">
                        <span class="quotes-show__label">{{ t('Lavorazione') }}</span>
                        <span class="quotes-show__value">{{ quote.operation?.id ?? '--' }}</span>
                    </div>
                    <div class="quotes-show__details-item">
                        <span class="quotes-show__label">{{ t('Stato') }}</span>
                        <OperationQuoteStatusBadge :status="quote.status" />
                    </div>
                    <div class="quotes-show__details-item">
                        <span class="quotes-show__label">{{ t('Accettato il') }}</span>
                        <span class="quotes-show__value">{{ dateTime(quote.accepted_at) ?? '--' }}</span>
                    </div>
                    <div class="quotes-show__details-item quotes-show__details-item--full">
                        <span class="quotes-show__label">{{ t('Note') }}</span>
                        <span class="quotes-show__value">{{ quote.notes ?? '--' }}</span>
                    </div>
                    <div class="quotes-show__details-item">
                        <span class="quotes-show__label">{{ t('Creato il') }}</span>
                        <span class="quotes-show__value">{{ dateTime(quote.created_at) ?? '--' }}</span>
                    </div>
                    <div class="quotes-show__details-item">
                        <span class="quotes-show__label">{{ t('Aggiornato il') }}</span>
                        <span class="quotes-show__value">{{ dateTime(quote.updated_at) ?? '--' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import type { Quote } from '@/types/Quote';
import { dateTime } from '@/utils/formatters/date';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Dettaglio preventivo' }, () => [page]),
});

type Props = {
    quote: Quote;
};

defineProps<Props>();

const backToIndex = () => {
    router.get(
        route('workspace.quotes.index', {
            building: workspace.value?.slug,
        }),
    );
};
</script>

<style>
@reference '@/../css/base.css';

.quotes-show__content {
    @apply mt-6;
}

.quotes-show__details {
    @apply py-4;
}

.quotes-show__details-grid {
    @apply grid gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.quotes-show__details-item {
    @apply flex flex-col gap-1;
}

.quotes-show__details-item--full {
    @apply sm:col-span-2 lg:col-span-3;
}

.quotes-show__label {
    @apply text-sm font-medium text-gray-500;
}

.quotes-show__value {
    @apply text-gray-900;
}
</style>
