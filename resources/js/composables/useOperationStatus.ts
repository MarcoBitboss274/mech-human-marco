import type { Operation } from '@/types/Operation';
import { computed, toValue, type MaybeRefOrGetter } from 'vue';

export const useOperationStatus = (operation: MaybeRefOrGetter<Operation | null | undefined>) => {
    const operationStatus = computed(() => toValue(operation)?.status);
    const prescriptionStatus = computed(() => toValue(operation)?.latest_prescription?.status);

    const enableSuppliersTab = computed(() => ['sent', 'confirmed'].includes(prescriptionStatus.value ?? ''));
    const enableQuotesTab = computed(() => ['sent', 'confirmed'].includes(prescriptionStatus.value ?? ''));
    const enableOrdersTab = computed(() => ['sent', 'confirmed'].includes(prescriptionStatus.value ?? ''));
    const enableInvoicesTab = computed(() => ['sent', 'confirmed'].includes(prescriptionStatus.value ?? ''));

    return {
        operationStatus,
        prescriptionStatus,
        enableSuppliersTab,
        enableQuotesTab,
        enableOrdersTab,
        enableInvoicesTab,
    };
};
