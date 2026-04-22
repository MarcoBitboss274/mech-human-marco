<template>
    <Head :title="t('Password dimenticata?')" />
    <AuthContainer>
        <div class="password-forgot">
            <BaseButton class="home-btn" href="/"><AppLogo auth /><span class="home-btn__label">{{ t('Vai alla home') }}</span></BaseButton>
            <h1 class="right-panel__title">{{ t('Password dimenticata?') }}</h1>
            <p class="right-panel__subtitle">
                {{ t('Nessun problema! Inserisci l\'email con cui ti sei registrato e ti invieremo le istruzioni per reimpostare la tua password') }}
            </p>

            <form class="password-forgot__form" @submit.prevent="submit">
                <div class="form-grid">
                    <BbTextInput v-model="form.email" autocomplete="email" :label="t('Email')" placeholder="mariorossi@example.com" required type="email" />
                </div>
                <XStepper class="-mx-1 -my-1 px-1 py-1" direction="top" :model-value="running ? 2 : 1">
                    <XStep>
                        <div class="button-stack">
                            <BbButton block :size="{ lg: 'lg' }" type="submit">{{ t('Invia') }}</BbButton>
                            <BbButton block class="bb-button--primary-outline" :size="{ lg: 'lg' }" :href="route('login')">{{ t('Torna al login') }}</BbButton>
                        </div>
                    </XStep>
                    <XStep>
                        <div class="password-forgot__requested">
                            <p class="">
                                {{ t("Ti abbiamo inviato un'email con le istruzioni per reimpostare la tua password. Potrai richiederne una nuova in") }}
                                <strong>{{ remainingFormatted.seconds }}</strong> {{ t('secondi.') }}
                            </p>

                            <BbProgress :model-value="progress" />
                        </div>
                    </XStep>
                </XStepper>
            </form>
        </div>
    </AuthContainer>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import XStep from '@/components/common/XStep.vue';
import XStepper from '@/components/common/XStepper.vue';
import AuthContainer from '@/components/layout/auth/AuthContainer.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { BaseButton, BbButton, BbProgress, BbTextInput, useCountdown, useToast } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const { toast } = useToast();
const status = computed(() => usePage().props.status);

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => {
            start();
        },
        onError: () => {
            toast({
                theme: 'error',
                text: t('Questo indirizzo email non corrisponde a nessun account, attendi qualche minuto e riprova'),
            });
        },
    });
};

const { start, running, progress, reset, remainingFormatted } = useCountdown({
    duration: 1000 * 30,
    onFinish: () => {
        reset();
    },
});

watch(status, (s) => {
    if (s) {
        toast({
            theme: 'success',
            text: t('Email inviata con successo'),
        });
    }
});
</script>

<style>
@reference '@/../css/base.css';

.password-forgot {
    .password-forgot__form {
        .form-grid {
            @apply mb-3;
        }
        .bb-alert.bb-collapsible--open {
            @apply mb-3;
        }
    }
    .password-forgot__requested {
        @apply mb-6 text-sm;

        .bb-progress {
            @apply mt-3;
        }
    }
}
</style>
