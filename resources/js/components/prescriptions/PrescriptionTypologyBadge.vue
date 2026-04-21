<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    typology: string | null | undefined;
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
    '!bg-violet-200 border-violet-500 !text-violet-600': props.typology === 'protrusor',
    '!bg-sky-200 border-sky-500 !text-sky-600': props.typology === 'lybra_aligner',
    '!bg-emerald-200 border-emerald-500 !text-emerald-600': props.typology === 'guided_surgery',
    '!bg-amber-200 border-amber-500 !text-amber-600': props.typology === '3d_mesh',
    '!bg-rose-200 border-rose-500 !text-rose-600': props.typology === 'prosthesis',
    '!bg-indigo-200 border-indigo-500 !text-indigo-600': props.typology === 'semi_finished_prostheses',
}));

const text = computed(() => {
    switch (props.typology) {
        case 'protrusor':
            return t('Protrusor');
        case 'lybra_aligner':
            return t('Lybra Aligner');
        case 'guided_surgery':
            return t('Chirurgia guidata');
        case '3d_mesh':
            return t('3D Mesh');
        case 'prosthesis':
            return t('Protesi');
        case 'semi_finished_prostheses':
            return t('Semilavorato di protesi');
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
