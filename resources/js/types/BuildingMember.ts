export type BuildingMember = {
    id: number;
    name: string;
    surname: string;
    email: string;
    building_role: 'admin' | 'member' | null;
    joined_at: string | null;
    accepted_at: string | null;
};
