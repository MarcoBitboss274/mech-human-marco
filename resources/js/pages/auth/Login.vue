<template>
    <Head :title="t('Login')" />
    <AuthContainer>
        <div class="login">
            <BaseButton class="home-btn" href="/"><AppLogo auth /><span class="home-btn__label">{{ t('Vai alla home') }}</span></BaseButton>
            <h1 class="right-panel__title">{{ t('Bentornato! 👋') }}</h1>
            <p class="right-panel__subtitle">{{ t('Siamo contenti di rivederti, effettua il login al tuo account per continuare') }}</p>

            <form class="login__form" @submit.prevent="submit">
                <div class="form-grid">
                    <BbTextInput
                        v-model="form.email"
                        autocomplete="email"
                        :label="t('Email')"
                        placeholder="mariorossi@example.com"
                        type="email"
                        :errors="form.errors.email"
                    />
                    <BbTextInput
                        v-model="form.password"
                        autocomplete="current-password"
                        :label="t('Password')"
                        placeholder="••••••••"
                        :type="passwordType"
                        :errors="form.errors.password"
                    >
                        <template #append>
                            <EyeToggle :shown="passwordType === 'text'" @click="passwordType = passwordType === 'text' ? 'password' : 'text'" />
                        </template>
                    </BbTextInput>
                    <div class="forgot-password-container">
                        <BaseButton class="base-btn--link" :disabled="form.processing" :href="route('password.request')">
                            <span>{{ t('Password dimenticata?') }}</span>
                        </BaseButton>
                    </div>
                </div>

                <!-- <BbAlert
                    :model-value="!!form.errors.email || !!form.errors.password"
                    :text="form.errors.email || form.errors.password"
                    theme="error"
                    title="Errore"
                /> -->
                <div class="button-stack">
                    <BbButton block :loading="form.processing" :size="{ lg: 'lg' }" type="submit">{{ t('Login') }}</BbButton>
                    <!-- <BbButton block class="bb-button--primary-outline" :disabled="loading" prepend:icon="google" :size="{ lg: 'lg' }"
                        >Accedi con Google</BbButton
                    > -->
                </div>
                <div class="login__unregistered">
                    <p>
                        {{ t('Non hai un account?') }}
                        <BaseButton class="base-btn--link" :disabled="form.processing" :href="route('register')">{{ t('Registrati') }}</BaseButton>
                    </p>
                </div>
            </form>
        </div>
    </AuthContainer>
</template>

<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import EyeToggle from '@/components/common/EyeToggle.vue';
import AuthContainer from '@/components/layout/auth/AuthContainer.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { BaseButton, BbButton, BbTextInput, useToast } from 'bitboss-ui';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    email: '',
    password: '',
});

const { toast } = useToast();

const passwordType = ref<'text' | 'password'>('password');
const status = computed(() => usePage().props.status);
watch(status, (s) => {
    if (s) {
        toast({
            theme: 'success',
            text: t('Password impostata con successo'),
        });
    }
});

const submit = () => {
    form.post(route('login'));
};
</script>

<style>
@reference '@/../css/base.css';

.login {
    .login__form {
        .form-grid {
            @apply mb-6;
        }
    }
    .forgot-password-container {
        @apply -mt-4 -mb-1 text-right;
    }
    .bb-alert.bb-collapsible--open {
        @apply mb-4;
    }
    .login__unregistered {
        @apply mt-3 text-center text-sm;
    }
}
</style>
