<template>
    <div class="x-pagination">
        <span class="x-pagination__text">{{ Math.min(perPage, totalItems) }} {{ t('di') }} {{ totalItems }} {{ t('elementi') }}</span>
        <BbPagination v-model="model" :disabled="disabled" :per-page="perPage" :total-pages="totalPages" />
    </div>
</template>

<script setup lang="ts">
import { BbPagination } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    disabled?: boolean;
    perPage: number;
    totalItems: number;
    totalPages: number;
};

defineProps<Props>();

const model = defineModel<number>({
    default: 1,
});
</script>

<style>
@reference '@/../css/base.css';
.x-pagination {
    @apply flex flex-wrap items-center justify-between gap-5 whitespace-nowrap md:flex-nowrap;

    .x-pagination__text {
        @apply text-sm font-medium;
    }
}
</style>
