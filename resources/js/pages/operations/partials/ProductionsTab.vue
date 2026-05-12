<script setup lang="ts">
import ProductionStatusBadge from '@/components/productions/ProductionStatusBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import type { Operation } from '@/types/Operation';
import type { Production } from '@/types/Production';
import { dateTime } from '@/utils/formatters/date';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { can } = usePermissions();
const { success, error } = useMainToast();

type Props = {
    operation: Operation;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'production:updated'): void;
}>();

const productions = computed<Production[]>(() => props.operation.productions ?? []);
const production = computed<Production | null>(() => productions.value[0] ?? null);

const productionStatus = computed<string | null>(() => production.value?.status ?? null);
const canManage = computed(() => can('operations.production.manage'));

const showConfirm = computed(() => canManage.value && productionStatus.value !== 'confirmed' && productionStatus.value !== 'completed');
const showCancel = computed(() => canManage.value && (productionStatus.value === 'confirmed' || productionStatus.value === 'completed'));
const showComplete = computed(() => canManage.value && productionStatus.value === 'confirmed');
const showReopen = computed(() => canManage.value && productionStatus.value === 'completed');

const working = ref(false);

const post = (routeName: string, successMsg: string) => {
    if (working.value) return;
    working.value = true;
    router.post(
        route(routeName, { operation: props.operation.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success(successMsg);
                emit('production:updated');
            },
            onError: () => error(t('Si è verificato un errore')),
            onFinish: () => (working.value = false),
        },
    );
};

const confirmProduction = () => post('operations.productions.confirm', t('Produzione confermata con successo'));
const cancelProduction = () => post('operations.productions.cancel', t('Produzione annullata con successo'));
const markCompleted = () => post('operations.productions.complete', t('Produzione segnata come completata'));
const reopenProduction = () => post('operations.productions.reopen', t('Produzione riaperta'));
</script>

<template>
    <div class="operation-production">
        <div class="operation-production__header">
            <h2 class="operation-production__title">{{ t('Produzione') }}</h2>

            <div class="flex flex-wrap items-center gap-2">
                <BbButton
                    v-if="showConfirm"
                    append:icon="play"
                    size="xs"
                    :disabled="working"
                    @click="confirmProduction"
                >
                    {{ productionStatus === 'canceled' ? t('Riconferma produzione') : t('Conferma produzione') }}
                </BbButton>

                <BbButton
                    v-if="showComplete"
                    variant="primary"
                    append:icon="check"
                    size="xs"
                    :disabled="working"
                    @click="markCompleted"
                >
                    {{ t('Segna completata') }}
                </BbButton>

                <BbButton
                    v-if="showReopen"
                    append:icon="refresh"
                    size="xs"
                    :disabled="working"
                    @click="reopenProduction"
                >
                    {{ t('Riapri produzione') }}
                </BbButton>

                <BbButton
                    v-if="showCancel"
                    variant="danger"
                    append:icon="trash"
                    size="xs"
                    :disabled="working"
                    @click="cancelProduction"
                >
                    {{ t('Annulla produzione') }}
                </BbButton>
            </div>
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
