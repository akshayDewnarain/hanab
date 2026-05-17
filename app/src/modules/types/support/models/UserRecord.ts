/**
 * Serialized user returned by auth and user endpoints (plain JSON, not a {@link import('@/models/auth/User.ts').default Model} instance).
 *
 * Used by Pinia auth state and API typings where the payload does not include `entity` / constructor wiring.
 *
 * @property id - Primary key.
 * @property created_at - ISO timestamp from API.
 * @property updated_at - ISO timestamp from API.
 * @property name - Display name.
 * @property email - Login email.
 * @property role_id - Resolved primary role id when exposed by the resource.
 * @property role_name - Resolved primary role name when exposed by the resource.
 * @property permissions - Permission names or `{ id, name }` rows from the API.
 * @property roles - Assigned roles when included by the API.
 * @property profile_picture_url - Absolute URL when media is exposed by the resource.
 */
export type UserRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    name: string;
    email: string;
    role_id?: number;
    role_name?: string;
    permissions?: Array<string | { id?: number; name: string }>;
    roles?: Array<{ id?: number; name: string }>;
    profile_picture_url?: string;
};
