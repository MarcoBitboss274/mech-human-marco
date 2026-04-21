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
    'bg-amber-100 border-amber-500 text-amber-700': !props.status || props.status === 'pending',
    '!bg-green-100 border-green-500 !text-green-700': props.status === 'confirmed',
}));

const text = computed(() => {
    switch (props.status) {
        case 'pending':
            return t('In corso');
        case 'confirmed':
            return t('Confermato');
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
