<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div class="">
                <h1 class="page__title">{{ t('Fornitori') }}</h1>
            </div>
            <div v-if="can('suppliers.create')" class="hidden lg:inline-flex">
                <BbButton append:icon="plus" @click="modal = true">{{ t('Aggiungi') }}</BbButton>
            </div>
            <div class="lg:hidden">
                <BbDropdown v-if="can('suppliers.create')" :items="dropdownItems">
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
            </div>
        </div>

        <div class="my-4 flex flex-wrap items-end justify-start gap-3">
            <BbTextInput
                class="w-full sm:w-1/3 lg:w-1/5"
                v-model="filters.query"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra i fornitori')"
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
                    :items="suppliers.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessun fornitore trovato') }}</template>
                    <template #status="{ item }">
                        <SupplierStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton
                                v-if="can('suppliers.edit')"
                                icon="pencil_line"
                                size="xs"
                                @click="
                                    () => {
                                        selectedSupplier = item;
                                        modal = true;
                                    }
                                "
                            >
                                {{ t('Modifica') }}
                            </BbButton>
                            <BbPopover ref="popover" v-if="can('suppliers.destroy')">
                                <template #activator="{ props }">
                                    <BbButton icon="trash" size="xs" v-bind="props">{{ t('Elimina') }}</BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler eliminare') }}
                                        <strong> {{ item.name ?? t('questo fornitore') }}?</strong>
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
                            </BbPopover>
                        </div>
                    </template>
                </BbTable>
            </ul>
        </div>

        <XPagination
            v-model="page"
            :disabled="loading"
            :per-page="suppliers.per_page"
            :total-items="suppliers.total"
            :total-pages="suppliers.last_page"
        />

        <BbDialog
            v-model="modal"
            :title="selectedSupplier?.id ? t('Modifica fornitore') : t('Aggiungi fornitore')"
            size="lg"
            @hidden="
                () => {
                    selectedSupplier = null;
                }
            "
        >
            <Form
                :supplier="selectedSupplier"
                @data:updated="
                    () => {
                        selectedSupplier = null;
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
import SupplierStatusBadge from '@/components/suppliers/SupplierStatusBadge.vue';
import { useIndexPage } from '@/composables/useIndexPage';
import { usePermissions } from '@/composables/usePermissions';
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import Form from '@/pages/suppliers/partials/Form.vue';
import type { Supplier } from '@/types/Supplier';
import { Pagination } from '@/types/Pagination';
import { router } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbDialog, BbDropdown, type BbDropdownItem, BbIcon, BbPopover, BbTable, BbTextInput, useToast } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Fornitori' }, () => [page]),
});

type Props = {
    suppliers: Pagination<Supplier>;
};

const props = defineProps<Props>();

const { toast } = useToast();
const { can } = usePermissions();

const filtersDefault = {
    query: route().params.query ?? null,
};

const { loading, page, execute, filters, resetFilters } = useIndexPage('/suppliers', filtersDefault, props.suppliers);

const tableContext = useTableContext<Supplier['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'name',
        label: t('Nome'),
    },
    {
        key: 'vat',
        label: t('Partita IVA'),
    },
    {
        key: 'mail',
        label: t('Email'),
    },
    {
        key: 'phone',
        label: t('Telefono'),
    },
    {
        key: 'city',
        label: t('Città'),
    },
    {
        key: 'status',
        label: t('Stato'),
    },
]);

const dropdownItems: BbDropdownItem[] = [
    {
        key: 'add-supplier',
        text: t('Aggiungi fornitore'),
        onClick: () => {
            selectedSupplier.value = null;
            modal.value = true;
        },
    },
];

const modal = ref(false);
const selectedSupplier = ref<Supplier | null>(null);

const deleteItem = async (id: Supplier['id']) => {
    await router.delete(route('suppliers.destroy', { supplier: id }), {
        onSuccess: () => {
            page.value = 1;
            toast({
                theme: 'success',
                text: t('Fornitore eliminato con successo'),
            });
        },
    });
};
</script>
