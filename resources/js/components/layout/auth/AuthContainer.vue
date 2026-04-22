<template>
    <div class="auth-container" :class="{ 'auth-container--no-illustration': !illustration }">
        <div class="left-panel">
            <slot name="panel:left">
                <BaseButton class="home-btn" href="/"><AppLogo auth /><span class="home-btn__label">{{ t('Vai alla home') }}</span></BaseButton>
            </slot>
        </div>
        <main class="right-panel">
            <div class="right-panel__boundary">
                <slot />
            </div>
        </main>
        <BbConfirm />
        <BbToast placement="top" />
    </div>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import { BaseButton, BbConfirm, BbToast } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    illustration?: boolean;
    logo?: boolean;
};
withDefaults(defineProps<Props>(), {
    illustration: true,
    logo: true,
});
</script>

<style>
@reference '@/../css/base.css';

.auth-container {
    --left-panel-size: 35%;
    --transition-duration: 0.5s;
    @apply grid overflow-x-clip;

    &.auth-container--no-illustration {
        .left-panel {
            background: rgb(251 249 249);
        }
    }

    .left-panel {
        @apply fixed inset-y-0 left-0 grid w-[var(--left-panel-size)] place-items-center lg:p-10;
        background: linear-gradient(60deg, rgb(0, 0, 0), rgb(36, 69, 99), rgb(0, 0, 0), rgb(36, 69, 99), rgb(0, 0, 0));
        background-size: 1000% 1000%;
        animation: gradientAnimation 20s linear infinite;

        > .home-btn {
            @apply inline-block;
            .app-logo {
                @apply w-40 text-white;
            }
            .home-btn__label {
                @apply sr-only;
            }
        }
    }

    .right-panel {
        @apply relative ml-0 flex flex-col justify-center bg-[var(--bb-panel)] p-10 px-[var(--min-px)] transition-all duration-[var(--transition-duration)] lg:ml-[var(--left-panel-size)] lg:pr-0 lg:pl-36;
        box-shadow: 0px 4px 20px 0px #0000000d;

        .right-panel__boundary {
            @apply relative left-1/2 w-full max-w-[460px] -translate-x-1/2 transition-all duration-[var(--transition-duration)] lg:left-0 lg:translate-x-0;

            .home-btn {
                @apply mb-4 block w-fit lg:hidden;
                .app-logo {
                    @apply w-40;
                }
                .home-btn__label {
                    @apply sr-only;
                }
            }
        }
        .right-panel__title {
            @apply text-mix-900 mb-2 text-2xl font-bold sm:text-4xl;
        }
        .right-panel__subtitle {
            @apply text-mix-600 mb-6 text-base;
        }

        .right-panel {
            @apply absolute inset-0 translate-x-full duration-300;
            transition:
                transform 0.3s,
                box-shadow 0.5s 0.3s;
            box-shadow: none;

            &.right-panel--current {
                @apply translate-x-0;
            }
        }
    }
}

@keyframes gradientAnimation {
    0%,
    100% {
        background-position: left bottom;
    }
    50% {
        background-position: right top;
    }
}
</style>
