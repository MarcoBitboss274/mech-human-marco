<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useMainToast } from '@/composables/useMainToast';
import { useWorkspace } from '@/composables/useWorkspace';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbSwitch, BbTextInput } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { success, error } = useMainToast();
const { t } = useI18n();
const { workspace } = useWorkspace();

type Address = {
    id: number;
    street: string | null;
    cap: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    is_default: boolean | null;
};

type Props = {
    address: Address | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.address?.id);

const form = useForm({
    street: null as string | null,
    cap: null as string | null,
    city: null as string | null,
    province: null as string | null,
    country: null as string | null,
    is_default: false as boolean,
});

const prefill = () => {
    form.street = props.address?.street ?? null;
    form.cap = props.address?.cap ?? null;
    form.city = props.address?.city ?? null;
    form.province = props.address?.province ?? null;
    form.country = props.address?.country ?? null;
    form.is_default = props.address?.is_default ?? false;
};

watch(
    () => props.address,
    () => {
        form.reset();
        prefill();
    },
    { immediate: true },
);

const { execute } = useAsyncFn(
    async () => {
        if (!workspace.value) return;

        const url = isCreating.value
            ? route('workspace.building.addresses.store', { building: workspace.value.slug })
            : route('workspace.building.addresses.update', { building: workspace.value.slug, address: props.address?.id });
        const method = isCreating.value ? 'post' : 'put';

        await form.submit(method, url, {
            onSuccess: () => {
                success('Indirizzo salvato con successo');
                form.reset();
                emit('data:updated');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        });
    },
    { immediate: false },
);
</script>

<template>
    <form class="admin-form" autocomplete="off">
        <div class="admin-form__grid">
            <BbTextInput v-model="form.street" autocomplete="off" :label="t('Via')" required :errors="form.errors?.street" />
            <BbTextInput v-model="form.cap" autocomplete="off" :label="t('CAP')" required :errors="form.errors?.cap" />
            <BbTextInput v-model="form.city" autocomplete="off" :label="t('Città')" required :errors="form.errors?.city" />
            <BbTextInput v-model="form.province" autocomplete="off" :label="t('Provincia')" required :errors="form.errors?.province" />
            <BbTextInput v-model="form.country" autocomplete="off" :label="t('Paese')" required :errors="form.errors?.country" />
            <BbSwitch v-model="form.is_default" :label="t('Default')" />
        </div>
        <div class="admin-form__actions">
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </div>
    </form>
</template>
