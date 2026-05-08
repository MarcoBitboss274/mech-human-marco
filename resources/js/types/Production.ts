export type Production = {
    id: number;
    operation_id: number | null;
    status: string | null;
    confirmed_at: string | null;
    canceled_at: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
    operation?: {
        id: number;
        typology: string | null;
        status: string | null;
        batch_number?: string | null;
    } | null;
};

export type ProductionForm = {
    operation_id: number | null;
    status: string | null;
};

