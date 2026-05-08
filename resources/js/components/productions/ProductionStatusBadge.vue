<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Props = {
    status: string | null | undefined;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | undefined;
};

const props = defineProps<Props>();

const { t } = useI18n();

const classes = computed(() => ({
    'w-fit whitespace-nowrap rounded-md border px-3 py-1 text-sm leading-none flex items-center justify-center': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    'bg-amber-100 border-amber-500 text-amber-700': !props.status,
    '!bg-green-100 border-green-500 !text-green-700': props.status === 'confirmed',
    '!bg-red-100 border-red-500 !text-red-700': props.status === 'canceled',
    '!bg-emerald-200 border-emerald-600 !text-emerald-800': props.status === 'completed',
}));

const text = computed(() => {
    switch (props.status) {
        case 'confirmed':
            return t('Confermata');
        case 'canceled':
            return t('Annullata');
        case 'completed':
            return t('Completata');
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

