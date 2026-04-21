export type Order = {
    id: number;
    operation_id: number | null;
    code: string | null;
    status: string | null;
    confirmed_at: string | null;
    amount: number | null;
    description: string | null;
    created_at: string;
    updated_at: string;
    operation?: {
        id: number;
        typology: string | null;
        status: string | null;
    } | null;
};

export type OrderForm = {
    operation_id: number | null;
    code: string | null;
    status: string | null;
    amount: number | null;
    description: string | null;
};
