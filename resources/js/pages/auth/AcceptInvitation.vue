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
    token: props.token ?? '',
    name: '',
    surname: '',
    password: '',
    password_confirmation: '',
    privacy: false,
});

const submit = () => {
    form.post(route('invitation.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('Accetta invito')" />

    <AuthContainer>
        <div class="accept-invitation">
            <BaseButton class="mb-6 flex w-full items-center lg:hidden" href="/">
                <AppLogo class="w-40" /><span class="home-btn__label">{{ t('Vai alla home') }}</span>
            </BaseButton>
            <h1 class="text-3xl font-bold text-[#171717]">{{ t('Accetta invito') }}</h1>
            <p class="my-4 text-sm text-[#374151]">{{ t('Completa il tuo profilo inserendo nome, cognome e impostando una password per accedere.') }}</p>
            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.token" type="hidden" name="token" />
                <BbTextInput :model-value="email" :label="t('Email')" disabled type="email" />
                <BbTextInput v-model="form.name" autocomplete="given-name" :label="t('Nome')" required :errors="form.errors?.name" />
                <BbTextInput v-model="form.surname" autocomplete="family-name" :label="t('Cognome')" required :errors="form.errors?.surname" />
                <BbTextInput
                    v-model="form.password"
                    autocomplete="new-password"
                    :label="t('Password')"
                    placeholder="********"
                    required
                    type="password"
                    :errors="form.errors?.password"
                />
                <BbTextInput
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    :label="t('Conferma password')"
                    placeholder="********"
                    required
                    type="password"
                />
                <BbButton type="submit" :disabled="form.processing">{{ t('Accetta invito') }}</BbButton>
            </form>
        </div>
    </AuthContainer>
</template>
