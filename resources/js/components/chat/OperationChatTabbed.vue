<template>
    <BbOffCanvas v-model="modelValue" direction="right" :title="t('Chat lavorazione')" overlay-classes="chat-slider-offcanvas">
        <div class="chat-tabbed">
            <BbTab v-model="active" :items="tabs">
                <template #customer>
                    <ChatSlider
                        v-if="active === 'customer'"
                        :model-value="true"
                        embedded
                        chat-scope="customer"
                        :operation-id="operationId"
                        :current-user-id="currentUserId"
                        :can-read="canReadCustomer"
                        :can-send="canSendCustomer"
                    />
                </template>
                <template #supplier>
                    <ChatSlider
                        v-if="active === 'supplier' && hasSupplier"
                        :model-value="true"
                        embedded
                        chat-scope="supplier"
                        :operation-id="operationId"
                        :current-user-id="currentUserId"
                        :can-read="canReadSupplier"
                        :can-send="canSendSupplier"
                    />
                    <div v-else-if="!hasSupplier" class="p-4 text-sm text-slate-500">
                        {{ t('Nessun fornitore assegnato.') }}
                    </div>
                </template>
            </BbTab>
        </div>
    </BbOffCanvas>
</template>

<script setup lang="ts">
import ChatSlider from '@/components/chat/ChatSlider.vue';
import { BbOffCanvas, BbTab, type BbTabItem } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

type Props = {
    operationId: number;
    currentUserId: number;
    canReadCustomer: boolean;
    canSendCustomer: boolean;
    canReadSupplier: boolean;
    canSendSupplier: boolean;
    hasSupplier: boolean;
};

const props = defineProps<Props>();
const { t } = useI18n();

const modelValue = defineModel<boolean>('modelValue', { required: true, default: false });

const active = ref<string>('customer');

const tabs = computed<BbTabItem[]>(() => [
    { key: 'customer', label: t('Chat Customer') },
    {
        key: 'supplier',
        label: t('Chat Fornitore'),
        disabled: !props.hasSupplier,
    },
]);
</script>

<style scoped>
.chat-tabbed {
    display: flex;
    flex-direction: column;
    height: 100%;
}
</style>
