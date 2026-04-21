<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    status: string | null | undefined;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | undefined;
};

const props = defineProps<Props>();

const classes = computed(() => ({
    'w-fit whitespace-nowrap rounded-md border px-3 py-1 text-sm leading-none flex items-center justify-center': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    'bg-gray-100 border-gray-300 text-gray-700': !props.status || props.status === 'draft',
    '!bg-yellow-100 border-yellow-400 !text-yellow-700': props.status === 'sent',
    '!bg-green-100 border-green-500 !text-green-700': props.status === 'accepted',
    '!bg-red-100 border-red-500 !text-red-700': props.status === 'rejected',
    '!bg-zinc-200 border-zinc-500 !text-zinc-700': props.status === 'canceled',
}));

const text = computed(() => {
    switch (props.status) {
        case 'draft':
            return t('Bozza');
        case 'sent':
            return t('Inviato');
        case 'accepted':
            return t('Accettato');
        case 'rejected':
            return t('Rifiutato');
        case 'canceled':
            return t('Annullato');
        default:
            return '--';
    }
});
</script>

<template>
    <div :class="classes">
        {{ text }}
    </div>
</template>
