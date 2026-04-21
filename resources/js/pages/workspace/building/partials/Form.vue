<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useWorkspace } from '@/composables/useWorkspace';
import { Building } from '@/types/Building';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbTextInput } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { workspace } = useWorkspace();

type Props = {
    building: Building | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.building?.id);

const form = useForm({
    name: null as string | null,
    vat: null as string | null,
    is_studio: null as boolean | null,
    customer_code: null as string | null,
    is_laboratory: null as boolean | null,
    headquarter_address: null as string | null,
    legal_address: null as string | null,
    fiscal_code: null as string | null,
    sdi_code: null as string | null,
});

const prefill = () => {
    form.name = props.building?.name ?? null;
    form.vat = props.building?.vat ?? null;
    form.is_studio = props.building?.is_studio ?? null;
    form.customer_code = props.building?.customer_code ?? null;
    form.is_laboratory = props.building?.is_laboratory ?? null;
    form.headquarter_address = props.building?.headquarter_address ?? null;
    form.legal_address = props.building?.legal_address ?? null;
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
        if (isCreating.value || !workspace.value) return;

        await form.put(route('workspace.building.update', { building: workspace.value.slug }), {
            onSuccess: () => {
                emit('data:updated');
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
        </div>
        <div class="admin-form__actions">
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </div>
    </form>
</template>
