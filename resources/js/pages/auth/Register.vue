<template>
    <Head :title="t('Registrati')" />
    <AuthContainer>
        <div class="register">
            <BaseButton class="home-btn" href="/"
                ><AppLogo /><span class="home-btn__label">{{ t('Vai alla home') }}</span></BaseButton
            >
            <h1 class="register__title">{{ t('Benvenuto! 👋') }}</h1>
            <p class="register__subtitle"></p>
            <form class="register__form" @submit.prevent="submit">
                <div class="form-grid">
                    <BbTextInput
                        id="firstname"
                        v-model="form.name"
                        autocomplete="name"
                        :errors="form.errors.name"
                        :label="t('Nome')"
                        :placeholder="t('Mario')"
                    ></BbTextInput>
                    <BbTextInput
                        v-model="form.surname"
                        autocomplete="family-name"
                        :errors="form.errors.surname"
                        :label="t('Cognome')"
                        :placeholder="t('Rossi')"
                    ></BbTextInput>
                    <BbTextInput
                        class="col-span-2"
                        v-model="form.email"
                        autocomplete="email"
                        :errors="form.errors.email"
                        :label="t('Email')"
                        :placeholder="t('Inserisci la tua email')"
                        type="email"
                    ></BbTextInput>

                    <BbCheckbox v-model="form.odontoiatra" :label="t('Odontoiatra')" />
                    <BbCheckbox v-model="form.odontotecnico" :label="t('Odontotecnico')" />
                    <template v-if="form.odontoiatra">
                        <BbTextInput
                            v-model="form.roll_number"
                            :label="t('Numero iscrizione albo')"
                            :placeholder="t('Numero')"
                            :errors="form.errors.roll_number"
                        />
                        <BbTextInput
                            v-model="form.roll_province"
                            :label="t('Provincia albo')"
                            :placeholder="t('Provincia')"
                            :errors="form.errors.roll_province"
                        />
                    </template>

                    <BbTextInput
                        v-model="form.password"
                        autocomplete="new-password"
                        :errors="form.errors.password"
                        :label="t('Password')"
                        :placeholder="t('Digita la tua nuova password')"
                        :type="passwordType"
                    >
                        <template #append>
                            <EyeToggle :shown="passwordType === 'text'" @click="passwordType = passwordType === 'text' ? 'password' : 'text'" />
                        </template>
                    </BbTextInput>
                    <BbTextInput
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        :errors="form.errors.password_confirmation"
                        :label="t('Conferma password')"
                        :placeholder="t('Conferma la tua nuova password')"
                        :type="passwordType"
                    >
                        <template #append>
                            <EyeToggle :shown="passwordType === 'text'" @click="passwordType = passwordType === 'text' ? 'password' : 'text'" />
                        </template>
                    </BbTextInput>
                    <BbCheckbox class="col-span-2" v-model="form.privacy" :errors="form.errors.privacy" :label="t('Ho letto e accetto la ')">
                        <template #label="{ text }">
                            {{ text }}
                            <BaseButton class="base-btn--link" target="_blank">{{ t('Privacy policy') }}</BaseButton>
                            {{ t('e i') }}
                            <BaseButton class="base-btn--link" target="_blank">{{ t('Termini e condizioni') }}</BaseButton>
                        </template>
                    </BbCheckbox>
                </div>
                <div class="button-stack">
                    <BbButton block :loading="form.processing" :size="{ lg: 'lg' }" type="submit">{{ t('Iniziamo') }}</BbButton>
                    <!-- <BbButton block class="bb-button--primary-outline" :disabled="form.processing" prepend:icon="google" :size="{ lg: 'lg' }" type="submit"
                        >Accedi con Google</BbButton
                    > -->
                </div>
                <div class="register__registered">
                    <p>
                        {{ t('Hai già un account?') }}
                        <BaseButton class="base-btn--link" :disabled="form.processing" :href="route('login')">{{ t('Accedi') }}</BaseButton>
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
import { Head, useForm } from '@inertiajs/vue3';
import { BaseButton, BbButton, BbCheckbox, BbTextInput } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    name: null,
    surname: null,
    email: null,
    password: null,
    password_confirmation: null,
    privacy: false,
    odontoiatra: false,
    odontotecnico: false,
    roll_number: null,
    roll_province: null,
});

const passwordType = ref<'text' | 'password'>('password');

const submit = () => {
    form.post(route('register'));
};
</script>

<style>
@reference '@/../css/base.css';

.register {
    .register__title {
        @apply mb-2 text-2xl font-bold sm:text-4xl;
    }

    .register__subtitle {
        @apply text-mix-600 mb-6 text-base;
    }
    .register__form {
        .form-grid {
            @apply mb-6 grid gap-4 md:grid-cols-2;
        }
    }
    .bb-alert.bb-collapsible--open {
        @apply mb-4;
    }
    .register__registered {
        @apply mt-3 text-center text-sm;
    }
}
</style>
