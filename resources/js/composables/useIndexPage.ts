import { useAsyncFn } from '@/composables/useAsyncFn';
import type { Pagination } from '@/types/Pagination';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { reactive, ref, watch } from 'vue';

type FromPagination = Partial<Omit<Pagination<any>, 'data'>>;

type Options = {
    debounce?: number;
    perPage?: number;
};

type Filters = Record<string, string | number | null>;

export const useIndexPage = (route: string, filtersDefault: Filters = {}, paginationDefault: FromPagination = {}, options: Options = {}) => {
    const filters = reactive(filtersDefault);
    const page = ref(paginationDefault.current_page ?? 1);
    const perPage = ref(paginationDefault.per_page ?? options.perPage ?? 1);

    const { error, loading, execute } = useAsyncFn(
        async () => {
            await router.visit(route, {
                data: {
                    ...Object.fromEntries(
                        Object.entries(filters)
                            .filter(([key, value]) => value !== null)
                            .map(([key, value]) => [key, value.toString()]),
                    ),
                    page: page.value,
                    per_page: perPage.value,
                },
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onSuccess: (response) => {},
            });
        },
        { immediate: false },
    );

    watchDebounced(
        filters,
        () => {
            page.value !== 1 ? (page.value = 1) : execute();
        },
        { debounce: options.debounce ?? 400 },
    );
    watch(page, execute);
    watch(perPage, execute);

    const resetFilters = () => {
        Object.keys(filters).forEach((key) => {
            filters[key] = null;
        });
    };

    return {
        filters,
        error,
        loading,
        execute,
        page,
        perPage,
        resetFilters,
    };
};
