import axios from 'axios';

export const useSelect = (key: string) => {
    const select = async (
        search?: string | null,
        prefill: boolean = false,
        modelValue?: number | number[] | string | string[] | null,
        filters?: any | null,
    ) => {
        let body = {};

        if (prefill) {
            if (modelValue) {
                body = {
                    id: modelValue,
                };
            }
        } else {
            body = {
                search: search ?? null,
            };
        }

        if (filters) {
            body = {
                ...body,
                ...filters,
            };
        }

        const response = await axios.get(route('select.' + key), {
            params: body,
        });
        return response.data;
    };

    return {
        select,
    };
};
