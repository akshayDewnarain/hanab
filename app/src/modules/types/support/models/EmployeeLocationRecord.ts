/**
 * Serialized employee location from `employee-locations` API.
 */
export type EmployeeLocationRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    name: string;
    cluster_name: string | null;
    is_active: boolean;
};
