import http from '@/utils/http.ts';
import type { AxiosRequestConfig, AxiosResponse } from 'axios';
import type { IndexResponse } from '@/modules/types/support/responses/IndexResponse.ts';
import type { QueryParameters } from '@/modules/types/support/responses/QueryParameters.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import type { TableColumnComponentType } from '@/modules/types/support/list-views/TableColumnComponentType.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import { toPayload } from '@/composables/useGlobalHelpers';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';

export default class Model<T> {
    id!: number;
    created_at!: string;
    updated_at!: string;

    private readonly entity: string;
    private readonly entity_singular: string;

    /**
     * Constructor
     * @param entity
     * @param entity_singular
     */
    constructor(entity: string, entity_singular: string) {
        this.entity = entity;
        this.entity_singular = entity_singular;
    }

    /**
     * Fetch all records
     * @param params
     * @param config
     */
    index(
        params: QueryParameters = {
            page: 1,
        },
        config: AxiosRequestConfig = {},
    ): Promise<AxiosResponse<IndexResponse<T>>> {
        return http.get(this.entity, {
            params,
            ...config,
        });
    }

    /**
     * Fetch a single record
     * @param id
     * @param config
     */
    show(id: number, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
        return http.get(`${this.entity}/${id}`, config);
    }

    /**
     * Create a new record
     * @param data
     * @param config
     */
    create(data: Record<string, unknown>, config = {}) {
        const { body } = toPayload(data);
        return http.post(this.entity, body, config);
    }

    /**
     * Update an existing record
     * @param id
     * @param data
     * @param config
     * @param patch
     */
    update(id: number | string, data: Record<string, unknown>, config = {}, patch = false) {
        const { body, isForm } = toPayload(data);

        if (isForm) {
            (body as FormData).append('_method', patch ? 'PATCH' : 'PUT');
            return http.post(`${this.entity}/${id}`, body, config);
        }

        return patch
            ? http.patch(`${this.entity}/${id}`, body, config)
            : http.put(`${this.entity}/${id}`, body, config);
    }

    /**
     * Delete a record
     * @param id
     * @param config
     */
    destroy(id: number, config: AxiosRequestConfig = {}): Promise<AxiosResponse<boolean>> {
        return http.delete(`${this.entity}/${id}`, config);
    }

    /**
     * Get the entity name in plural
     */
    getEntity(): string {
        return this.entity;
    }

    /**
     * Get the entity singular name
     */
    getEntitySingular(): string {
        return this.entity_singular;
    }

    /**
     * Get default columns to display in the list view
     */
    columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'created_at',
                label: 'TABLE_DEFAULT_CREATED_AT',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                filterKey: 'created_at_between',
                component: {
                    type: TableColumnComponentEnum.DATE,
                },
            }),
            new TableColumn({
                name: 'updated_at',
                label: 'TABLE_DEFAULT_UPDATED_AT',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                filterKey: 'updated_at_between',
                component: {
                    type: TableColumnComponentEnum.DATE,
                },
            }),
        ];
    }

    /**
     * Get the default input fields for the form
     */
    inputFields(): BaseInputField[] {
        return [];
    }

    /**
     * Get the form detail builder configuration
     */
    detailSchematic(): FormBuilder {
        return {
            name: 'default',
        };
    }
}
