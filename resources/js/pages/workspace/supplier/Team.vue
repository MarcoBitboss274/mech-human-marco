<template>
    <div>
        <h1 class="page__title">{{ t('Team del fornitore') }}</h1>
        <p class="page__subtitle">{{ t('Gestisci ruoli e composizione del team. Per aggiungere un nuovo membro contatta M&H.') }}</p>

        <BbTable :columns="memberColumns" item-value="id" :items="localMembers" :loading="false">
            <template #no-data>{{ t('Nessun membro nel team') }}</template>
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
            <template #accepted_at="{ item }">
                {{ formatDateTime(item.accepted_at) }}
            </template>
            <template #actions="{ item }">
                <BbButton icon="trash" size="xs" variant="danger" @click="onRemove(item)">
                    {{ t('Rimuovi') }}
                </BbButton>
            </template>
        </BbTable>
    </div>
</template>

<script setup lang="ts">
import { useSelect } from '@/composables/useSelect';
import WorkspaceSupplierLayout from '@/layouts/WorkspaceSupplierLayout.vue';
import type { SupplierMember } from '@/types/Supplier';
import { router } from '@inertiajs/vue3';
import { BbButton, BbSelect, BbTable, type BbTableColumn, useToast } from 'bitboss-ui';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();
const { select: selectSupplierUserRoles } = useSelect('supplier-user-roles');

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceSupplierLayout, { title: 'Team del fornitore' }, () => [page]),
});

const props = defineProps<{
    supplier: { id: number; name: string | null };
    members: SupplierMember[];
}>();

const localMembers = ref<SupplierMember[]>([...props.members]);

const memberColumns: BbTableColumn[] = [
    { key: 'full_name', label: t('Nome') },
    { key: 'email', label: t('Email') },
    { key: 'role', label: t('Ruolo') },
    { key: 'accepted_at', label: t('Accettato il') },
];

const formatDateTime = (d: string | null | undefined): string =>
    d ? new Date(d).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' }) : '--';

const onRoleChange = (member: SupplierMember, newRole: string) => {
    if (!newRole || newRole === member.role) return;
    router.put(
        route('workspace.supplier.team.update', { user: member.id }),
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
    router.delete(route('workspace.supplier.team.destroy', { user: member.id }), {
        preserveScroll: true,
        onSuccess: () => {
            localMembers.value = localMembers.value.filter((m) => m.id !== member.id);
            toast({ theme: 'success', text: t('Utente rimosso dal team del fornitore') });
        },
    });
};
</script>
