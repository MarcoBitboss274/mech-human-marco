<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <h1 class="page__title">{{ t('Impostazioni fornitore') }}</h1>
        </div>
        <p class="page__subtitle">{{ t('Modifica i dati anagrafici della tua azienda.') }}</p>

        <div class="suppliers-show__content">
            <form class="admin-form" autocomplete="off" @submit.prevent="submit">
                <div class="admin-form__grid">
                    <BbTextInput v-model="form.name" autocomplete="off" :label="t('Ragione sociale')" :errors="form.errors.name" />
                    <BbTextInput v-model="form.vat" autocomplete="off" :label="t('Partita IVA')" :errors="form.errors.vat" />
                    <BbTextInput v-model="form.mail" autocomplete="off" :label="t('Email')" type="email" required :errors="form.errors.mail" />
                    <BbTextInput v-model="form.phone" autocomplete="off" :label="t('Telefono')" :errors="form.errors.phone" />
                    <BbTextInput v-model="form.address" autocomplete="off" :label="t('Indirizzo')" :errors="form.errors.address" />
                    <BbTextInput v-model="form.cap" autocomplete="off" :label="t('CAP')" :errors="form.errors.cap" />
                    <BbTextInput v-model="form.city" autocomplete="off" :label="t('Città')" :errors="form.errors.city" />
                    <BbTextInput v-model="form.province" autocomplete="off" :label="t('Provincia')" :errors="form.errors.province" />
                </div>
                <div class="admin-form__actions">
                    <BbButton :loading="form.processing" type="submit">{{ t('Salva') }}</BbButton>
                </div>
            </form>
        </div>
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
