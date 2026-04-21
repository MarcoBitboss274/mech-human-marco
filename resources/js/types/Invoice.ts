import type { Media } from '@/types/Media';

export type Invoice = {
    id: number;
    operation_id: number | null;
    status: string | null;
    sent_at: string | null;
    code: string | null;
    amount: number | null;
    description: string | null;
    created_at: string;
    updated_at: string;
    operation?: {
        id: number;
        typology: string | null;
        status: string | null;
    } | null;
    media?: Media[] | null;
    invoiceFile?: Media | null;
};

export type InvoiceForm = {
    operation_id: number | null;
    status: string | null;
    code: string | null;
    amount: number | null;
    description: string | null;
};
