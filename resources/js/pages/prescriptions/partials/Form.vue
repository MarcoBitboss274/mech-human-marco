<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useSelect } from '@/composables/useSelect';
import type { Prescription, PrescriptionForm } from '@/types/Prescription';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbSelect, BbTextInput } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    prescription: Prescription | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.prescription?.id);

const { select: selectBuildings } = useSelect('buildings');
const { select: selectOperations } = useSelect('operations');
const { select: selectUsers } = useSelect('users');
const { select: selectTypologies } = useSelect('prescription-typologies');
const { select: selectStatuses } = useSelect('prescription-statuses');
const { select: selectGenders } = useSelect('prescription-genders');

const loadBuildings = async (search?: string) => selectBuildings(search ?? null, false, null);
const loadOperations = async (search?: string) => selectOperations(search ?? null, false, null);
const loadUsers = async (search?: string) => selectUsers(search ?? null, false, null);
const loadTypologies = async () => selectTypologies(null, false, null);
const loadStatuses = async () => selectStatuses(null, false, null);
const loadGenders = async () => selectGenders(null, false, null);

const form = useForm<PrescriptionForm>({
    operation_id: null,
    building_id: null,
    user_id: null,
    status: null,
    typology: null,
    ref: null,
    name: null,
    surname: null,
    age: null,
    gender: null,
});

const prefill = () => {
    form.operation_id = props.prescription?.operation_id ?? null;
    form.building_id = props.prescription?.building_id ?? null;
    form.user_id = props.prescription?.user_id ?? null;
    form.status = props.prescription?.status ?? null;
    form.typology = props.prescription?.typology ?? null;
    form.ref = props.prescription?.ref ?? null;
    form.name = props.prescription?.name ?? null;
    form.surname = props.prescription?.surname ?? null;
    form.age = props.prescription?.age ?? null;
    form.gender = props.prescription?.gender ?? null;
};

watch(
    () => props.prescription,
    () => {
        form.reset();
        form.clearErrors();
        prefill();
    },
    { immediate: true },
);

const { execute } = useAsyncFn(
    async () => {
        const url = isCreating.value
            ? route('prescriptions.store')
            : route('prescriptions.update', { prescription: props.prescription?.id });
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
            <BbSelect
                v-model="form.operation_id"
                item-text="label"
                item-value="value"
                :items="loadOperations"
                :label="t('Lavorazione')"
                :errors="form.errors?.operation_id"
            />
            <BbSelect
                v-model="form.building_id"
                item-text="label"
                item-value="value"
                :items="loadBuildings"
                :label="t('Struttura')"
                :errors="form.errors?.building_id"
            />
            <BbSelect
                v-model="form.user_id"
                item-text="label"
                item-value="value"
                :items="loadUsers"
                :label="t('Utente')"
                :errors="form.errors?.user_id"
            />
            <BbSelect
                v-model="form.typology"
                item-text="label"
                item-value="value"
                :items="loadTypologies"
                :label="t('Tipologia')"
                :errors="form.errors?.typology"
            />
            <BbSelect
                v-model="form.status"
                item-text="label"
                item-value="value"
                :items="loadStatuses"
                :label="t('Stato')"
                :errors="form.errors?.status"
            />
            <BbTextInput
                v-model="form.ref"
                autocomplete="off"
                :label="t('Riferimento')"
                :errors="form.errors?.ref"
            />
            <BbTextInput
                v-model="form.name"
                autocomplete="off"
                :label="t('Nome')"
                :errors="form.errors?.name"
            />
            <BbTextInput
                v-model="form.surname"
                autocomplete="off"
                :label="t('Cognome')"
                :errors="form.errors?.surname"
            />
            <BbTextInput
                v-model="form.age"
                type="number"
                autocomplete="off"
                :label="t('Età')"
                :errors="form.errors?.age"
            />
            <BbSelect
                v-model="form.gender"
                item-text="label"
                item-value="value"
                :items="loadGenders"
                :label="t('Genere')"
                :errors="form.errors?.gender"
            />
        </div>
        <div class="admin-form__actions">
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </div>
    </form>
</template>
