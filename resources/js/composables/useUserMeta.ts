import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type UserMeta = {
    customer_buildings_count?: number | null;
};

export function useUserMeta() {
    const userMeta = computed<UserMeta>(() => usePage().props.user_meta as UserMeta);

    return {
        userMeta,
    };
}
