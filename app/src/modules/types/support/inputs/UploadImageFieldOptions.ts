import type MediaURL from '@/modules/types/support/media/MediaURL.ts';
import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for image upload fields (single or multiple files).
 *
 * Used by forms to cap size/count, restrict MIME types, show existing media, and run post-upload hooks.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property maxFileSizeMB - Maximum size per file in megabytes when enforced client-side.
 * @property maxFiles - Maximum number of files accepted.
 * @property multiple - When true, the control accepts more than one file.
 * @property accept - HTML `accept` filter for file types (e.g. `image/*`).
 * @property media - Currently linked media preview/value from the server.
 * @property callback - Optional hook after upload or change; return false to veto state updates.
 */
export type UploadImageFieldOptions = BaseInputFieldOptions & {
    maxFileSizeMB?: number;
    maxFiles: number;
    multiple: boolean;
    accept?: string;

    media?: MediaURL | null;
    callback?: () => Promise<boolean>;
};
