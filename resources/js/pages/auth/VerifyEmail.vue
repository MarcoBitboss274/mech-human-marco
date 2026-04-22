<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import AuthContainer from '@/components/layout/auth/AuthContainer.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { BaseButton, BbButton, useToast } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const user = computed(() => usePage().props.auth.user);
const status = computed(() => usePage().props.status);
const { toast } = useToast();
const form = useForm({});

const resend = () => {
    form.post(route('verification.send'));
};

const logout = () => {
    router.post(route('logout'));
};

watch(status, (s) => {
    if (s === 'verification-link-sent') {
        toast({
            theme: 'success',
            text: t('Email inviata con successo'),
        });
    }
});
</script>

<template>
    <Head :title="t('Verifica la tua email')" />
    <AuthContainer>
        <div>
            <BaseButton class="home-btn" href="/"> <AppLogo auth class="w-40" /><span class="home-btn__label">{{ t('Vai alla home') }}</span> </BaseButton>

            <h1 class="right-panel__title">
                {{ t('Hai quasi finito!') }} <br />
                {{ t('Verifica la tua email') }}
            </h1>

            <p class="right-panel__subtitle mt-6">
                {{ t("Abbiamo inviato un link di verifica all'indirizzo") }}
                <strong>{{ user.email }}</strong>
            </p>
            <div class="mb-6">
                <div class="flex items-center justify-start gap-2">
                    <p class="text-sm text-[#374151]">{{ t("Non l'hai ricevuta?") }}</p>
                    <BbButton append:icon="arrow-right" size="xs" @click="resend">{{ t('Reinvia') }}</BbButton>
                </div>
                <BbButton class="mt-6" size="xs" @click="logout">{{ t('Logout') }}</BbButton>
            </div>
        </div>
    </AuthContainer>
</template>
