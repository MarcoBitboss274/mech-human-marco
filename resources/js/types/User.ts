export type UserBuilding = {
    id: number;
    name: string | null;
    pivot?: { role: string };
};

export type UserManagedBuilding = {
    id: number;
    name: string | null;
};

export type User = {
    id: number;
    name: string;
    surname: string;
    full_name: string;
    email: string;
    role: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    active: boolean;
    last_login_at: string | null;
    invited_at: string | null;
    impersonable: boolean;
    odontoiatra: boolean;
    odontotecnico: boolean;
    roll_number: string | null;
    roll_province: string | null;
    buildings?: UserBuilding[];
    managed_buildings?: UserManagedBuilding[];
    suppliers?: UserSupplier[];
};

export type UserSupplier = {
    id: number;
    name: string | null;
    pivot?: { role: string };
};

export type BuildingRelation = {
    building_id: number;
    building_label: string;
    role: string;
};

export type SupplierRelation = {
    supplier_id: number | null;
    role: string | null;
};

export type UserForm = {
    id: number | null;
    name: string | null;
    surname: string | null;
    email: string | null;
    role: string | null;
    active: boolean;
    send_invite: boolean;
    password: string | null;
    password_confirmation: string | null;
    odontoiatra: boolean;
    odontotecnico: boolean;
    roll_number: string | null;
    roll_province: string | null;
    building_relations: BuildingRelation[];
    managed_building_ids?: number[];
    supplier_relation: SupplierRelation | null;
    verify_email: boolean;
};
