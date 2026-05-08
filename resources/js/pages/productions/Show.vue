<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <h1 class="page__title">{{ t('Dettaglio produzione') }}</h1>
            <BbButton icon="arrow-left" @click="router.get(route('productions.index'))">{{ t('Torna alla lista') }}</BbButton>
        </div>

        <div class="productions-show__content">
            <div class="productions-show__details">
                <div class="productions-show__details-grid">
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">ID</span>
                        <span class="productions-show__value">{{ production.id }}</span>
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Lavorazione') }}</span>
                        <span class="productions-show__value">{{ production.operation?.id ?? '--' }}</span>
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Stato') }}</span>
                        <ProductionStatusBadge :status="production.status" />
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Completata il') }}</span>
                        <span class="productions-show__value">{{ dateTime(production.completed_at) ?? '--' }}</span>
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Confermata il') }}</span>
                        <span class="productions-show__value">{{ dateTime(production.confirmed_at) ?? '--' }}</span>
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Annullata il') }}</span>
                        <span class="productions-show__value">{{ dateTime(production.canceled_at) ?? '--' }}</span>
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Creato il') }}</span>
                        <span class="productions-show__value">{{ dateTime(production.created_at) ?? '--' }}</span>
                    </div>
                    <div class="productions-show__details-item">
                        <span class="productions-show__label">{{ t('Aggiornato il') }}</span>
                        <span class="productions-show__value">{{ dateTime(production.updated_at) ?? '--' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ProductionStatusBadge from '@/components/productions/ProductionStatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Production } from '@/types/Production';
import { dateTime } from '@/utils/formatters/date';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Dettaglio produzione' }, () => [page]),
});

type Props = {
    production: Production;
};

defineProps<Props>();
</script>

<style>
@reference '@/../css/base.css';

.productions-show__content {
    @apply mt-6;
}

.productions-show__details {
    @apply py-4;
}

.productions-show__details-grid {
    @apply grid gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.productions-show__details-item {
    @apply flex flex-col gap-1;
}

.productions-show__label {
    @apply text-sm font-medium text-gray-500;
}

.productions-show__value {
    @apply text-gray-900;
}
</style>

