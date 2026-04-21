<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    gender: string | null | undefined;
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
    '!bg-blue-200 border-blue-500 !text-blue-700': props.gender === 'male',
    '!bg-pink-200 border-pink-500 !text-pink-700': props.gender === 'female',
    '!bg-violet-200 border-violet-500 !text-violet-700': props.gender === 'other',
}));

const text = computed(() => {
    switch (props.gender) {
        case 'male':
            return t('Maschio');
        case 'female':
            return t('Femmina');
        case 'other':
            return t('Altro');
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
