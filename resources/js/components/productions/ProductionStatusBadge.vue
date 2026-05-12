<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Props = {
    status: string | null | undefined;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | undefined;
    verbose?: boolean;
    timestamp?: string | null;
};

const props = withDefaults(defineProps<Props>(), {
    verbose: false,
    timestamp: null,
});

const formattedTimestamp = computed(() =>
    props.timestamp ? new Date(props.timestamp).toLocaleDateString('it-IT') : null,
);

const { t } = useI18n();

const classes = computed(() => ({
    'w-fit whitespace-nowrap rounded-md border px-3 py-1 text-sm leading-none flex items-center justify-center': true,
    '!text-xs': props.size === 'xs',
    '!text-sm': props.size === 'sm',
    '!text-base': props.size === 'md',
    '!text-lg': props.size === 'lg',
    '!text-xl': props.size === 'xl',
    'bg-amber-100 border-amber-500 text-amber-700': !props.status && !props.verbose,
    '!bg-gray-200 border-gray-400 !text-gray-700': !props.status && props.verbose,
    '!bg-yellow-200 border-yellow-500 !text-yellow-700': props.status === 'confirmed',
    '!bg-red-100 border-red-500 !text-red-700': props.status === 'canceled',
    '!bg-green-200 border-green-500 !text-green-700': props.status === 'completed',
}));

const text = computed(() => {
    const ts = formattedTimestamp.value;
    switch (props.status) {
        case 'confirmed':
            return ts ? `${t('Confermata il:')} ${ts}` : t('Confermata');
        case 'canceled':
            return ts ? `${t('Annullata il:')} ${ts}` : t('Annullata');
        case 'completed':
            return ts ? `${t('Completata il:')} ${ts}` : t('Completata');
        default:
            return props.verbose ? t('Da confermare') : '--';
    }
});
</script>

<template>
    <div :class="classes">
        {{ text }}
    </div>
</template>
