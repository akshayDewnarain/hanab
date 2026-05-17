/**
 * Serialized employee skill from `employee-skills` API.
 */
export type EmployeeSkillRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    employee_id: number;
    skill_id: number;
    level: string | null;
    notes: string | null;
    assessed_at: string | null;
};
