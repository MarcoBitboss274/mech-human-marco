<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <h1 class="page__title">{{ t('Team del fornitore') }}</h1>
            <BbButton append:icon="plus" @click="openInviteMember">{{ t('Invita membro') }}</BbButton>
        </div>
        <p class="page__subtitle">{{ t('Gestisci ruoli e composizione del team.') }}</p>

        <div class="suppliers-show__content mt-4 space-y-4">
            <BbTable :columns="memberColumns" item-value="id" :items="members" :actions="true">
                <template #no-data>{{ t('Nessun membro nel team') }}</template>
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
import SupplierUserRoleBadge from '@/components/suppliers/SupplierUserRoleBadge.vue';
import { useMainToast } from '@/composables/useMainToast';
import { usePermissions } from '@/composables/usePermissions';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { SupplierMember } from '@/types/Supplier';
import { router, useForm } from '@inertiajs/vue3';
import type { BbTableColumn } from 'bitboss-ui';
import { BbButton, BbDialog, BbPopover, BbSelect, BbTable, BbTextInput } from 'bitboss-ui';
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
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Team del fornitore' }, () => [page]),
});

const props = defineProps<{
    supplier: { id: number; name: string | null };
    members: SupplierMember[];
}>();

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
    inviteMemberForm.post(route('workspace.supplier.team.invite'), {
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
        route('workspace.supplier.team.update', { user: selectedMemberId.value }),
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
    router.delete(route('workspace.supplier.team.destroy', { user: member.id }), {
        preserveScroll: true,
        onSuccess: () => {
            success(t('Utente rimosso dal team del fornitore'));
            router.reload({ only: ['members'] });
        },
        onError: () => {
            error(t('Si è verificato un errore'));
        },
    });
};
</script>
