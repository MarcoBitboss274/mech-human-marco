<script setup lang="ts">
import OperationQuoteStatusBadge from '@/components/operations/OperationQuoteStatusBadge.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { Quote } from '@/types/Quote';
import { dateTime } from '@/utils/formatters/date';
import { BbButton, BbPopover } from 'bitboss-ui';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Mode = 'admin' | 'customer';

type Props = {
    quote: Quote;
    mode: Mode;
    sendingId?: number | null;
    acceptingId?: number | null;
};

const props = withDefaults(defineProps<Props>(), {
    sendingId: null,
    acceptingId: null,
});

const emit = defineEmits<{
    (e: 'edit', quote: Quote): void;
    (e: 'send', quote: Quote): void;
    (e: 'accept', quote: Quote): void;
    (e: 'reject', quote: Quote): void;
    (e: 'cancel', quote: Quote): void;
    (e: 'delete', quote: Quote): void;
}>();

const { t } = useI18n();
const { can } = usePermissions();

const canManage = computed(() => props.mode === 'admin' && can('operations.quote.manage'));
const isSent = computed(() => props.quote.status === 'sent');
const isDraft = computed(() => props.quote.status === 'draft');
const showAcceptReject = computed(() => {
    if (!isSent.value) return false;
    return props.mode === 'customer' || canManage.value;
});
</script>

<template>
    <article class="operation-quotes__card">
        <div class="operation-quotes__card-header">
            <h3 class="operation-quotes__card-title">{{ t('Preventivo') }} {{ quote.id }}</h3>
            <div class="operation-quotes__actions">
                <BbButton v-if="canManage" size="xs" icon="pencil" @click="emit('edit', quote)">
                    {{ t('Modifica') }}
                </BbButton>
                <BbButton
                    v-if="canManage && isDraft"
                    size="xs"
                    append:icon="play"
                    :disabled="sendingId === quote.id"
                    @click="emit('send', quote)"
                >
                    {{ t('Invia') }}
                </BbButton>
                <BbButton
                    v-if="showAcceptReject"
                    size="xs"
                    :disabled="acceptingId === quote.id"
                    @click="emit('accept', quote)"
                >
                    {{ t('Accetta') }}
                </BbButton>
                <BbButton v-if="showAcceptReject" size="xs" variant="outline" @click="emit('reject', quote)">
                    {{ t('Rifiuta') }}
                </BbButton>
                <BbButton v-if="canManage && isSent" size="xs" variant="outline" @click="emit('cancel', quote)">
                    {{ t('Annulla') }}
                </BbButton>
                <BbPopover v-if="canManage">
                    <template #activator="{ props: popoverProps }">
                        <BbButton size="xs" variant="danger" v-bind="popoverProps">
                            {{ t('Elimina') }}
                        </BbButton>
                    </template>
                    <template #default="{ close }">
                        <p class="mb-2 max-w-[250px]">
                            {{ t('Sei sicuro di voler eliminare') }}
                            <strong> {{ t('Preventivo') }} {{ quote.id }}?</strong>
                        </p>
                        <div class="text-right">
                            <BbButton
                                variant="danger"
                                size="xs"
                                @click="
                                    () => {
                                        emit('delete', quote);
                                        close();
                                    }
                                "
                            >
                                {{ t('Elimina') }}
                            </BbButton>
                        </div>
                    </template>
                </BbPopover>
            </div>
        </div>

        <div class="operation-quotes__status">
            <OperationQuoteStatusBadge :status="quote.status" size="xs" />
        </div>

        <div class="operation-quotes__meta">
            <div class="operation-quotes__meta-item">
                <span class="operation-quotes__meta-label">{{ t('Accettato il') }}</span>
                <span>{{ dateTime(quote.accepted_at) ?? '--' }}</span>
            </div>
            <div class="operation-quotes__meta-item">
                <span class="operation-quotes__meta-label">{{ t('Note') }}</span>
                <span>{{ quote.notes ?? '--' }}</span>
            </div>
        </div>
    </article>
</template>

<style>
@reference '@/../css/base.css';

.operation-quotes__card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operation-quotes__card-header {
    @apply flex items-start justify-between gap-3;
}

.operation-quotes__card-title {
    @apply text-base font-semibold text-gray-900;
}

.operation-quotes__actions {
    @apply flex items-center gap-2;
}

.operation-quotes__status {
    @apply mt-3;
}

.operation-quotes__meta {
    @apply mt-3 space-y-2;
}

.operation-quotes__meta-item {
    @apply flex flex-col gap-1 text-sm text-gray-700;
}

.operation-quotes__meta-label {
    @apply text-xs text-gray-500;
}
</style>
