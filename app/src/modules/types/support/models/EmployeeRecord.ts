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
 * @property email - Personal email address.
 * @property phone - Optional personal phone number.
 * @property business_email - Optional business email address.
 * @property business_phone - Business phone number. Required when the record is saved.
 * @property date_of_birth - Optional date used for a birthday message. Colleagues do not see this date.
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
    initials: string | null;
    email?: string | null;
    phone?: string | null;
    business_email: string | null;
    business_phone: string | null;
    introduction: string | null;
    date_of_birth: string | null;
    manager_id: number | null;
    organization_level_id: number | null;
    employee_number: string;
    employment_type: string;
    start_date: string | null;
    is_active: boolean;
    image?: MediaURL | null;
    employee_role?: { id: number; name: string } | null;
    employee_location?: { id: number; name: string } | null;
    manager?: { id: number; first_name: string; last_name: string; initials: string | null; role: string | null } | null;
    organization_level?: { id: number; name: string } | null;
    skills?: SkillRecord[];
    certificates?: Array<CertificateRecord & { expires_at?: string | null; certificate_number?: string | null }>;
    direct_reports?: EmployeeDirectReport[];
};

export type EmployeeDirectReport = {
    id: number;
    first_name: string;
    last_name: string;
    initials: string;
    role: string | null;
    photo_url: string | null;
};
