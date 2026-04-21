<template>
    <div class="common-page">
        <div class="centered-container-no-min">
            <slot name="header">
                <h1 class="page__title">{{ title }}</h1>
                <p class="page__subtitle">{{ subtitle }}</p>
            </slot>
            <BbAlert :model-value="!!error" :text="error" theme="error" :title="t('Errore')" />
            <CardContainer class="common-page__card">
                <slot />
            </CardContainer>
        </div>
    </div>
</template>

<script setup lang="ts">
import { BbAlert } from 'bitboss-ui';
import CardContainer from '../common/CardContainer.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    title?: string;
    subtitle?: string;
    error?: string;
};
defineProps<Props>();
</script>

<style>
@reference '@/../css/base.css';
.common-page {
    @apply mx-auto w-full p-5;

    .bb-alert {
        @apply sticky top-1 z-10;

        &.bb-collapsible--open {
            @apply mb-6;
        }
    }

    .card {
        @apply p-[var(--min-px)] pt-5 pb-[var(--min-px)];
    }
}
</style>
