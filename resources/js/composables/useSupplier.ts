import { Supplier } from '@/types/Supplier';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useSupplier() {
    const supplier = computed<Supplier | null>(() => usePage().props.supplier as Supplier | null);

    return {
        supplier,
    };
}
