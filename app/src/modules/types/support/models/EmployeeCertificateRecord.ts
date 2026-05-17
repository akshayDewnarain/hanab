/**
 * Serialized employee certificate from `employee-certificates` API.
 */
export type EmployeeCertificateRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    employee_id: number;
    certificate_id: number;
    certificate_number: string | null;
    issued_at: string | null;
    expires_at: string | null;
    notes: string | null;
};
