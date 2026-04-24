export type ActorPerson = { name: string | null; email: string | null } | null;

export type PrescriptionSummary = {
    empty: boolean;
    count: number;
    status: string | null;
    main_id: number | null;
    sent_at: string | null;
    confirmed_at: string | null;
};

export type SuppliersSummary = {
    empty: boolean;
    selected: {
        id: number;
        name: string;
        selected_at: string | null;
    } | null;
};

export type QuotesSummary = {
    empty: boolean;
    count: number;
    main_id: number | null;
    accepted: {
        id: number;
        accepted_at: string | null;
    } | null;
    sent_ids: number[];
    rejected_count: number;
    canceled_count: number;
};

export type ProductionSummary = {
    empty: boolean;
    count: number;
    status: string | null;
    main_id: number | null;
    confirmed_at: string | null;
    canceled_at: string | null;
};

export type InvoicesSummary = {
    empty: boolean;
    sent_ids: number[];
    canceled_count: number;
};

export type OverviewPayload = {
    actors: {
        requester: ActorPerson;
        building: ActorPerson;
        agent: ActorPerson;
        supplier: ActorPerson;
    };
    summary: {
        prescription: PrescriptionSummary;
        suppliers?: SuppliersSummary;
        quotes: QuotesSummary;
        production: ProductionSummary;
        invoices: InvoicesSummary;
    };
};
