import Certificate from '@/models/employees/Certificate';
import Employee from '@/models/employees/Employee';
import EmployeeCertificate from '@/models/employees/EmployeeCertificate';
import EmployeeLocation from '@/models/employees/EmployeeLocation';
import EmployeeRole from '@/models/employees/EmployeeRole';
import EmployeeSkill from '@/models/employees/EmployeeSkill';
import Skill from '@/models/employees/Skill';
import { CustomField } from '@/modules/models/support/forms/CustomField';
import type BaseInputField from '@/modules/models/support/inputs/BaseInputField';

/** Permission that gates import, export, and templates for a catalog key. */
export function transferPermission(modelKey: string): string {
    if (modelKey === 'employees') {
        return 'import employees';
    }

    return `create ${modelKey.replaceAll('-', '_')}`;
}

export const TRANSFER_MODEL_LABELS: Record<string, string> = {
    employees: 'PAGE_TITLE_EMPLOYEES',
    'employee-roles': 'PAGE_TITLE_EMPLOYEE_ROLES',
    'employee-locations': 'PAGE_TITLE_EMPLOYEE_LOCATIONS',
    skills: 'PAGE_TITLE_SKILLS',
    certificates: 'PAGE_TITLE_CERTIFICATES',
    'employee-skills': 'PAGE_TITLE_EMPLOYEE_SKILLS',
    'employee-certificates': 'PAGE_TITLE_EMPLOYEE_CERTIFICATES',
};

const columnLabels: Record<string, Record<string, string>> = {
    employees: {
        role: 'EMPLOYEE_ROLE',
        location: 'EMPLOYEE_LOCATION',
    },
};

const models: Record<string, () => { inputFields: () => BaseInputField[] }> = {
    employees: () => new Employee(),
    'employee-roles': () => new EmployeeRole(),
    'employee-locations': () => new EmployeeLocation(),
    skills: () => new Skill(),
    certificates: () => new Certificate(),
    'employee-skills': () => new EmployeeSkill(),
    'employee-certificates': () => new EmployeeCertificate(),
};

/** Translation keys for the fields this app already shows on the model form. */
export function transferFieldLabelKeys(modelKey: string): Map<string, string> {
    const model = models[modelKey]?.();
    const labels = new Map<string, string>();
    if (!model) {
        return labels;
    }

    for (const field of model.inputFields()) {
        if (field instanceof CustomField && field.isExcludedFromForm()) {
            continue;
        }
        if (!field.options.name || !field.options.label) {
            continue;
        }
        labels.set(field.options.name, field.options.label);
    }

    for (const [name, key] of Object.entries(columnLabels[modelKey] ?? {})) {
        labels.set(name, key);
    }

    return labels;
}
