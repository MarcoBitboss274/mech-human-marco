<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div class="">
                <h1 class="page__title">{{ t('Prescrizioni') }}</h1>
            </div>
            <!-- <div v-if="can('prescriptions.create')" class="hidden lg:inline-flex">
                <BbButton append:icon="plus" @click="modal = true">{{ t('Aggiungi') }}</BbButton>
            </div>
            <div class="lg:hidden">
                <BbDropdown v-if="can('prescriptions.create')" :items="dropdownItems">
                    <template #activator="{ props }">
                        <BbButton icon="vertical_dots" v-bind="props">{{ t('Menu') }}</BbButton>
                    </template>
                    <template #item="{ text }">
                        <span class="flex gap-x-1">
                            <BbIcon type="plus" />
                            {{ text }}
                        </span>
                    </template>
                </BbDropdown>
            </div> -->
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="filters.query"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra le prescrizioni')"
            />
            <BbButton icon="trash" @click="resetFilters">{{ t('Pulisci') }}</BbButton>
        </div>

        <div class="mb-6">
            <ul class="space-y-4">
                <BbTable
                    v-model="tableContext.selected"
                    v-model:select-all="tableContext.all"
                    v-model:unselected-items="tableContext.unselected"
                    :columns="columns"
                    item-value="id"
                    :items="prescriptions.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessuna prescrizione trovata') }}</template>
                    <template #typology="{ item }">
                        <PrescriptionTypologyBadge :typology="item.typology" size="xs" />
                    </template>
                    <template #status="{ item }">
                        <PrescriptionStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="router.get(route('prescriptions.show', { prescription: item.id }))">
                                {{ t('Visualizza') }}
                            </BbButton>
                            <!-- <BbButton
                                v-if="can('prescriptions.edit')"
                                icon="pencil"
                                size="xs"
                                @click="
                                    () => {
                                        selectedPrescription = item;
                                        modal = true;
                                    }
                                "
                            >
                                {{ t('Modifica') }}
                            </BbButton>
                            <BbPopover ref="popover" v-if="can('prescriptions.destroy')">
                                <template #activator="{ props }">
                                    <BbButton icon="trash" size="xs" v-bind="props">{{ t('Elimina') }}</BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler eliminare questa prescrizione?') }}
                                    </p>

                                    <div class="text-right">
                                        <BbButton
                                            variant="danger"
                                            size="xs"
                                            @click="
                                                () => {
                                                    deleteItem(item.id);
                                                    close();
                                                }
                                            "
                                            >{{ t('Elimina') }}</BbButton
                                        >
                                    </div>
                                </template>
                            </BbPopover> -->
                        </div>
                    </template>
                </BbTable>
            </ul>
        </div>

        <XPagination
            v-model="page"
            :disabled="loading"
            :per-page="prescriptions.per_page"
            :total-items="prescriptions.total"
            :total-pages="prescriptions.last_page"
        />

        <BbDialog
            v-model="modal"
            :title="selectedPrescription?.id ? t('Modifica prescrizione') : t('Aggiungi prescrizione')"
            size="lg"
            @hidden="
                () => {
                    selectedPrescription = null;
                }
            "
        >
            <Form
                :prescription="selectedPrescription"
                @data:updated="
                    () => {
                        selectedPrescription = null;
                        modal = false;
                        execute();
                    }
                "
            />
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import XPagination from '@/components/common/XPagination.vue';
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue';
import PrescriptionTypologyBadge from '@/components/prescriptions/PrescriptionTypologyBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { usePermissions } from '@/composables/usePermissions';
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import Form from '@/pages/prescriptions/partials/Form.vue';
import { Pagination } from '@/types/Pagination';
import type { Prescription } from '@/types/Prescription';
import { router } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbDialog, type BbDropdownItem, BbTable, BbTextInput, useToast } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Prescrizioni' }, () => [page]),
});

type Props = {
    prescriptions: Pagination<Prescription>;
};

const props = defineProps<Props>();

const { toast } = useToast();
const { can } = usePermissions();

const filtersDefault = {
    query: route().params.query ?? null,
};

const { loading, page, execute, filters, resetFilters } = useIndexPage('/prescriptions', filtersDefault, props.prescriptions);

const tableContext = useTableContext<Prescription['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'operation.batch_number',
        label: t('Lotto lavorazione'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'typology',
        label: t('Tipologia'),
    },
    {
        key: 'status',
        label: t('Stato'),
    },
    {
        key: 'ref',
        label: t('Riferimento'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'name',
        label: t('Nome'),
        formatter: (d, i, item) => {
            const parts = [item.name, item.surname].filter(Boolean);
            return parts.length ? parts.join(' ') : '--';
        },
    },
    {
        key: 'building.name',
        label: t('Struttura'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'created_at',
        label: t('Creato il'),
        formatter: (d) => (d ? new Date(d).toLocaleDateString('it-IT') : '--'),
    },
]);

const dropdownItems: BbDropdownItem[] = [
    {
        key: 'add-prescription',
        text: t('Aggiungi prescrizione'),
        onClick: () => {
            selectedPrescription.value = null;
            modal.value = true;
        },
    },
];

const modal = ref(false);
const selectedPrescription = ref<Prescription | null>(null);

const deleteItem = async (id: Prescription['id']) => {
    await router.delete(route('prescriptions.destroy', { prescription: id }), {
        onSuccess: () => {
            page.value = 1;
            toast({
                theme: 'success',
                text: t('Prescrizione eliminata con successo'),
            });
        },
    });
};
</script>
