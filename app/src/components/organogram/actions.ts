import type { InjectionKey } from 'vue';

export type OrganogramActions = {
    openEmployee: (employeeId: number) => void;
    toggleNode: (nodeId: string) => void;
    focusGroup: (groupId: string) => void;
};

export const organogramActionsKey: InjectionKey<OrganogramActions> = Symbol('organogram-actions');
