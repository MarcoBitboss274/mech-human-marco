<script setup lang="ts">
import ProductionStatusBadge from '@/components/productions/ProductionStatusBadge.vue';
import type { Operation } from '@/types/Operation';
import type { Production } from '@/types/Production';
import { dateTime } from '@/utils/formatters/date';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();

const productions = computed<Production[]>(() => props.operation.productions ?? []);
const production = computed<Production | null>(() => productions.value[0] ?? null);
</script>

<template>
    <div class="operation-production">
        <div class="operation-production__header">
            <h2 class="operation-production__title">{{ t('Produzione') }}</h2>
        </div>

        <div v-if="!production" class="operation-production__empty">
            {{ t('Nessuna produzione presente') }}
        </div>

        <div v-else class="operation-production__content">
            <div class="operation-production__row">
                <span class="operation-production__label">{{ t('Stato') }}</span>
                <ProductionStatusBadge :status="production.status" />
            </div>
            <div v-if="production.status === 'completed'" class="operation-production__row">
                <span class="operation-production__label">{{ t('Completata il') }}</span>
                <span class="operation-production__value">{{ dateTime(production.completed_at) ?? '--' }}</span>
            </div>
            <div v-else-if="production.status === 'confirmed'" class="operation-production__row">
                <span class="operation-production__label">{{ t('Confermata il') }}</span>
                <span class="operation-production__value">{{ dateTime(production.confirmed_at) ?? '--' }}</span>
            </div>
            <div v-else-if="production.status === 'canceled'" class="operation-production__row">
                <span class="operation-production__label">{{ t('Annullata il') }}</span>
                <span class="operation-production__value">{{ dateTime(production.canceled_at) ?? '--' }}</span>
            </div>
        </div>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.operation-production {
    @apply py-4;
}

.operation-production__header {
    @apply mb-4 flex items-center justify-between gap-3;
}

.operation-production__title {
    @apply text-lg font-semibold text-gray-900;
}

.operation-production__empty {
    @apply py-10 text-center text-gray-500;
}

.operation-production__content {
    @apply space-y-3 rounded-lg border border-gray-200 bg-white p-4;
}

.operation-production__row {
    @apply flex flex-wrap items-center justify-between gap-2 text-sm text-gray-700;
}

.operation-production__label {
    @apply text-xs text-gray-500;
}

.operation-production__value {
    @apply text-gray-900;
}
</style>

