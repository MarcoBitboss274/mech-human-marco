<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    canceled_at: string | null | undefined;
};

const props = defineProps<Props>();

const isVisible = computed(() => !!props.canceled_at);

const classes = computed(() => ({
    'px-3 py-1 rounded-md bg-gray-100 text-gray-500 w-fit text-sm leading-none flex items-center justify-center border whitespace-nowrap': true,
    '!bg-red-200 border-red-500 !text-red-700': true,
}));
</script>

<template>
    <div v-if="isVisible" :class="classes">
        {{ t('Cancellata') }}
    </div>
</template>

