import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { UserRecord } from '@/modules/types/support/models/UserRecord.ts';

/**
 * API model for the `users` resource (CRUD, list builder, forms).
 */
export default class User extends Model<UserRecord> {
    constructor() {
        super('users', 'user');
    }

    override columns(): TableColumn[] {
        return [];
    }

    override inputFields(): BaseInputField[] {
        return [];
    }

    override detailSchematic(): FormBuilder {
        return {
            name: 'default',
        };
    }
}
