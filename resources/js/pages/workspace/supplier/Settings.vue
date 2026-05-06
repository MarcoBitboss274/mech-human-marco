<template>
    <div>
        <h1 class="page__title">{{ t('Impostazioni fornitore') }}</h1>
        <p class="page__subtitle">{{ t('Modifica i dati anagrafici della tua azienda.') }}</p>

        <form class="settings-form" @submit.prevent="submit">
            <BbTextInput v-model="form.name" :label="t('Ragione sociale')" :errors="form.errors.name" />
            <BbTextInput v-model="form.vat" :label="t('Partita IVA')" :errors="form.errors.vat" />
            <BbTextInput v-model="form.mail" :label="t('Email')" type="email" required :errors="form.errors.mail" />
            <BbTextInput v-model="form.phone" :label="t('Telefono')" :errors="form.errors.phone" />
            <BbTextInput v-model="form.address" :label="t('Indirizzo')" :errors="form.errors.address" />
            <BbTextInput v-model="form.cap" :label="t('CAP')" :errors="form.errors.cap" />
            <BbTextInput v-model="form.city" :label="t('Città')" :errors="form.errors.city" />
            <BbTextInput v-model="form.province" :label="t('Provincia')" :errors="form.errors.province" />

            <div class="settings-form__actions">
                <BbButton :loading="form.processing" type="submit">{{ t('Salva') }}</BbButton>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbTextInput, useToast } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Impostazioni fornitore' }, () => [page]),
});

type SupplierFull = {
    id: number;
    name: string | null;
    vat: string | null;
    mail: string | null;
    phone: string | null;
    address: string | null;
    cap: string | null;
    city: string | null;
    province: string | null;
    status: string | null;
};

const props = defineProps<{
    supplier: SupplierFull;
}>();

const form = useForm({
    name: props.supplier.name,
    vat: props.supplier.vat,
    mail: props.supplier.mail,
    phone: props.supplier.phone,
    address: props.supplier.address,
    cap: props.supplier.cap,
    city: props.supplier.city,
    province: props.supplier.province,
    status: props.supplier.status,
});

const submit = () => {
    form.put(route('workspace.supplier.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast({ theme: 'success', text: t('Modifiche salvate') });
        },
    });
};
</script>

<style scoped>
.settings-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    max-width: 960px;
}

.settings-form__actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
}
</style>
