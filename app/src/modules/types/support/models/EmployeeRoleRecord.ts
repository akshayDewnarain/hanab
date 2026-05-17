/**
 * Serialized employee role from `employee-roles` API.
 */
export type EmployeeRoleRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    name: string;
    description: string | null;
    is_active: boolean;
};
