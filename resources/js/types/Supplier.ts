export type Supplier = {
    id: number;
    name: string | null;
    vat: string | null;
    mail: string | null;
    phone: string | null;
    address: string | null;
    cap: string | null;
    city: string | null;
    province: string | null;
    status: string | null;
    created_at: string;
    updated_at: string;
};

export type OperationSupplierPivot = {
    operation_id: number;
    supplier_id: number;
    status: string | null;
    selected: boolean | null;
    created_at: string;
    updated_at: string;
};

export type OperationSupplier = Supplier & {
    pivot: OperationSupplierPivot;
};

export type SupplierMember = {
    id: number;
    name: string | null;
    surname: string | null;
    full_name: string;
    email: string | null;
    role: string | null;
    created_at: string | null;
    accepted_at: string | null;
    pivot: {
        role: string | null;
    };
};

export type SupplierForm = {
    name: string | null;
    vat: string | null;
    mail: string | null;
    phone: string | null;
    address: string | null;
    cap: string | null;
    city: string | null;
    province: string | null;
    status: string | null;
};
