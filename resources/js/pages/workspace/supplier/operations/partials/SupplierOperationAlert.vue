<template>
    <div v-if="alerts.length" class="supplier-alert-stack">
        <div
            v-for="alert in alerts"
            :key="alert.key"
            class="supplier-alert"
            :class="bannerClass(alert.theme)"
        >
            <div class="supplier-alert__body">
                <strong>{{ alert.title }}</strong>
                <p>{{ alert.message }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Operation } from '@/types/Operation';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Theme = 'error' | 'warning' | 'info';

type Props = {
    operation: Operation & {
        supplier_visible_status?: string | null;
        production_canceled_at?: string | null;
        canceled_at?: string | null;
        supplier_completed_at?: string | null;
    };
};

const props = defineProps<Props>();
const { t } = useI18n();

type Banner = {
    key: string;
    title: string;
    message: string;
    theme: Theme;
};

const status = computed(() => props.operation.supplier_visible_status);
const isCanceled = computed(() => !!props.operation.canceled_at);
const productionWasCanceled = computed(() => !!props.operation.production_canceled_at);
const hasActiveRevision = computed(() => !!props.operation.latest_prescription?.active_revision);

// Solo alert eccezionali (situazioni a cui il fornitore deve prestare attenzione).
// Nessun banner di stato base: lo stato è già visibile dal badge nell'header.
const alerts = computed<Banner[]>(() => {
    const list: Banner[] = [];

    if (isCanceled.value) {
        list.push({
            key: 'canceled',
            title: t('Lavorazione annullata'),
            message: t('Questa lavorazione è stata annullata da M&H.'),
            theme: 'error',
        });
    }

    if (productionWasCanceled.value && status.value === 'new_case' && !isCanceled.value) {
        list.push({
            key: 'production-canceled',
            title: t('Produzione annullata'),
            message: t('M&H ha annullato la produzione. Lo stato è tornato a Nuovo caso.'),
            theme: 'warning',
        });
    }

    if (hasActiveRevision.value) {
        list.push({
            key: 'prescription-in-revision',
            title: t('Prescrizione in revisione'),
            message: t('M&H sta verificando alcuni dati con il customer.'),
            theme: 'info',
        });
    }

    return list;
});

const bannerClass = (theme: Theme) => {
    switch (theme) {
        case 'error':
            return 'supplier-alert--error';
        case 'warning':
            return 'supplier-alert--warning';
        default:
            return 'supplier-alert--info';
    }
};
</script>

<style scoped>
.supplier-alert-stack {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 16px 0;
}

.supplier-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 16px;
    border-radius: 8px;
    border: 2px solid;
}

.supplier-alert__body p {
    margin: 4px 0 0;
    font-size: 14px;
}

.supplier-alert--error {
    background: #fee2e2;
    border-color: #fecaca;
    color: #991b1b;
}

.supplier-alert--warning {
    background: #fef3c7;
    border-color: #fde68a;
    color: #92400e;
}

.supplier-alert--info {
    background: #e0f2fe;
    border-color: #bae6fd;
    color: #075985;
}
</style>
