<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import AuthContainer from '@/components/layout/auth/AuthContainer.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { BaseButton, BbButton, BbTextInput } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('Imposta nuova password')" />

    <AuthContainer>
        <div class="reset-password">
            <BaseButton class="mb-6 flex w-full items-center lg:hidden" href="/">
                <AppLogo class="w-40" /><span class="home-btn__label">{{ t('Vai alla home') }}</span>
            </BaseButton>
            <h1 class="text-3xl font-bold text-[#171717]">{{ t('Imposta nuova password') }}</h1>
            <p class="my-4 text-sm text-[#374151]">{{ t('Per impostare la tua password, inserisci la tua password e clicca su "Imposta nuova password".') }}</p>
            <form class="space-y-3">
                <BbTextInput v-model="form.email" autocomplete="email" :label="t('Email')" placeholder="" required type="email" disabled />
                <BbTextInput v-model="form.password" autocomplete="password" :label="t('Password')" placeholder="********" required type="password" />
                <BbTextInput
                    v-model="form.password_confirmation"
                    autocomplete="password_confirmation"
                    :label="t('Conferma password')"
                    placeholder="********"
                    required
                    type="password"
                />
                <BbButton @click="submit">{{ t('Imposta nuova password') }}</BbButton>
            </form>
        </div>
    </AuthContainer>
</template>
