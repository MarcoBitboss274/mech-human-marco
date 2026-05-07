<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <div>
                <h1 class="page__title">{{ supplier.name ?? t('Fornitore') }}</h1>
                <p v-if="supplier.vat" class="text-sm text-gray-500">P.IVA {{ supplier.vat }}</p>
            </div>
            <div class="hidden lg:inline-flex">
                <BbButton variant="secondary" prepend:icon="arrow-left" @click="router.get(route('suppliers.index'))">
                    {{ t('Torna alla lista') }}
                </BbButton>
            </div>
        </div>

        <div class="suppliers-show__content">
            <div class="mt-4">
                <BbTab v-model="activeTab" :items="tabs" />
            </div>

            <div v-if="activeTab === 'details'" class="mt-4">
                <SupplierForm :supplier="supplier" @data:updated="onSupplierUpdated" />
            </div>

            <div v-else-if="activeTab === 'members'" class="mt-4 space-y-4">
                <div class="flex items-center justify-end">
                    <BbButton append:icon="plus" @click="openInviteMember">{{ t('Invita membro') }}</BbButton>
                </div>

                <BbTable :columns="memberColumns" item-value="id" :items="members" :actions="true">
                    <template #no-data>{{ t('Nessun membro associato') }}</template>
                    <template #role="{ item }">
                        <SupplierUserRoleBadge :role="item.pivot?.role" />
                    </template>
                    <template #created_at="{ item }">
                        {{ formatDateTime(item.created_at) }}
                    </template>
                    <template #accepted_at="{ item }">
                        {{ formatDateTime(item.accepted_at) }}
                    </template>
                    <template #actions="{ item }">
                        <div class="flex gap-x-2">
                            <BbButton icon="pencil" size="xs" @click="openEditMember(item)">
                                {{ t('Modifica') }}
                            </BbButton>
                            <BbPopover v-if="item.id !== currentUser.id">
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
                                                    onRemove(item);
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
        </div>

        <BbDialog v-model="inviteMemberModal" :title="t('Invita membro')" size="md">
            <form class="flex flex-col gap-4" @submit.prevent="submitInviteMember">
                <BbTextInput v-model="inviteMemberForm.email" type="email" :label="t('Email')" required :errors="inviteMemberForm.errors?.email" />
                <BbSelect
                    v-model="inviteMemberForm.role"
                    item-text="label"
                    item-value="value"
                    :items="SUPPLIER_ROLES"
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
                    :items="SUPPLIER_ROLES"
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
import SupplierForm from '@/pages/suppliers/partials/Form.vue';
import SupplierUserRoleBadge from '@/components/suppliers/SupplierUserRoleBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Supplier, SupplierMember } from '@/types/Supplier';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbPopover, BbSelect, BbTab, type BbTabItem, BbTable, type BbTableColumn, BbTextInput } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { success, error } = useMainToast();
const { currentUser } = usePermissions();

const SUPPLIER_ROLES = computed(() => [
    { value: 'admin', label: t('Admin') },
    { value: 'member', label: t('Membro') },
]);

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Dettaglio fornitore' }, () => [page]),
});

type Props = {
    supplier: Supplier;
    members: SupplierMember[];
};

const props = defineProps<Props>();

const activeTab = ref<'details' | 'members'>('details');

const tabs: BbTabItem[] = [
    { key: 'details', label: t('Dettagli fornitore') },
    { key: 'members', label: t('Membri') },
];

const members = computed(() => props.members);

const memberColumns: BbTableColumn[] = [
    { key: 'name', label: t('Nome') },
    { key: 'surname', label: t('Cognome') },
    { key: 'email', label: t('Email') },
    { key: 'role', label: t('Ruolo') },
    { key: 'created_at', label: t('Data creazione') },
    { key: 'accepted_at', label: t('Accettato il') },
];

const formatDateTime = (d: string | null | undefined): string =>
    d ? new Date(d).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' }) : '--';

const getFullName = (item: SupplierMember) => `${item.name ?? ''} ${item.surname ?? ''}`.trim() || (item.email ?? '');

const onSupplierUpdated = () => {
    success(t('Modifiche salvate'));
};

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

const openInviteMember = () => {
    inviteMemberForm.reset();
    inviteMemberForm.clearErrors();
    inviteMemberModal.value = true;
};

const submitInviteMember = () => {
    inviteMemberForm.post(route('suppliers.members.invite', { supplier: props.supplier.id }), {
        preserveScroll: true,
        onSuccess: () => {
            inviteMemberModal.value = false;
            inviteMemberForm.reset();
            success(t('Invito inviato'));
            router.reload({ only: ['members'] });
        },
        onError: () => {
            error(t('Si è verificato un errore'));
        },
    });
};

const openEditMember = (member: SupplierMember) => {
    selectedMemberId.value = member.id;
    editMemberForm.reset();
    editMemberForm.role = member.pivot?.role === 'admin' ? 'admin' : 'member';
    editMemberModal.value = true;
};

const submitEditMemberRole = () => {
    if (selectedMemberId.value === null) return;
    editMemberForm.put(
        route('suppliers.members.update', { supplier: props.supplier.id, user: selectedMemberId.value }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editMemberModal.value = false;
                selectedMemberId.value = null;
                success(t('Ruolo membro aggiornato'));
                router.reload({ only: ['members'] });
            },
            onError: () => {
                error(t('Si è verificato un errore'));
            },
        },
    );
};

const onRemove = (member: SupplierMember) => {
    router.delete(
        route('suppliers.members.destroy', { supplier: props.supplier.id, user: member.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('Utente rimosso dal team del fornitore'));
                router.reload({ only: ['members'] });
            },
            onError: () => {
                error(t('Si è verificato un errore'));
            },
        },
    );
};
</script>
