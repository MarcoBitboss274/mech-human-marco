<template>
    <BbOffCanvas
        v-model="modelValue"
        direction="right"
        overlay-classes="create-user-offcanvas"
        :title="props.user ? t('Modifica utente') : t('Aggiungi utente')"
        @hidden="onOffcanvasHidden"
    >
        <form autocomplete="off">
            <div class="form-grid">
                <BbTextInput v-model="form.name" autocomplete="off" :label="t('Nome')" required :errors="form.errors?.name" />
                <BbTextInput v-model="form.surname" autocomplete="off" :label="t('Cognome')" required :errors="form.errors?.surname" />
                <BbTextInput v-model="form.email" autocomplete="off" :label="t('Email')" required type="email" :errors="form.errors?.email" />
                <BbSelect
                    v-model="form.role"
                    item-text="text"
                    item-value="value"
                    :items="[
                        { text: t('Amministratore'), value: 'admin' },
                        { text: t('Utente'), value: 'user' },
                    ]"
                    :label="t('Ruolo')"
                    :errors="form.errors?.role"
                />
                <BbSwitch v-model="form.active" :label="t('Attivo')" />
                <BbCheckbox v-if="isCreating" :label="t('Invia email di benvenuto')" v-model="form.send_invite" />
                <fieldset v-if="!isCreating">
                    <legend>{{ t('Cambio password') }}</legend>
                    <form autocomplete="off">
                        <BbTextInput
                            v-model="form.password"
                            autocomplete="false"
                            :label="t('Password')"
                            required
                            type="password"
                            :errors="form.errors?.password"
                        />
                        <BbTextInput
                            v-model="form.password_confirmation"
                            autocomplete="false"
                            :label="t('Conferma password')"
                            required
                            type="password"
                            :errors="form.errors?.password_confirmation"
                        />
                    </form>
                </fieldset>
            </div>
        </form>
        <template #footer>
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </template>
    </BbOffCanvas>
</template>

<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { User } from '@/types/User';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbOffCanvas, BbSelect, BbSwitch, BbTextInput } from 'bitboss-ui';
import { computed, onUpdated } from 'vue';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const modelValue = defineModel<boolean>('modelValue', {
    required: true,
    default: false,
});

type Props = {
    user: User | null;
};

const props = defineProps<Props>();

const isCreating = computed(() => !props.user?.id);

const emit = defineEmits(['canvas:hide', 'data:updated']);

onUpdated(() => {
    form.reset();
    prefill();
});

const form = useForm({
    name: null,
    surname: null,
    email: null,
    role: null,
    password: null,
    password_confirmation: null,
    active: true,
    send_invite: false,
});

const prefill = () => {
    form.name = props.user?.name ?? null;
    form.surname = props.user?.surname ?? null;
    form.email = props.user?.email ?? null;
    form.role = props.user?.role ?? null;
    form.active = props.user?.active ?? true;
};
const { execute } = useAsyncFn(
    async () => {
        const url = isCreating.value ? route('users.store') : route('users.update', props.user?.id);
        const method = isCreating.value ? 'post' : 'put';
        await form.submit(method, url, {
            onSuccess: () => {
                form.reset();
                modelValue.value = false;
                setTimeout(() => {
                    emit('data:updated');
                }, 10);
            },
        });
    },
    { immediate: false },
);

const onOffcanvasHidden = () => {
    form.reset();
    form.clearErrors();
    emit('canvas:hide');
};
</script>

<style>
@reference '@/../css/base.css';

.create-user-offcanvas {
    .stepper {
        @apply -mx-1 px-1 pb-1;

        .bb-alert {
            @apply sticky bottom-0 mt-6;
        }
    }

    .bb-offcanvas__footer {
        @apply text-right;
    }
}
</style>
