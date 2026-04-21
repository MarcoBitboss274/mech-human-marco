import { Building } from '@/types/Building';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useWorkspace() {
    const workspace = computed<Building | null>(() => usePage().props.workspace as Building | null);

    return {
        workspace,
    };
}
