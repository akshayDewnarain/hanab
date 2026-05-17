import type { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

export interface TableColumnMultiSelectOptions {
    model: ModelConstructor<unknown>;
    labelKey?: string;
    valueKey?: string;
    /** Optional API filter applied when loading options (e.g. active records only). */
    staticFilter?: Record<string, unknown>;
}

export interface TableColumnComponentType {
    type: TableColumnComponentEnum;
    badges?: { [key: string]: string };
    enumValues?: { [key: string]: string };
    customClasses?: string;
    useLocalizedString?: boolean;
    options?: Record<string, string>;
    multiSelect?: TableColumnMultiSelectOptions;
}
