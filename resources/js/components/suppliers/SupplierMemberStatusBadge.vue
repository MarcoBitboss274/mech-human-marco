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
    '!bg-green-200 border-green-500 !text-green-600': props.status === 'active',
    '!bg-amber-200 border-amber-500 !text-amber-700': props.status === 'pending',
}));

const text = computed(() => {
    switch (props.status) {
        case 'active':
            return t('Attivo');
        case 'pending':
            return t('In attesa');
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
