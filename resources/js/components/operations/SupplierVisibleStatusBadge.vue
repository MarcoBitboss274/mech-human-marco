<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    status: string | null | undefined;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
});

const variantClass = computed(() => {
    switch (props.status) {
        case 'new_case':
            return '!bg-yellow-200 border-yellow-500 !text-yellow-700';
        case 'production_confirmed':
            return '!bg-purple-200 border-purple-500 !text-purple-700';
        case 'completed':
            return '!bg-green-200 border-green-500 !text-green-600';
        default:
            return '';
    }
});

const classes = computed(() => ({
    'flex w-fit items-center justify-center whitespace-nowrap rounded-md border bg-gray-100 px-3 py-1 leading-none text-gray-500': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    [variantClass.value]: true,
}));

const text = computed(() => {
    switch (props.status) {
        case 'new_case':
            return t('Nuovo caso');
        case 'production_confirmed':
            return t('Produzione confermata');
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
