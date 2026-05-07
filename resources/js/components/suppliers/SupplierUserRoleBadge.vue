<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    role: string | null;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
};

const props = defineProps<Props>();

const classes = computed(() => ({
    'px-3 py-1 rounded-md bg-gray-100 text-gray-500 w-fit text-sm leading-none flex items-center justify-center border whitespace-nowrap': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    '!bg-violet-200 border-violet-500 !text-violet-600': props.role === 'admin',
    '!bg-sky-200 border-sky-500 !text-sky-600': props.role === 'member',
}));

const text = computed(() => {
    switch (props.role) {
        case 'admin':
            return t('Admin');
        case 'member':
            return t('Membro');
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
