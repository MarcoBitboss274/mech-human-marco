<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div class="">
                <h1 class="page__title">{{ t('Utenti') }}</h1>
            </div>
            <div class="hidden lg:inline-flex">
                <BbButton append:icon="plus" @click="modal = true">{{ t('Aggiungi') }}</BbButton>
            </div>
            <div class="lg:hidden">
                <BbDropdown :items="dropdownItems">
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
                :label="t('Cerca tra gli utenti')"
            />
            <BbSelect
                v-model="roleFilter"
                class="w-full sm:w-1/3 lg:w-1/4"
                item-text="label"
                item-value="value"
                :items="loadRolesFilter"
                :label="t('Ruolo')"
                clearable
            />
            <BbSelect
                v-model="buildingIdFilter"
                class="w-full sm:w-1/3 lg:w-1/4"
                item-text="label"
                item-value="value"
                :items="loadBuildingsFilter"
                :label="t('Strutture')"
                clearable
            />
            <BbSelect
                v-model="activeFilter"
                class="w-full sm:w-1/3 lg:w-1/5"
                item-text="label"
                item-value="value"
                :items="activeFilterItems"
                :label="t('Attivo')"
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
                    :items="users.data ?? []"
                    :loading="loading"
                    actions
                >
                    <template #no-data>{{ t('Nessun utente trovato') }}</template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton
                                v-if="can('users.edit')"
                                icon="pencil_line"
                                size="xs"
                                @click="
                                    () => {
                                        selectedUser = item;
                                        modal = true;
                                    }
                                "
                            >
                                {{ t('Modifica') }}
                            </BbButton>
                            <BbButton
                                v-if="item.impersonable && can('users.impersonate')"
                                icon="users"
                                size="xs"
                                @click="
                                    () => {
                                        router.get(route('impersonate', { id: item.id }));
                                    }
                                "
                            >
                                {{ t('Impersona') }}
                            </BbButton>
                            <BbPopover ref="popover" v-if="can('users.destroy')">
                                <template #activator="{ props }">
                                    <BbButton icon="trash" size="xs" v-bind="props">{{ t('Elimina') }}</BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler eliminare') }}
                                        <strong> {{ item.full_name }}?</strong>
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
                            <BbPopover v-if="item.invited_at === null && can('users.invite')" ref="popoverInvite">
                                <template #activator="{ props }">
                                    <BbButton icon="emails" size="xs" v-bind="props">{{ t('Invita') }}</BbButton>
                                </template>
                                <template #default="{ close }">
                                    <p class="mb-2 max-w-[250px]">
                                        {{ t('Sei sicuro di voler invitare') }}
                                        <strong> {{ item.full_name }}?</strong>
                                    </p>

                                    <div class="text-right">
                                        <BbButton
                                            variant="primary"
                                            size="xs"
                                            @click="
                                                () => {
                                                    invite(item.id);
                                                    close();
                                                }
                                            "
                                            >{{ t('Invita') }}</BbButton
                                        >
                                    </div>
                                </template>
                            </BbPopover>
                        </div>
                    </template>
                </BbTable>
            </ul>
        </div>

        <XPagination v-model="page" :disabled="loading" :per-page="users.per_page" :total-items="users.total" :total-pages="users.last_page" />

        <BbDialog
            v-model="modal"
            :title="selectedUser?.id ? t('Modifica utente') : t('Aggiungi utente')"
            size="lg"
            @hidden="
                () => {
                    selectedUser = null;
                }
            "
        >
            <Form
                :user="selectedUser"
                @data:updated="
                    () => {
                        selectedUser = null;
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
import Form from '@/pages/users/partials/Form.vue';
import { Pagination } from '@/types/Pagination';
import type { User } from '@/types/User';
import { dateTime } from '@/utils/formatters/date';
import { router, usePage } from '@inertiajs/vue3';
import {
    BbButton,
    BbDialog,
    BbDropdown,
    type BbDropdownItem,
    BbIcon,
    BbPopover,
    BbSelect,
    BbTable,
    type BbTableColumn,
    BbTextInput,
    useToast,
} from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Utenti' }, () => [page]),
});

type Props = {
    users: Pagination<User>;
};

const props = defineProps<Props>();

const { toast } = useToast();
const { can } = usePermissions();

