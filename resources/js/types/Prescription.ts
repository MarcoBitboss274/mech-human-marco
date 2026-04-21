export type Prescription = {
    id: number;
    operation_id: number | null;
    building_id: number | null;
    user_id: number | null;
    status: string | null;
    typology: string | null;
    ref: string | null;
    name: string | null;
    surname: string | null;
    age: number | null;
    gender: string | null;
    send_at: string | null;
    expire_at: string | null;
    company_name: string | null;
    address: string | null;
    city: string | null;
    province: string | null;
    cap: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    protrusor_details?: {
        protrusor_typology: string | null;
        odontogram: number[] | null;
        jig: string | null;
        remaining_upper_teeth: string | null;
        remaining_lower_teeth: string | null;
        transpalatal_arch: string | null;
        mandibular_advancement: number | null;
        mandibular_advancement_2: number | null;
    } | null;
    lybra_aligner_details?: {
        cut_line: string | null;
    } | null;
    guided_surgery_details?: {
        surgery_typology: string | null;
        odontogram: number[] | null;
        desired_implant_line: string | null;
        additional_info: string | null;
    } | null;
    three_d_mesh_details?: {
        dimension: string | null;
        odontogram: number[] | null;
        outer_finish: string | null;
        inner_finish: string | null;
        pattern: string | null;
        stress_breakers: string | null;
        '3d_model': string | null;
        screw_diameter: number | null;
        shared_project_note: string | null;
    } | null;
    prosthesis_details?: {
        typology: string | null;
        crowns_and_bridges_details: string | null;
        full_bridge_details: string | null;
        odontogram: number[] | null;
        '3d_normal_model': string | null;
        '3d_excellent_model': string | null;
    } | null;
    semi_finished_prostheses_details?: {
        typology: string | null;
        crowns_and_bridges_details: string | null;
        full_bridge_details: string | null;
        odontogram: number[] | null;
    } | null;
    operation?: {
        id: number;
        typology: string | null;
        status: string | null;
    } | null;
    building?: {
        id: number;
        name: string | null;
    } | null;
    user?: {
        id: number;
        name: string | null;
        surname: string | null;
    } | null;
};

export type PrescriptionForm = {
    operation_id: number | null;
    building_id: number | null;
    user_id: number | null;
    status: string | null;
    typology: string | null;
    ref: string | null;
    name: string | null;
    surname: string | null;
    age: number | null;
    gender: string | null;
};
