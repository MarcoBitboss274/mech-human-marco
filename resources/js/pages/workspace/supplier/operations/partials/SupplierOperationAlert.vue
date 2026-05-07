<template>
    <div v-if="banner" class="supplier-alert" :class="bannerClass">
        <div class="supplier-alert__body">
            <strong>{{ banner.title }}</strong>
            <p>{{ banner.message }}</p>
        </div>
        <BbButton v-if="banner.cta && onGoToUpload" size="sm" @click="onGoToUpload">
            {{ banner.cta }}
        </BbButton>
    </div>
</template>

<script setup lang="ts">
import type { Operation } from '@/types/Operation';
import { BbButton } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Props = {
    operation: Operation & {
        supplier_visible_status?: string | null;
        production_canceled_at?: string | null;
        canceled_at?: string | null;
    };
    onGoToUpload?: () => void;
};

const props = defineProps<Props>();
const { t } = useI18n();

type Banner = {
    title: string;
    message: string;
    theme: 'error' | 'warning' | 'info' | 'success' | 'success-muted';
    cta?: string;
};

const banner = computed<Banner | null>(() => {
    const status = props.operation.supplier_visible_status;

    if (status === 'canceled') {
        return {
            title: t('Lavorazione annullata'),
            message: t('Questa lavorazione è stata annullata da M&H.'),
            theme: 'error',
        };
    }

    if (props.operation.production_canceled_at && status === 'under_evaluation') {
        return {
            title: t('Produzione annullata'),
            message: t('M&H ha annullato l’avvio produzione.'),
            theme: 'warning',
        };
    }

    if (status === 'assigned_waiting_documents') {
        return {
            title: t('Documenti mancanti'),
            message: t('Carica i documenti necessari a M&H per procedere con il preventivo.'),
            theme: 'info',
            cta: t('Vai a Documenti da caricare'),
        };
    }

    if (status === 'documents_sent' || status === 'under_evaluation') {
        return {
            title: t('Documenti inviati, in valutazione'),
            message: t('Documenti inviati. M&H sta elaborando il preventivo. Riceverai un avviso quando la produzione sarà confermata.'),
            theme: 'info',
        };
    }

    if (status === 'production_confirmed') {
        return {
            title: t('Produzione confermata'),
            message: t('Produzione confermata. Puoi procedere con la lavorazione.'),
            theme: 'success',
        };
    }

    if (status === 'completed') {
        return {
            title: t('Completata'),
            message: t('Lavorazione completata.'),
            theme: 'success-muted',
        };
    }

    return null;
});

const bannerClass = computed(() => {
    switch (banner.value?.theme) {
        case 'error':
            return 'supplier-alert--error';
        case 'warning':
            return 'supplier-alert--warning';
        case 'success':
            return 'supplier-alert--success';
        case 'success-muted':
            return 'supplier-alert--success-muted';
        default:
            return 'supplier-alert--info';
    }
});
</script>

<style scoped>
.supplier-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin: 16px 0;
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

.supplier-alert--success {
    background: #dcfce7;
    border-color: #bbf7d0;
    color: #166534;
}

.supplier-alert--success-muted {
    background: #f0fdf4;
    border-color: #dcfce7;
    color: #166534;
}
</style>
