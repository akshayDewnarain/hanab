/**
 * Options for building or pruning HTTP / JSON payloads from form data.
 *
 * Used by payload helpers to control file field handling and media preview stripping.
 *
 * @property fileOnlyKeys - Keys whose values should be treated as file-only payload segments.
 * @property skipMediaPreview - When true, omit or strip media preview data from serialized output.
 */
export type PayLoadOptions = {
    fileOnlyKeys?: string[];
    skipMediaPreview?: boolean;
};
