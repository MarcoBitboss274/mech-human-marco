<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useSelect } from '@/composables/useSelect';
import { Building, BuildingForm } from '@/types/Building';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbSelect, BbSwitch, BbTextInput } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    building: Building | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.building?.id);

const { select: selectAgents } = useSelect('agents');

const form = useForm<BuildingForm>({
    name: null,
    vat: null,
    agent_id: null,
    is_studio: null,
    customer_code: null,
    is_laboratory: null,
    headquarter_address: null,
    legal_address: null,
    approved: true,
    fiscal_code: null,
    sdi_code: null,
});

const prefill = () => {
    form.name = props.building?.name ?? null;
    form.vat = props.building?.vat ?? null;
    form.agent_id = props.building?.agent_id ?? null;
    form.is_studio = props.building?.is_studio ?? null;
    form.customer_code = props.building?.customer_code ?? null;
    form.is_laboratory = props.building?.is_laboratory ?? null;
    form.headquarter_address = props.building?.headquarter_address ?? null;
    form.legal_address = props.building?.legal_address ?? null;
    form.approved = props.building?.approved ?? true;
    form.fiscal_code = props.building?.fiscal_code ?? null;
    form.sdi_code = props.building?.sdi_code ?? null;
};

watch(
    () => props.building,
    () => {
        form.reset();
        prefill();
    },
    { immediate: true },
);

const { execute } = useAsyncFn(
    async () => {
        const url = isCreating.value ? route('buildings.store') : route('buildings.update', { building: props.building?.id });
        const method = isCreating.value ? 'post' : 'put';
        await form.submit(method, url, {
            onSuccess: () => {
                form.reset();
                setTimeout(() => {
                    emit('data:updated');
                }, 10);
            },
        });
    },
    { immediate: false },
);
</script>

<template>
    <form class="admin-form" autocomplete="off">
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
            <BbTextInput v-model="form.legal_address" autocomplete="off" :label="t('Indirizzo sede legale')" :errors="form.errors?.legal_address" />

            <fieldset>
                <legend>{{ t('Agente') }}</legend>
                <BbSelect
                    v-model="form.agent_id"
                    item-text="label"
                    item-value="value"
                    :items="selectAgents"
                    :label="t('Agente assegnato')"
                    :errors="form.errors?.agent_id"
                />
            </fieldset>

            <fieldset>
                <legend>{{ t('Stato') }}</legend>
                <BbSwitch v-model="form.approved" :label="t('Approvato')" />
            </fieldset>
        </div>
        <div class="admin-form__actions">
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </div>
    </form>
</template>
