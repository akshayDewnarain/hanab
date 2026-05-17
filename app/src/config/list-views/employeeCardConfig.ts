import type { CardListConfig } from '@/modules/types/support/list-views/CardListConfig.ts';

/**
 * Card layout for the employees directory (smoelenboek).
 */
export const employeeCardConfig: CardListConfig = {
    titleFields: ['first_name', 'last_name'],
    imageField: 'image',
    subtitleField: 'email',
    headerColor: 'var(--color-primary)',
    deriveFromColumns: false,
    emptyStateKey: 'EMPLOYEE_CARD_LIST_EMPTY',
    fields: [
        {
            key: 'phone',
            label: 'GENERAL_PHONE',
            icon: 'material-symbols:phone-in-talk-outline-rounded',
            hideEmpty: true,
        },
        {
            key: 'employee_number',
            label: 'EMPLOYEE_NUMBER',
            icon: 'material-symbols:badge-outline-rounded',
            hideEmpty: true,
        },
        {
            key: 'employee_role',
            label: 'EMPLOYEE_ROLE',
            icon: 'material-symbols:cases-outline-rounded',
            columnName: 'employee_role',
            hideEmpty: true,
        },
        {
            key: 'employee_location',
            label: 'EMPLOYEE_LOCATION',
            icon: 'material-symbols:location-on-outline-rounded',
            columnName: 'employee_location',
            hideEmpty: true,
        },
        {
            key: 'employment_type',
            label: 'EMPLOYEE_EMPLOYMENT_TYPE',
            icon: 'material-symbols:schedule-outline-rounded',
            hideEmpty: true,
        },
        {
            key: 'start_date',
            label: 'EMPLOYEE_START_DATE',
            icon: 'material-symbols:calendar-today-outline-rounded',
            type: 'date',
            columnName: 'start_date',
            hideEmpty: true,
        },
        {
            key: 'skills',
            label: 'EMPLOYEE_SKILLS',
            icon: 'material-symbols:verified-outline-rounded',
            type: 'badges',
            columnName: 'skills',
            badgeKey: 'code',
            hideEmpty: false,
            emptyWarningKey: 'EMPLOYEE_CARD_NO_SKILLS',
        },
        {
            key: 'certificates',
            label: 'EMPLOYEE_CERTIFICATES',
            icon: 'material-symbols:workspace-premium-outline',
            type: 'badges',
            columnName: 'certificates',
            badgeKey: 'code',
            hideEmpty: false,
            emptyWarningKey: 'EMPLOYEE_CARD_NO_CERTIFICATES',
        },
    ],
};
