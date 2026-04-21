export type Building = {
    id: number;
    slug: string | null;
    name: string | null;
    vat: string | null;
    is_studio: boolean | null;
    customer_code: string | null;
    is_laboratory: boolean | null;
    headquarter_address: string | null;
    legal_address: string | null;
    approved: boolean;
    fiscal_code: string | null;
    sdi_code: string | null;
    agent_id?: number | null;
    agent_full_name?: string | null;
    created_at: string;
    updated_at: string;
    users_count?: number;
};

export type BuildingForm = {
    name: string | null;
    vat: string | null;
    agent_id: number | null;
    is_studio: boolean | null;
    customer_code: string | null;
    is_laboratory: boolean | null;
    headquarter_address: string | null;
    legal_address: string | null;
    approved: boolean;
    fiscal_code: string | null;
    sdi_code: string | null;
};
