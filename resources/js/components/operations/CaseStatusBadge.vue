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
        case 'open':
            return '!bg-sky-200 border-sky-500 !text-sky-700';
        case 'completed':
            return '!bg-green-200 border-green-500 !text-green-700';
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
        case 'open':
            return t('Aperta');
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
