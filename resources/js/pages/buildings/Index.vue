<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div class="">
                <h1 class="page__title">{{ t('Strutture') }}</h1>
            </div>
            <div v-if="can('buildings.create')" class="hidden lg:inline-flex">
                <BbButton append:icon="plus" @click="modal = true">{{ t('Aggiungi') }}</BbButton>
            </div>
            <div class="lg:hidden">
                <BbDropdown v-if="can('buildings.create')" :items="dropdownItems">
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
                v-model="queryFilter"
                append:icon="lens"
                autocomplete="off"
                clearable
                :label="t('Cerca tra le strutture')"
            />
            <BbSelect
                v-model="agentIdFilter"
                class="w-full sm:w-1/3 lg:w-1/4"
                item-text="label"
                item-value="value"
                :items="loadAgentsFilter"
                :label="t('Agente')"
                clearable
            />
            <BbSelect
                v-model="typologyFilterModel"
                class="w-full sm:w-1/3 lg:w-1/4"
                multiple
                item-text="label"
                item-value="value"
                :items="typologyFilterItems"
                :label="t('Tipologia')"
                clearable
            />
            <BbSelect
                v-model="approvedFilter"
                class="w-full sm:w-1/3 lg:w-1/5"
                item-text="label"
                item-value="value"
                :items="approvedFilterItems"
                :label="t('Approvato')"
                clearable
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
                    :items="buildings.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessuna struttura trovata') }}</template>
                    <template #approved="{ item }">
                        <div v-if="item.approved" class="h-4 w-4 rounded-full border border-green-500 bg-green-200"></div>
                        <div v-else class="h-4 w-4 rounded-full border border-red-500 bg-red-200"></div>
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="eye" size="xs" @click="router.get(route('buildings.show', { building: item.id }))">
                                {{ t('Visualizza') }}
                            </BbButton>
                            <BbPopover ref="popover" v-if="can('buildings.destroy')">
                                <template #activator="{ props }">
                                    <BbButton icon="trash" size="xs" v-bind="props">{{ t('Elimina') }}</BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler eliminare') }}
                                        <strong> {{ item.name ?? t('questa struttura') }}?</strong>
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
            :per-page="buildings.per_page"
            :total-items="buildings.total"
            :total-pages="buildings.last_page"
        />

        <BbDialog
            v-model="modal"
            :title="t('Aggiungi struttura')"
            size="lg"
        >
            <Form
                :building="null"
                @data:updated="
                    () => {
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
import { useIndexPage } from '@/composables/useIndexPage';
import { useSelect } from '@/composables/useSelect';
import { usePermissions } from '@/composables/usePermissions';
import { useTableContext } from '@/composables/useTableContext';
import AppLayout from '@/layouts/AppLayout.vue';
import Form from '@/pages/buildings/partials/Form.vue';
import type { Building } from '@/types/Building';
import { Pagination } from '@/types/Pagination';
import { router, usePage } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbDialog, BbDropdown, type BbDropdownItem, BbIcon, BbPopover, BbSelect, BbTable, BbTextInput, useToast } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Strutture' }, () => [page]),
});

type Props = {
    buildings: Pagination<Building>;
};

const props = defineProps<Props>();

const { toast } = useToast();
const { can } = usePermissions();

const parseAgentIdParam = (param: unknown): string | null => {
    if (param == null || param === '') return null;
    const n = typeof param === 'number' ? param : parseInt(String(param), 10);
    return Number.isFinite(n) && n > 0 ? String(n) : null;
};

const parseTypologyParam = (param: unknown): string | null => {
    const allowed = new Set(['studio', 'laboratory']);
    if (param == null || param === '') return null;
    if (Array.isArray(param)) {
        const parts = param.map((x) => String(x).trim()).filter((x) => allowed.has(x));
        const s = [...new Set(parts)].sort().join(',');
        return s || null;
    }
    const parts = String(param)
        .split(',')
        .map((x) => x.trim())
        .filter((x) => allowed.has(x));
    const s = [...new Set(parts)].sort().join(',');
    return s || null;
};

