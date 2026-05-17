import type MediaURL from '@/modules/types/support/media/MediaURL.ts';
import type { CertificateRecord } from '@/modules/types/support/models/CertificateRecord.ts';
import type { SkillRecord } from '@/modules/types/support/models/SkillRecord.ts';

/**
 * Serialized employee returned by the employees API (plain JSON, not an {@link import('@/models/employees/Employee.ts').default Employee} class instance).
 *
 * Aligns with the API `EmployeeData` DTO and `EmployeeResource` attribute list (`employee_role_id` through `is_active`).
 *
 * @property id - Primary key.
 * @property created_at - ISO timestamp from API.
 * @property updated_at - ISO timestamp from API.
 * @property employee_role_id - Foreign key to `employee_roles`.
 * @property employee_location_id - Foreign key to `employee_locations`.
 * @property first_name - Given name.
 * @property last_name - Family name.
 * @property email - Unique work email.
 * @property phone - Optional contact number.
 * @property employee_number - Unique internal identifier.
 * @property employment_type - Indexed employment category string from API.
 * @property start_date - ISO date string when present; null if open-ended / unknown.
 * @property is_active - Whether the employee is currently active.
 * @property image - Optional persisted media URLs when employee image exists.
 */
export type EmployeeRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    employee_role_id: number;
    employee_location_id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    employee_number: string;
    employment_type: string;
    start_date: string | null;
    is_active: boolean;
    image?: MediaURL | null;
    employee_role?: { id: number; name: string } | null;
    employee_location?: { id: number; name: string } | null;
    skills?: SkillRecord[];
    certificates?: CertificateRecord[];
};
