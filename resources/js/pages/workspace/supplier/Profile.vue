<template>
    <div>
        <h1 class="page__title">{{ t('Il mio profilo') }}</h1>
        <p class="page__subtitle">{{ t('Aggiorna i tuoi dati personali.') }}</p>

        <form class="profile-form" @submit.prevent="submit">
            <BbTextInput v-model="form.name" autocomplete="off" :label="t('Nome')" required :errors="form.errors.name" />
            <BbTextInput v-model="form.surname" autocomplete="off" :label="t('Cognome')" required :errors="form.errors.surname" />
            <BbTextInput v-model="form.email" autocomplete="off" :label="t('Email')" required type="email" :errors="form.errors.email" />
            <BbTextInput
                v-model="form.password"
                autocomplete="new-password"
                :label="t('Nuova password (opzionale)')"
                type="password"
                :errors="form.errors.password"
            />
            <BbTextInput
                v-model="form.password_confirmation"
                autocomplete="new-password"
                :label="t('Conferma password')"
                type="password"
            />

            <div class="profile-form__actions">
                <BbButton :loading="form.processing" type="submit">{{ t('Salva') }}</BbButton>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { BbButton, BbTextInput, useToast } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();
const page = usePage();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Il mio profilo' }, () => [page]),
});

defineProps<{
    supplier: { id: number; name: string | null };
}>();

const user = (page.props.auth as { user: { name: string; surname: string; email: string } }).user;

const form = useForm({
    name: user.name,
    surname: user.surname,
    email: user.email,
    password: null as string | null,
    password_confirmation: null as string | null,
});

const submit = () => {
    form.post(route('workspace.supplier.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.password = null;
            form.password_confirmation = null;
            toast({ theme: 'success', text: t('Modifiche salvate') });
        },
    });
};
</script>

<style scoped>
.profile-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    max-width: 720px;
}

.profile-form__actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
}
</style>
