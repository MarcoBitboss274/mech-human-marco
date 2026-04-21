<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useSelect } from '@/composables/useSelect';
import type { Supplier, SupplierForm } from '@/types/Supplier';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbSelect, BbTextInput } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    supplier: Supplier | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.supplier?.id);

const { select: selectStatuses } = useSelect('supplier-statuses');
const loadStatuses = async (search?: string) => selectStatuses(search ?? null, false, null);

const form = useForm<SupplierForm>({
    name: null,
    vat: null,
    mail: null,
    phone: null,
    address: null,
    cap: null,
    city: null,
    province: null,
    status: null,
});

const prefill = () => {
    form.name = props.supplier?.name ?? null;
    form.vat = props.supplier?.vat ?? null;
    form.mail = props.supplier?.mail ?? null;
    form.phone = props.supplier?.phone ?? null;
    form.address = props.supplier?.address ?? null;
    form.cap = props.supplier?.cap ?? null;
    form.city = props.supplier?.city ?? null;
    form.province = props.supplier?.province ?? null;
    form.status = props.supplier?.status ?? null;
};

watch(
    () => props.supplier,
    () => {
        form.reset();
        form.clearErrors();
        prefill();
    },
    { immediate: true },
);

const { execute } = useAsyncFn(
    async () => {
        const url = isCreating.value ? route('suppliers.store') : route('suppliers.update', { supplier: props.supplier?.id });
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
            <BbTextInput v-model="form.mail" autocomplete="off" :label="t('Email')" :errors="form.errors?.mail" />
            <BbTextInput v-model="form.phone" autocomplete="off" :label="t('Telefono')" :errors="form.errors?.phone" />
            <BbTextInput v-model="form.address" autocomplete="off" :label="t('Indirizzo')" :errors="form.errors?.address" />
            <BbTextInput v-model="form.cap" autocomplete="off" :label="t('CAP')" :errors="form.errors?.cap" />
            <BbTextInput v-model="form.city" autocomplete="off" :label="t('Città')" :errors="form.errors?.city" />
            <BbTextInput v-model="form.province" autocomplete="off" :label="t('Provincia')" :errors="form.errors?.province" />
            <BbSelect
                v-model="form.status"
                item-text="label"
                item-value="value"
                :items="loadStatuses"
                :label="t('Stato')"
                :errors="form.errors?.status"
            />
        </div>
        <div class="admin-form__actions">
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </div>
    </form>
</template>
