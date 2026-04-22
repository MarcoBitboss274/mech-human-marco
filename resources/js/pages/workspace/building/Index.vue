<template>
    <div class="workspace-view">
        <div class="workspace-view__header">
            <h1 class="workspace-view__title">{{ building.name ?? t('Struttura') }}</h1>
        </div>

        <div class="buildings-show__content">
            <BbTab v-model="tab" :items="tabs">
                <template #details>
                    <div class="buildings-show__details">
                        <div class="buildings-show__details-header">
                            <BbButton v-if="canInWorkspace('workspace.building.update')" @click="openBuildingModal">
                                {{ t('Modifica') }}
                            </BbButton>
                        </div>
                        <div class="buildings-show__details-grid">
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Nome') }}</span>
                                <span class="buildings-show__value">{{ building.name ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Partita IVA') }}</span>
                                <span class="buildings-show__value">{{ building.vat ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Studio') }}</span>
                                <span class="buildings-show__value">{{ building.is_studio ? t('Sì') : t('No') }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Codice cliente') }}</span>
                                <span class="buildings-show__value">{{ building.customer_code ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Codice fiscale') }}</span>
                                <span class="buildings-show__value">{{ building.fiscal_code ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Codice SDI') }}</span>
                                <span class="buildings-show__value">{{ building.sdi_code ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Laboratorio') }}</span>
                                <span class="buildings-show__value">{{ building.is_laboratory ? t('Sì') : t('No') }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Sede operativa') }}</span>
                                <span class="buildings-show__value">{{ building.headquarter_address ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Sede legale') }}</span>
                                <span class="buildings-show__value">{{ building.legal_address ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Approvato') }}</span>
                                <span class="buildings-show__value">{{ building.approved ? t('Sì') : t('No') }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Numero utenti') }}</span>
                                <span class="buildings-show__value">{{ building.users_count ?? 0 }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Creato il') }}</span>
                                <span class="buildings-show__value">{{ dateTime(building.created_at) ?? '--' }}</span>
                            </div>
                            <div class="buildings-show__details-item">
                                <span class="buildings-show__label">{{ t('Aggiornato il') }}</span>
                                <span class="buildings-show__value">{{ dateTime(building.updated_at) ?? '--' }}</span>
                            </div>
                        </div>
                    </div>
                </template>
                <template #users>
                    <div class="buildings-show__users">
                        <div class="buildings-show__users-header">
                            <BbButton v-if="canInWorkspace('workspace.building.members.invite')" append:icon="plus" @click="openInviteModal">
                                {{ t('Invita membro') }}
                            </BbButton>
                        </div>
                    </div>
                    <BbTable :columns="userColumns" item-value="id" :items="users" actions>
                        <template #no-data>{{ t('Nessun utente associato a questa struttura') }}</template>
                        <template #building_role="{ item }">
                            <BuildingUserRoleBadge :role="item.building_role" />
                        </template>
                        <template #created_at="{ item }">
                            {{ dateTime(item.created_at) ?? '--' }}
                        </template>
                        <template #accepted_at="{ item }">
                            {{ dateTime(item.accepted_at) ?? '--' }}
                        </template>
                        <template #actions="{ item }">
                            <div class="flex gap-x-2">
                                <BbButton
                                    v-if="item.id !== currentUser.id && canInWorkspace('workspace.building.members.edit')"
                                    icon="pencil_line"
                                    size="xs"
                                    @click="openEditMemberModal(item)"
                                >
                                    {{ t('Modifica') }}
                                </BbButton>
                                <BbPopover v-if="item.id !== currentUser.id && canInWorkspace('workspace.building.members.remove')">
                                    <template #activator="{ props }">
                                        <BbButton icon="trash" size="xs" v-bind="props">
                                            {{ t('Elimina') }}
                                        </BbButton>
                                    </template>
                                    <template #default="{ close }">
                                        <p class="mb-2 max-w-[250px]">
                                            {{ t('Sei sicuro di voler rimuovere') }}
                                            <strong> {{ getFullName(item) }}</strong
                                            >?
                                        </p>
                                        <div class="text-right">
                                            <BbButton
                                                variant="danger"
                                                size="xs"
                                                @click="
                                                    () => {
                                                        removeMember(item.id);
                                                        close();
                                                    }
                                                "
                                            >
                                                {{ t('Elimina') }}
                                            </BbButton>
                                        </div>
                                    </template>
                                </BbPopover>
                            </div>
                        </template>
                    </BbTable>
                </template>
                <template #addresses>
                    <div class="buildings-show__addresses">
                        <div class="buildings-show__addresses-header">
                            <BbButton v-if="canInWorkspace('workspace.building.addresses.manage')" append:icon="plus" @click="openAddressModal(null)">
                                {{ t('Aggiungi indirizzo') }}
                            </BbButton>
                        </div>
                        <BbTable :columns="addressColumns" item-value="id" :items="addresses" actions>
                            <template #no-data>{{ t('Nessun indirizzo di spedizione') }}</template>
                            <template #is_default="{ item }">
                                {{ item.is_default ? t('Sì') : t('No') }}
                            </template>
                            <template #actions="{ item }">
                                <div class="flex gap-x-2">
                                    <BbButton
                                        v-if="canInWorkspace('workspace.building.addresses.manage')"
                                        icon="pencil_line"
                                        size="xs"
                                        @click="openAddressModal(item)"
                                    >
                                        {{ t('Modifica') }}
                                    </BbButton>
                                    <BbButton
                                        v-if="!item.is_default && canInWorkspace('workspace.building.addresses.manage')"
                                        icon="check-circle"
                                        size="xs"
                                        @click="setDefaultAddress(item.id)"
                                    >
                                        {{ t('Imposta come default') }}
                                    </BbButton>
                                    <BbPopover v-if="canInWorkspace('workspace.building.addresses.manage')">
                                        <template #activator="{ props }">
                                            <BbButton icon="trash" size="xs" v-bind="props">
                                                {{ t('Elimina') }}
                                            </BbButton>
                                        </template>
                                        <template #default="{ close }">
                                            <p class="mb-2 max-w-[250px]">
                                                {{ t('Sei sicuro di voler eliminare questo indirizzo?') }}
                                            </p>
                                            <div class="text-right">
                                                <BbButton
                                                    variant="danger"
                                                    size="xs"
                                                    @click="
                                                        () => {
                                                            deleteAddress(item.id);
                                                            close();
                                                        }
                                                    "
                                                >
                                                    {{ t('Elimina') }}
                                                </BbButton>
                                            </div>
                                        </template>
                                    </BbPopover>
                                </div>
                            </template>
                        </BbTable>
                    </div>
                </template>
            </BbTab>
        </div>

        <BbDialog v-model="buildingModal" :title="t('Modifica struttura')" size="lg" @hidden="selectedBuilding = null">
            <Form
                :building="selectedBuilding"
                @data:updated="
                    () => {
                        selectedBuilding = null;
                        buildingModal = false;
                        router.reload({ only: ['building'] });
                    }
                "
            />
        </BbDialog>

        <BbDialog
            v-model="addressModal"
            :title="selectedAddress?.id ? t('Modifica indirizzo') : t('Aggiungi indirizzo')"
            size="lg"
            @hidden="selectedAddress = null"
        >
            <AddressForm
                :address="selectedAddress"
                @data:updated="
                    () => {
                        selectedAddress = null;
                        addressModal = false;
                        router.reload({ only: ['addresses'] });
                    }
                "
            />
        </BbDialog>

        <BbDialog v-model="inviteMemberModal" :title="t('Invita membro')" size="md">
            <form class="flex flex-col gap-4" @submit.prevent="submitInviteMember">
                <BbTextInput v-model="inviteMemberForm.email" type="email" :label="t('Email')" required :errors="inviteMemberForm.errors?.email" />
                <BbSelect
                    v-model="inviteMemberForm.role"
                    item-text="label"
                    item-value="value"
                    :items="BUILDING_ROLES"
                    :label="t('Ruolo')"
                    :errors="inviteMemberForm.errors?.role"
                />
                <BbButton type="submit" :disabled="inviteMemberForm.processing">
                    {{ t('Invita') }}
                </BbButton>
            </form>
        </BbDialog>

        <BbDialog v-model="editMemberModal" :title="t('Modifica ruolo membro')" size="md">
            <form class="flex flex-col gap-4" @submit.prevent="submitEditMemberRole">
                <BbSelect
                    v-model="editMemberForm.role"
                    item-text="label"
                    item-value="value"
                    :items="BUILDING_ROLES"
                    :label="t('Ruolo')"
                    :errors="editMemberForm.errors?.role"
                />
                <BbButton type="submit" :disabled="editMemberForm.processing || selectedMemberId === null">
                    {{ t('Salva') }}
                </BbButton>
            </form>
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import BuildingUserRoleBadge from '@/components/buildings/BuildingUserRoleBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import { useWorkspace } from '@/composables/useWorkspace';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import AddressForm from '@/pages/workspace/building/partials/AddressForm.vue';
import Form from '@/pages/workspace/building/partials/Form.vue';
import { Building } from '@/types/Building';
import { dateTime } from '@/utils/formatters/date';
import { router, useForm } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbDialog, BbPopover, BbSelect, BbTab, BbTable, BbTextInput, type BbTabItem } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { success, error } = useMainToast();
const { currentUser, canInWorkspace } = usePermissions();
const { workspace } = useWorkspace();

const BUILDING_ROLES = computed(() => [
    { value: 'admin', label: t('Admin') },
    { value: 'member', label: t('Membro') },
]);

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Struttura' }, () => [page]),
});

type BuildingShowUser = {
    id: number;
    name: string;
    surname: string;
    email: string;
    building_role: string | null;
    created_at: string | null;
    accepted_at: string | null;
};

type BuildingShowData = {
    id: number;
    name: string | null;
    vat: string | null;
    is_studio: boolean | null;
    customer_code: string | null;
    is_laboratory: boolean | null;
    headquarter_address: string | null;
    legal_address: string | null;
    approved: boolean;
    fiscal_code: string | null;
    sdi_code: string | null;
    users_count: number;
    created_at: string | null;
    updated_at: string | null;
};

type BuildingShowAddress = {
    id: number;
    building_id: number | null;
    street: string | null;
    cap: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    is_default: boolean | null;
};

type Props = {
    building: BuildingShowData;
    users: BuildingShowUser[];
    addresses: BuildingShowAddress[];
};

const props = defineProps<Props>();

const tab = ref<string>('details');
const tabs = ref<BbTabItem[]>([
    { key: 'details', label: t('Dettagli') },
    { key: 'users', label: t('Membri') },
    { key: 'addresses', label: t('Indirizzi di spedizione') },
]);

const building = props.building;
const users = computed(() => props.users);
const addresses = computed(() => props.addresses);

const buildingModal = ref(false);
const selectedBuilding = ref<Building | null>(null);
const addressModal = ref(false);
const selectedAddress = ref<BuildingShowAddress | null>(null);
const inviteMemberModal = ref(false);
const editMemberModal = ref(false);
const selectedMemberId = ref<number | null>(null);

const inviteMemberForm = useForm({
    email: '',
    role: 'member' as 'admin' | 'member',
});

const editMemberForm = useForm({
    role: 'member' as 'admin' | 'member',
});

const addressColumns = ref<BbTableColumn[]>([
    { key: 'street', label: t('Via') },
    { key: 'cap', label: t('CAP') },
    { key: 'city', label: t('Città') },
    { key: 'province', label: t('Provincia') },
    { key: 'country', label: t('Paese') },
    { key: 'is_default', label: t('Default') },
]);

const userColumns = ref<BbTableColumn[]>([
    { key: 'name', label: t('Nome') },
    { key: 'surname', label: t('Cognome') },
    { key: 'email', label: t('Email') },
    { key: 'building_role', label: t('Ruolo') },
    { key: 'created_at', label: t('Data creazione') },
    { key: 'accepted_at', label: t('Accettato il') },
]);

const openAddressModal = (address: BuildingShowAddress | null) => {
    selectedAddress.value = address;
    addressModal.value = true;
};

const openBuildingModal = () => {
    selectedBuilding.value = building as Building;
    buildingModal.value = true;
};

const setDefaultAddress = (id: number) => {
    if (!workspace.value) return;

    router.post(
        route('workspace.building.addresses.set-default', {
            building: workspace.value.slug,
            address: id,
        }),
        {},
        {
            onSuccess: () => {
                success('Indirizzo impostato come default');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const deleteAddress = (id: number) => {
    if (!workspace.value) return;

    router.delete(
        route('workspace.building.addresses.destroy', {
            building: workspace.value.slug,
            address: id,
        }),
        {
            onSuccess: () => {
                success('Indirizzo eliminato con successo');
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const getFullName = (item: BuildingShowUser) => `${item.name} ${item.surname}`.trim();

const openInviteModal = () => {
    inviteMemberForm.reset();
    inviteMemberModal.value = true;
};

const submitInviteMember = () => {
    if (!workspace.value) return;

    inviteMemberForm.post(route('workspace.building.members.invite', { building: workspace.value.slug }), {
        onSuccess: () => {
            inviteMemberModal.value = false;
            success('Invito inviato con successo');
            router.reload({ only: ['users', 'building'] });
        },
        onError: () => {
            error('Si è verificato un errore');
        },
    });
};

const openEditMemberModal = (member: BuildingShowUser) => {
    selectedMemberId.value = member.id;
    editMemberForm.reset();
    editMemberForm.role = member.building_role === 'admin' ? 'admin' : 'member';
    editMemberModal.value = true;
};

const submitEditMemberRole = () => {
    if (selectedMemberId.value === null || !workspace.value) return;

    editMemberForm.put(
        route('workspace.building.members.edit', {
            building: workspace.value.slug,
            user: selectedMemberId.value,
        }),
        {
            onSuccess: () => {
                editMemberModal.value = false;
                selectedMemberId.value = null;
                success('Ruolo membro aggiornato con successo');
                router.reload({ only: ['users'] });
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};

const removeMember = (id: number) => {
    if (!workspace.value) return;

    router.delete(
        route('workspace.building.members.destroy', {
            building: workspace.value.slug,
            user: id,
        }),
        {
            onSuccess: () => {
                success('Membro rimosso con successo');
                router.reload({ only: ['users', 'building'] });
            },
            onError: () => {
                error('Si è verificato un errore');
            },
        },
    );
};
</script>

<style>
@reference '@/../css/base.css';

.buildings-show__content {
    @apply mt-6;
}

.buildings-show__details {
    @apply py-4;
}

.buildings-show__details-header {
    @apply mb-4 flex justify-end;
}

.buildings-show__details-grid {
    @apply grid gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.buildings-show__details-item {
    @apply flex flex-col gap-1;
}

.buildings-show__label {
    @apply text-sm font-medium text-gray-500;
}

.buildings-show__value {
    @apply text-gray-900;
}

.buildings-show__addresses {
    @apply py-4;
}

.buildings-show__addresses-header {
    @apply mb-4 flex justify-end;
}

.buildings-show__users {
    @apply py-4;
}

.buildings-show__users-header {
    @apply mb-4 flex justify-end;
}
</style>
