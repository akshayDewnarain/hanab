import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { TableColumnComponentType } from '@/modules/types/support/list-views/TableColumnComponentType.ts';
import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
import type { TableRelation } from '@/modules/types/support/list-views/TableRelation.ts';

export default class TableColumn implements TableColumnInterface {
    public name!: string;
    public label!: string;
    public sortable!: boolean;
    public filterable!: boolean;
    public visible!: boolean;
    public quickFilter!: boolean;
    public component?: TableColumnComponentType;
    public width?: string;
    public formatter?: (value: unknown) => string;
    public relation?: TableRelation;
    public filterKey?: string;

    public constructor(data: Partial<TableColumn>, enumType?: Record<string, string | number>) {
        Object.assign(this, data);

        if (enumType) {
            if (!this.component) {
                this.component = {
                    type: TableColumnComponentEnum.ENUM,
                } as TableColumnComponentType;
            }

            this.component.enumValues = this.getEnumValues(enumType);
        }
    }

    private getEnumValues(enumType: object): { [key: string]: string } {
        const enumValues: { [key: string]: string } = {};

        for (const [key, value] of Object.entries(enumType)) {
            enumValues[key] = value as string;
        }

        return enumValues;
    }
}
