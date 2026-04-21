export type QuoteStatus = 'draft' | 'sent' | 'accepted' | 'rejected' | 'canceled';

export type Quote = {
    id: number;
    operation_id: number | null;
    status: QuoteStatus | null;
    accepted_at: string | null;
    rejected_at: string | null;
    rejected_notes: string | null;
    canceled_at: string | null;
    canceled_notes: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    operation?: {
        id: number;
        typology: string | null;
        status: string | null;
    } | null;
};

export type QuoteForm = {
    operation_id: number | null;
    status: QuoteStatus | null;
    accepted_at: string | null;
    rejected_at: string | null;
    rejected_notes: string | null;
    canceled_at: string | null;
    canceled_notes: string | null;
    notes: string | null;
};
