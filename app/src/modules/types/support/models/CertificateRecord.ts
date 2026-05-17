/**
 * Serialized certificate from `certificates` API.
 */
export type CertificateRecord = {
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
    requires_expiry_date: boolean;
    is_active: boolean;
};
