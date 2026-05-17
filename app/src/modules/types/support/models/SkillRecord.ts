/**
 * Serialized skill from `skills` API.
 */
export type SkillRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    name: string;
    code: string | null;
    label_background_color: string | null;
    label_text_color: string | null;
    label_border_color: string | null;
    category: string | null;
    description: string | null;
    is_active: boolean;
};
