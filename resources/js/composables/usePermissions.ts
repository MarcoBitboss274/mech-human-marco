import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const permissions = computed(() => usePage().props.auth.permissions);
    const currentUser = computed(() => usePage().props.auth.user);
    const role = computed(() => currentUser.value?.role);
    const impersonating = computed(() => usePage().props.auth.impersonating);
    const workspacePermissions = computed(() => usePage().props.auth.workspacePermissions);
    const workspaceRole = computed(() => usePage().props.auth.workspaceRole);

    const can = (permission: string) => {
        return ['superadmin'].includes(currentUser.value.role) || permissions.value.includes(permission);
    };

    const canInWorkspace = (permission: string) => {
        return workspacePermissions.value?.includes(permission) ?? false;
    };

    const isAdmin = computed(() => role.value === 'admin' || role.value === 'superadmin');
    const isAgent = computed(() => role.value === 'agent');
    const isCustomer = computed(() => role.value === 'customer');
    const isSupplier = computed(() => role.value === 'supplier');

    return {
        can,
        currentUser,
        role,
        impersonating,
        canInWorkspace,
        workspaceRole,
        isAdmin,
        isAgent,
        isCustomer,
        isSupplier,
    };
}
