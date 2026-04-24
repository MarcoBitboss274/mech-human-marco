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
    'px-3 py-1 rounded-md bg-gray-100 text-gray-500 w-fit text-sm leading-none flex items-center justify-center border whitespace-nowrap': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    '!bg-gray-200 border-gray-500 !text-gray-700': props.status === 'draft',
    '!bg-blue-200 border-blue-500 !text-blue-700': props.status === 'sent',
    '!bg-orange-200 border-orange-500 !text-orange-700': props.status === 'in_review',
    '!bg-sky-200 border-sky-500 !text-sky-700': props.status === 'revised',
    '!bg-green-200 border-green-500 !text-green-700': props.status === 'confirmed',
}));

const text = computed(() => {
    switch (props.status) {
        case 'draft':
            return t('Bozza');
        case 'sent':
            return t('Inviata');
        case 'in_review':
            return t('In revisione');
        case 'revised':
            return t('Revisionata');
        case 'confirmed':
            return t('Confermata');
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
