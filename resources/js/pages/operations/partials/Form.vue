<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useSelect } from '@/composables/useSelect';
import type { Operation, OperationForm } from '@/types/Operation';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbSelect, BbTextInput } from 'bitboss-ui';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    operation: Operation | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.operation?.id);

const { select: selectBuildings } = useSelect('buildings');
const { select: selectStatuses } = useSelect('operation-statuses');

const loadBuildings = async (search?: string) => selectBuildings(search ?? null, false, null);
const loadStatuses = async (search?: string) => selectStatuses(search ?? null, false, null);

const form = useForm<OperationForm>({
    building_id: null,
    typology: null,
    status: null,
});

const prefill = () => {
    form.building_id = props.operation?.building_id ?? null;
    form.typology = props.operation?.typology ?? null;
    form.status = props.operation?.status ?? null;
};

watch(
    () => props.operation,
    () => {
        form.reset();
        form.clearErrors();
        prefill();
    },
    { immediate: true },
);

const { execute } = useAsyncFn(
    async () => {
        const url = isCreating.value ? route('operations.store') : route('operations.update', { operation: props.operation?.id });
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
                v-model="form.building_id"
                item-text="label"
                item-value="value"
                :items="loadBuildings"
                :label="t('Struttura')"
                :errors="form.errors?.building_id"
            />
            <BbTextInput v-model="form.typology" autocomplete="off" :label="t('Tipologia')" :errors="form.errors?.typology" />
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