const parseApprovedParam = (param: unknown): '1' | '0' | null => {
    if (param == null || param === '') return null;
    const s = String(param);
    if (s === '1' || s === '0') return s;
    return null;
};

const inertiaPage = usePage();

const queryParam = (key: string): unknown => {
    const idx = inertiaPage.url.indexOf('?');
    if (idx === -1) return undefined;
    const params = new URLSearchParams(inertiaPage.url.slice(idx + 1));
    const all = params.getAll(key);
    if (all.length === 0) return undefined;
    if (all.length === 1) return all[0];
    return all;
};

const filtersDefault = {
    query: (queryParam('query') as string | undefined) ?? null,
    agent_id: parseAgentIdParam(queryParam('agent_id')),
    typology: parseTypologyParam(queryParam('typology')),
    approved: parseApprovedParam(queryParam('approved')),
};

const { loading, page, execute, filters, resetFilters } = useIndexPage('/buildings', filtersDefault, props.buildings);

const queryFilter = computed({
    get: () => (filters.query != null && filters.query !== '' ? String(filters.query) : null),
    set: (v: string | null) => {
        filters.query = v;
    },
});

const { select: selectAgents } = useSelect('agents');

const agentIdFilter = computed({
    get: () => {
        const v = filters.agent_id;
        if (v === null || v === undefined || v === '') return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    },
    set: (v: number | null) => {
        filters.agent_id = v != null && Number.isFinite(v) ? String(v) : null;
    },
});

const loadAgentsFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? agentIdFilter.value) as number | string | string[] | number[] | null | undefined;
    return selectAgents(query || null, prefill, mv ?? null);
};

const typologyFilterModel = computed({
    get: () => (filters.typology ? String(filters.typology).split(',').filter(Boolean) : []),
    set: (v: string[]) => {
        const allowed = v.filter((x) => x === 'studio' || x === 'laboratory');
        filters.typology = allowed.length ? [...new Set(allowed)].sort().join(',') : null;
    },
});

const typologyFilterItems = computed(() => [
    { value: 'studio', label: t('Studio') },
    { value: 'laboratory', label: t('Laboratorio') },
]);

const approvedFilter = computed({
    get: () => {
        const a = filters.approved;
        if (a === null || a === undefined || a === '') return null;
        const s = String(a);
        return s === '1' || s === '0' ? s : null;
    },
    set: (v: string | null) => {
        filters.approved = v;
    },
});

const approvedFilterItems = computed(() => [
    { value: '1', label: t('Sì') },
    { value: '0', label: t('No') },
]);

const tableContext = useTableContext<Building['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'name',
        label: t('Nome'),
    },
    {
        key: 'agent_full_name',
        label: t('Agente'),
        formatter: (d) => d ?? '--',
    },
    {
        key: 'vat',
        label: t('Partita IVA'),
    },
    {
        key: 'is_studio',
        label: t('Studio'),
        formatter: (d) => (d ? t('Sì') : t('No')),
    },
    {
        key: 'is_laboratory',
        label: t('Laboratorio'),
        formatter: (d) => (d ? t('Sì') : t('No')),
    },
    {
        key: 'headquarter_address',
        label: t('Sede operativa'),
    },
    {
        key: 'legal_address',
        label: t('Sede legale'),
    },
    {
        key: 'users_count',
        label: t('Utenti'),
        formatter: (d) => (d != null ? String(d) : '0'),
    },
    {
        key: 'approved',
        label: t('Approvato'),
        formatter: (d) => (d ? t('Sì') : t('No')),
    },
]);

const dropdownItems: BbDropdownItem[] = [
    {
        key: 'add-building',
        text: t('Aggiungi struttura'),
        onClick: () => {
            modal.value = true;
        },
    },
];

const modal = ref(false);

const deleteItem = async (id: Building['id']) => {
    await router.delete(route('buildings.destroy', { building: id }), {
        onSuccess: () => {
            page.value = 1;
            toast({
                theme: 'success',
                text: t('Struttura eliminata con successo'),
            });
        },
    });
};
</script>
