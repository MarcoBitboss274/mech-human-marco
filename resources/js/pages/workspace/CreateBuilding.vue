<script setup lang="ts">
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import { BuildingForm } from '@/types/Building';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbTextInput } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Crea struttura' }, () => [page]),
});

const form = useForm<
    Pick<
        BuildingForm,
        'name' | 'vat' | 'is_studio' | 'customer_code' | 'is_laboratory' | 'headquarter_address' | 'legal_address' | 'fiscal_code' | 'sdi_code'
    >
>({
    name: '',
    vat: '',
    is_studio: null,
    customer_code: null,
    is_laboratory: null,
    headquarter_address: null,
    legal_address: null,
    fiscal_code: null,
    sdi_code: null,
});

const submit = () => {
    form.post(route('workspace.buildings.store'));
};
</script>

<template>
    <div>
        <h1 class="mb-6 text-center text-2xl font-bold">{{ t('Crea struttura') }}</h1>

        <div class="mx-auto max-w-2xl">
            <form class="admin-form" autocomplete="off" @submit.prevent="submit">
                <div class="admin-form__grid">
                    <BbTextInput v-model="form.name" autocomplete="off" :label="t('Nome')" :errors="form.errors?.name" />
                    <BbTextInput v-model="form.vat" autocomplete="off" :label="t('Partita IVA')" :errors="form.errors?.vat" />
                    <BbCheckbox v-model="form.is_studio" :label="t('Studio')" />
                    <BbCheckbox v-model="form.is_laboratory" :label="t('Laboratorio')" />
                    <BbTextInput v-model="form.customer_code" autocomplete="off" :label="t('Codice cliente')" :errors="form.errors?.customer_code" />
                    <BbTextInput v-model="form.fiscal_code" autocomplete="off" :label="t('Codice fiscale')" :errors="form.errors?.fiscal_code" />
                    <BbTextInput v-model="form.sdi_code" autocomplete="off" :label="t('Codice SDI')" :errors="form.errors?.sdi_code" />

                    <BbTextInput
                        v-model="form.headquarter_address"
                        autocomplete="off"
                        :label="t('Indirizzo sede operativa')"
                        :errors="form.errors?.headquarter_address"
                    />
                    <BbTextInput
                        v-model="form.legal_address"
                        autocomplete="off"
                        :label="t('Indirizzo sede legale')"
                        :errors="form.errors?.legal_address"
                    />
                </div>
                <div class="admin-form__actions">
                    <BbButton type="submit" :loading="form.processing">{{ t('Salva') }}</BbButton>
                </div>
            </form>
        </div>
    </div>
</template>
