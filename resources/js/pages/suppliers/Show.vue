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
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600">{{ t('Membri del team del fornitore') }}</p>
                    <BbButton prepend:icon="plus" @click="openInviteMember">{{ t('Invita membro') }}</BbButton>
                </div>

                <BbTable :columns="memberColumns" item-value="id" :items="members" :loading="false">
                    <template #no-data>{{ t('Nessun membro associato') }}</template>
                    <template #role="{ item }">
                        <BbSelect
                            :model-value="item.role"
                            item-text="label"
                            item-value="value"
                            :items="selectSupplierUserRoles"
                            class="w-40"
                            @update:model-value="(v: string) => onRoleChange(item, v)"
                        />
                    </template>
                    <template #status="{ item }">
                        <SupplierMemberStatusBadge :status="item.status" size="xs" />
                    </template>
                    <template #actions="{ item }">
                        <BbButton icon="trash" size="xs" variant="danger" @click="onRemove(item)">
                            {{ t('Rimuovi') }}
                        </BbButton>
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
                    :items="selectSupplierUserRoles"
                    :label="t('Ruolo')"
                    :errors="inviteMemberForm.errors?.role"
                />
                <BbButton type="submit" :disabled="inviteMemberForm.processing">
                    {{ t('Invita') }}
                </BbButton>
            </form>
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import SupplierForm from '@/pages/suppliers/partials/Form.vue';
import SupplierMemberStatusBadge from '@/components/suppliers/SupplierMemberStatusBadge.vue';
import { useSelect } from '@/composables/useSelect';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Supplier, SupplierMember } from '@/types/Supplier';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbDialog, BbSelect, BbTab, type BbTabItem, BbTable, type BbTableColumn, BbTextInput, useToast } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();

defineOptions({
    layout: (h: any, page: any) => h(AppLayout, { title: 'Dettaglio fornitore' }, () => [page]),
});

type Props = {
    supplier: Supplier;
    members: SupplierMember[];
};

const props = defineProps<Props>();

const { select: selectSupplierUserRoles } = useSelect('supplier-user-roles');

const activeTab = ref<'details' | 'members'>('details');

const tabs: BbTabItem[] = [
    { key: 'details', label: t('Dettagli fornitore') },
    { key: 'members', label: t('Membri') },
];

const members = ref<SupplierMember[]>(props.members);

const memberColumns: BbTableColumn[] = [
    { key: 'full_name', label: t('Nome') },
    { key: 'email', label: t('Email') },
    { key: 'role', label: t('Ruolo') },
    { key: 'status', label: t('Stato') },
];

const onSupplierUpdated = () => {
    toast({ theme: 'success', text: t('Modifiche salvate') });
};

const inviteMemberModal = ref(false);
const inviteMemberForm = useForm({
    email: '',
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
            toast({ theme: 'success', text: t('Invito inviato') });
            router.reload({ only: ['members'] });
        },
    });
};

const onRoleChange = (member: SupplierMember, newRole: string) => {
    if (!newRole || newRole === member.role) return;
    router.put(
        route('suppliers.members.update', { supplier: props.supplier.id, user: member.id }),
        { role: newRole },
        {
            preserveScroll: true,
            onSuccess: () => {
                member.role = newRole;
                toast({ theme: 'success', text: t('Ruolo aggiornato') });
            },
        },
    );
};

const onRemove = (member: SupplierMember) => {
    if (!confirm(t('Rimuovere {name} dal team?', { name: member.full_name }))) return;
    router.delete(
        route('suppliers.members.destroy', { supplier: props.supplier.id, user: member.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                members.value = members.value.filter((m) => m.id !== member.id);
                toast({ theme: 'success', text: t('Utente rimosso dal team del fornitore') });
            },
        },
    );
};
</script>