const parseRoleParam = (param: unknown): string | null => {
    if (param == null || param === '') return null;
    if (Array.isArray(param)) {
        const first = param.find((x) => x != null && String(x).trim() !== '');
        return first != null ? String(first).trim() : null;
    }
    const s = String(param).trim();
    return s || null;
};

const parseBuildingIdParam = (param: unknown): string | null => {
    if (param == null || param === '') return null;
    const n = typeof param === 'number' ? param : parseInt(String(param), 10);
    return Number.isFinite(n) && n > 0 ? String(n) : null;
};

const parseActiveParam = (param: unknown): '1' | '0' | null => {
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
    role: parseRoleParam(queryParam('role')),
    building_id: parseBuildingIdParam(queryParam('building_id')),
    active: parseActiveParam(queryParam('active')),
};

const { loading, page, execute, filters, resetFilters } = useIndexPage('/users', filtersDefault, props.users);

const queryFilter = computed({
    get: () => (filters.query != null && filters.query !== '' ? String(filters.query) : null),
    set: (v: string | null) => {
        filters.query = v;
    },
});

const { select: selectRoles } = useSelect('roles');
const { select: selectBuildings } = useSelect('buildings');

const roleFilter = computed({
    get: () => (filters.role != null && filters.role !== '' ? String(filters.role) : null),
    set: (v: string | null) => {
        filters.role = v;
    },
});

const loadRolesFilter = (query: string, prefill: boolean, modelValue: unknown) =>
    selectRoles(query || null, prefill, modelValue as string | null);

const buildingIdFilter = computed({
    get: () => {
        const v = filters.building_id;
        if (v === null || v === undefined || v === '') return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    },
    set: (v: number | null) => {
        filters.building_id = v != null && Number.isFinite(v) ? String(v) : null;
    },
});

const loadBuildingsFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? buildingIdFilter.value) as number | string | string[] | number[] | null | undefined;
    return selectBuildings(query || null, prefill, mv ?? null);
};

const activeFilter = computed({
    get: () => {
        const a = filters.active;
        if (a === null || a === undefined || a === '') return null;
        const s = String(a);
        return s === '1' || s === '0' ? s : null;
    },
    set: (v: string | null) => {
        filters.active = v;
    },
});

const activeFilterItems = computed(() => [
    { value: '1', label: t('Sì') },
    { value: '0', label: t('No') },
]);

const tableContext = useTableContext<User['id']>();
const columns = ref<BbTableColumn[]>([
    {
        key: 'name',
        label: t('Nome'),
    },
    {
        key: 'surname',
        label: t('Cognome'),
    },
    {
        key: 'email',
        label: t('Email'),
    },
    {
        key: 'role',
        label: t('Ruolo'),
        formatter: (d) => {
            if (d === 'admin') return t('Admin');
            if (d === 'agent') return t('Agente');
            if (d === 'customer') return t('Cliente');
            if (d === 'supplier') return t('Fornitore');
            return '--';
        },
    },
    {
        key: 'active',
        label: t('Attivo'),
        formatter: (d) => (d ? t('Sì') : t('No')),
    },
    {
        key: 'email_verified_at',
        label: t('Email verificata'),
        formatter: (d) => {
            return d ? dateTime(d) : '--';
        },
    },
    {
        key: 'last_login_at',
        label: t('Ultimo accesso'),
        formatter: (d) => {
            return d ? dateTime(d) : '--';
        },
    },
    {
        key: 'invited_at',
        label: t('Invitato il'),
        formatter: (d) => {
            return d ? dateTime(d) : '--';
        },
    },
]);

const dropdownItems: BbDropdownItem[] = [
    {
        key: 'add-user',
        text: t('Aggiungi utente'),
        onClick: () => {
            selectedUser.value = null;
            modal.value = true;
        },
    },
];

const modal = ref(false);
const selectedUser = ref<User | null>(null);

const deleteItem = async (id: User['id']) => {
    await router.delete(route('users.destroy', { id }), {
        onSuccess: () => {
            page.value = 1;
            toast({
                theme: 'success',
                text: t('Utente eliminato con successo'),
            });
        },
    });
};

const invite = async (id: User['id']) => {
    await router.post(
        route('users.invite', { id }),
        {},
        {
            onSuccess: () => {
                toast({
                    theme: 'success',
                    text: t('Utente invitato con successo'),
                });
            },
        },
    );
};
</script>
