export interface Query {
    page?: number;
    perPage?: number;
    search?: string;
    filters?: Record<string, unknown>;
    filter?: Record<string, unknown>;
    sort?: string;
    include?: string[];
}
