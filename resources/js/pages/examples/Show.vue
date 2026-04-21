<template>
    <div class="examples-show">
        <div class="examples-show__header">
            <h1 class="page__title">Mario Rossi</h1>
            <BbButton append:icon="eye" @click="editModal = true">{{ t('Modifica') }}</BbButton>
        </div>
        <div class="examples-show__details">
            <div class="examples-show__details-item">
                <BbIcon type="user" size="sm" />
                <p>{{ t('Nome') }}: <span class="font-bold">Mario Rossi</span></p>
            </div>
            <div class="examples-show__details-item">
                <BbIcon type="box" size="sm" />
                <p>{{ t('Nome') }}: <span class="font-bold">Mario Rossi</span></p>
            </div>
            <div class="examples-show__details-item">
                <BbIcon type="user" size="sm" />
                <p>{{ t('Nome') }}: <span class="font-bold">Mario Rossi</span></p>
            </div>
        </div>

        <div class="examples-show__filters">
            <BbTextInput class="w-full sm:w-1/3 lg:w-1/5" v-model="search" append:icon="lens" autocomplete="off" clearable :label="t('Cerca')" />
        </div>

        <div class="examples-show__content">
            <BbTab v-model="tab" :items="tabs">
                <template #users>
                    <BbTable
                        v-model="tableContext1.selected"
                        v-model:select-all="tableContext1.all"
                        v-model:unselected-items="tableContext1.unselected"
                        :columns="columns1"
                        item-value="id"
                        :items="data1"
                        actions
                    >
                        <template #no-data>{{ t('Nessun utente trovato') }}</template>
                        <template #actions="{ item }">
                            <div class="flex gap-x-2">
                                <BbButton icon="pencil" size="xs" @click="editModal = true">{{ t('Modifica') }}</BbButton>
                            </div>
                        </template>
                    </BbTable>
                </template>
                <template #linked>
                    <BbTable
                        v-model="tableContext2.selected"
                        v-model:select-all="tableContext2.all"
                        v-model:unselected-items="tableContext2.unselected"
                        :columns="columns2"
                        item-value="id"
                        :items="data2"
                        actions
                    >
                        <template #no-data>{{ t('Nessun utente trovato') }}</template>
                        <template #actions="{ item }">
                            <div class="flex gap-x-2">
                                <BbButton icon="pencil" size="xs" @click="editModal = true">{{ t('Modifica') }}</BbButton>
                            </div>
                        </template>
                    </BbTable>
                </template>
            </BbTab>
        </div>

        <BbDialog :title="t('Modifica utente')" v-model="editModal" size="md">
            <form class="flex flex-col gap-y-4">
                <BbTextInput :label="t('Nome')" v-model="form.name" />
                <BbTextInput :label="t('Email')" v-model="form.email" />
                <BbTextInput :label="t('Telefono')" v-model="form.phone" />
                <BbTextInput :label="t('Indirizzo')" v-model="form.address" />
                <BbButton type="submit">{{ t('Salva') }}</BbButton>
            </form>
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbIcon, BbTab, BbTable, BbTableColumn, BbTextInput, type BbTabItem } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Esempio show' }, () => [page]),
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
});

const editModal = ref<boolean>(false);

const search = ref<string>('');
const tab = ref<string>('users');
const tabs = ref<BbTabItem[]>([
    {
        key: 'users',
        label: t('Utenti'),
    },
    {
        key: 'linked',
        label: t('Utenti collegati'),
    },
]);

const tableContext1 = useTableContext<number>();
const columns1 = ref<BbTableColumn[]>([
    {
        key: 'name',
        label: t('Nome'),
    },
    {
        key: 'email',
        label: t('Email'),
    },
    {
        key: 'phone',
        label: t('Telefono'),
    },
    {
        key: 'address',
        label: t('Indirizzo'),
    },
]);

const tableContext2 = useTableContext<number>();
const columns2 = ref<BbTableColumn[]>([
    {
        key: 'name',
        label: t('Nome'),
    },
    {
        key: 'email',
        label: t('Email'),
    },
    {
        key: 'phone',
        label: t('Telefono'),
    },
    {
        key: 'address',
        label: t('Indirizzo'),
    },
]);

const data1 = [
    {
        id: 1,
        name: 'Mario Rossi',
        email: 'mario.rossi@example.com',
        phone: '333 1234567',
        address: 'Via Roma, 123',
        city: 'Roma',
        country: 'Italia',
    },
    {
        id: 2,
        name: 'Luigi Bianchi',
        email: 'luigi.bianchi@example.com',
        phone: '333 1234567',
        address: 'Via Roma, 123',
        city: 'Roma',
        country: 'Italia',
    },
    {
        id: 3,
        name: 'Giovanna Verdi',
        email: 'giovanna.verdi@example.com',
        phone: '333 1234567',
        address: 'Via Roma, 123',
        city: 'Roma',
        country: 'Italia',
    },
];

const data2 = [
    {
        id: 1,
        name: 'Mario Rossi',
        email: 'mario.rossi@example.com',
        phone: '333 1234567',
        address: 'Via Roma, 123',
        city: 'Roma',
        country: 'Italia',
    },
    {
        id: 2,
        name: 'Luigi Bianchi',
        email: 'luigi.bianchi@example.com',
        phone: '333 1234567',
        address: 'Via Roma, 123',
        city: 'Roma',
        country: 'Italia',
    },
    {
        id: 3,
        name: 'Giovanna Verdi',
        email: 'giovanna.verdi@example.com',
        phone: '333 1234567',
        address: 'Via Roma, 123',
        city: 'Roma',
        country: 'Italia',
    },
];
</script>

<style>
@reference '@/../css/base.css';

.examples-show {
    @apply flex flex-col gap-y-4;

    .examples-show__header {
        @apply flex items-center justify-between;
    }

    .examples-show__filters {
        @apply flex justify-start gap-2;
    }

    .examples-show__details {
        @apply flex justify-start gap-6;
    }

    .examples-show__details-item {
        @apply flex items-center gap-x-2;
    }
}
</style>
