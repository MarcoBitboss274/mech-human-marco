import type { Invoice } from '@/types/Invoice';
import type { Order } from '@/types/Order';
import type { Prescription } from '@/types/Prescription';
import type { Production } from '@/types/Production';
import type { Quote, QuoteStatus } from '@/types/Quote';
import type { OperationSupplier } from '@/types/Supplier';

export type Operation = {
    id: number;
    building_id: number | null;
    typology: string | null;
    status: string | null;
    canceled_at?: string | null;
    archived_at?: string | null;
    created_at: string;
    updated_at: string;
    can_be_canceled?: boolean;
    can_be_archived?: boolean;
    can_be_reopened?: boolean;
    can_be_reactivated?: boolean;
    can_be_deleted?: boolean;
    building?: {
        id: number;
        name: string | null;
    } | null;
    prescriptions?: Prescription[];
    quotes?: Quote[];
    orders?: Order[];
    productions?: Production[];
    invoices?: Invoice[];
    suppliers?: OperationSupplier[];
    selected_supplier?: OperationSupplier | null;
    selected_supplier_name?: string | null;
    latest_quote_status?: QuoteStatus | null;
    latest_prescription?: Prescription | null;
    batch_number?: string | null;
    supplier_completed_at?: string | null;
    case_status?: string | null;
    production_status?: string | null;
};

export type OperationForm = {
    building_id: number | null;
    typology: string | null;
    status: string | null;
};

export type OperationCreateWizardForm = {
    draft: boolean;
    submit_revision: boolean;
    building_id: number | null;
    user_id: number | null;
    typology: string | null;
    ref: string | null;
    manual: boolean | null;
    name: string | null;
    surname: string | null;
    age: string | null;
    gender: string | null;
    company_name: string | null;
    address: string | null;
    city: string | null;
    province: string | null;
    cap: string | null;
    notes: string | null;
    protrusor_details: {
        protrusor_typology: string | null;
        odontogram: number[] | null;
        jig: string | null;
        remaining_upper_teeth: string | null;
        remaining_lower_teeth: string | null;
        transpalatal_arch: string | null;
        mandibular_advancement: string | null;
        mandibular_advancement_2: string | null;
        note: string | null;
    } | null;
    lybra_aligner_details: {
        cut_line: string | null;
        note: string | null;
    } | null;
    guided_surgery_details: {
        surgery_typology: string | null;
        odontogram: number[] | null;
        desired_implant_line: string | null;
        additional_info: string | null;
        note: string | null;
    } | null;
    three_d_mesh_details: {
        dimension: string | null;
        odontogram: number[] | null;
        outer_finish: string | null;
        inner_finish: string | null;
        pattern: string | null;
        stress_breakers: string | null;
        '3d_model': string | null;
        screw_diameter: string | null;
        shared_project_note: string | null;
        note: string | null;
    } | null;
    prosthesis_details: {
        typology: string | null;
        crowns_and_bridges_details: string | null;
        full_bridge_details: string | null;
        odontogram: number[] | null;
        '3d_normal_model': string | null;
        '3d_excellent_model': string | null;
        note: string | null;
    } | null;
    semi_finished_prostheses_details: {
        typology: string | null;
        crowns_and_bridges_details: string | null;
        full_bridge_details: string | null;
        odontogram: number[] | null;
        note: string | null;
    } | null;
    protrusor_attachments: {
        scansione_intraorale: File | null;
        rilevazione_dell_avanzamento_mandibolare_con_occlusione: File | null;
    } | null;
    lybra_aligner_attachments: {
        scansione_intraorale: File | null;
        foto_del_sorriso_e_morso_del_paziente: File | null;
        ortopantomografia: File | null;
        rx_anteroposteriore_delle_ossa_mascellari: File | null;
    } | null;
    guided_surgery_attachments: {
        scansione_intraorale: File | null;
        cbct_allineabile_con_la_scansione_rilevata: File | null;
        ceratura_diagnostica: File | null;
    } | null;
    three_d_mesh_attachments: {
        scansione_intraorale: File | null;
        cbct_allineabile_con_la_scansione_rilevata: File | null;
        ceratura_diagnostica: File | null;
        scansione_facciale_o_foto_del_sorriso: File | null;
    } | null;
    prosthesis_attachments: {
        scansione_intraorale: File | null;
        articolazione: File | null;
        foto_con_campione_colore: File | null;
        foto_del_sorriso: File | null;
        scansione_e_foto_del_provvisorio: File | null;
    } | null;
    semi_finished_prostheses_attachments: {
        progetto_in_stl_da_fresare_oppure_scansione_digitale_completa: File | null;
        scansione_del_provvisorio: File | null;
    } | null;
};

export type OperationWizardPageData = {
    mode: 'create' | 'edit';
    operationId: number | null;
    prescriptionId: number | null;
    initialForm: OperationCreateWizardForm | null;
    buildings: {
        id: number;
        name: string | null;
        vat: string | null;
        legal_address: string | null;
        addresses: {
            id: number;
            street: string | null;
            cap: string | null;
            city: string | null;
            province: string | null;
            country: string | null;
            is_default: boolean;
        }[];
    }[];
};
