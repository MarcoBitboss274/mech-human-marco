<template>
    <footer class="layout-footer">
        <div class="layout-footer__container">
            <p class="layout-footer__text">&copy; {{ new Date().getFullYear() }} {{ t('BitBoss - C.so Vittorio Emanuele II, 74, 10121 Torino') }}</p>
            <nav class="layout-footer__nav">
                <ul class="layout-footer__list">
                    <li class="layout-footer__item">
                        <BaseButton class="base-btn--link layout-footer__link" href="/privacy-policy">{{ t('Privacy Policy') }}</BaseButton>
                    </li>
                    <li class="layout-footer__item">
                        <BaseButton class="base-btn--link layout-footer__link" href="/terms-of-service">{{ t('Terms of Service') }}</BaseButton>
                    </li>
                    <li class="layout-footer__item">
                        <BaseButton class="base-btn--link layout-footer__link" href="/contact">{{ t('Contact') }}</BaseButton>
                    </li>
                    <li class="layout-footer__item">
                        <BaseButton class="base-btn--link layout-footer__link" :href="route('home')">{{ t('FAQ') }}</BaseButton>
                    </li>
                </ul>
            </nav>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { BaseButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<style>
@reference '@/../css/base.css';
.layout-footer {
    &__container {
        @apply flex min-h-[80px] flex-col items-center justify-center bg-[var(--bb-primary)] px-[var(--min-px)] text-center text-[var(--bb-contrasting)] md:flex-row;
    }
    &__list {
        @apply ml-2 flex gap-x-2;
    }
}
</style>
